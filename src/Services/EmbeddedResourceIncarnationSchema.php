<?php

namespace Aura\Base\Services;

use Illuminate\Database\Grammar;
use Illuminate\Database\MariaDbConnection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\MariaDbGrammar;
use Illuminate\Support\Facades\Schema;
use PDO;
use Pdo\Mysql as PdoMySql;
use ReflectionProperty;
use RuntimeException;

final class EmbeddedResourceIncarnationSchema
{
    public const LEGACY_KEY_TYPE = 'legacy';

    /** @var list<string> */
    public const REQUIRED_COLUMNS = [
        'id',
        'resource_type',
        'resource_key_hash',
        'resource_key_type',
        'resource_key',
        'incarnation',
        'version',
        'created_at',
        'updated_at',
    ];

    /** @var list<string> */
    public const UPGRADE_COLUMNS = ['resource_key_type', 'resource_key', 'version'];

    /**
     * @param  list<string>  $columns
     */
    public function assertColumns(array $columns, bool $allowHistoricalUpgradeDefaults = false): void
    {
        if (! Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE)) {
            throw new RuntimeException('The embedded resource incarnation table has an unexpected schema because it is missing.');
        }

        $actualColumns = collect(Schema::getColumns(EmbeddedResourceIncarnationStore::TABLE))
            ->keyBy('name');

        foreach ($columns as $column) {
            $actual = $actualColumns->get($column);

            if (! is_array($actual)
                || ! $this->hasExpectedDefinition($column, $actual, $allowHistoricalUpgradeDefaults)
            ) {
                $definition = json_encode($actual, JSON_THROW_ON_ERROR);
                $connection = Schema::getConnection()->getName();

                throw new RuntimeException(
                    "The column [{$column}] has an unexpected definition on [{$connection}]: {$definition}",
                );
            }
        }
    }

    public function assertPrimaryKey(): void
    {
        $primary = collect(Schema::getIndexes(EmbeddedResourceIncarnationStore::TABLE))
            ->filter(static fn (array $index): bool => (bool) ($index['primary'] ?? false));

        if ($primary->count() !== 1
            || $primary->first()['columns'] !== ['id']
            || ! (bool) $primary->first()['unique']
        ) {
            $indexes = json_encode($primary->values()->all(), JSON_THROW_ON_ERROR);

            throw new RuntimeException(
                "The embedded resource incarnation primary key has an unexpected definition: {$indexes}",
            );
        }
    }

    public function assertReliableConnectionCapability(): void
    {
        $connection = Schema::getConnection();
        $grammar = $connection->getSchemaGrammar();

        if ($connection->getDriverName() === 'mariadb'
            || $connection->getConfig('driver') === 'mariadb'
            || $connection instanceof MariaDbConnection
            || $grammar instanceof MariaDbGrammar
        ) {
            $this->reliableMariaDbUuidType();
        }
    }

    public function hasColumn(string $column, bool $allowHistoricalUpgradeDefaults = false): bool
    {
        if (! Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, $column)) {
            return false;
        }

        $this->assertColumns([$column], $allowHistoricalUpgradeDefaults);

        return true;
    }

    private function compiledMariaDbUuidType(MariaDbConnection $connection): string
    {
        $blueprint = new Blueprint($connection, 'aura_embedded_resource_incarnation_schema_probe');
        $blueprint->create();
        $blueprint->uuid('incarnation');
        $statements = $blueprint->toSql();
        $matchCount = preg_match_all(
            '/`incarnation`\s+(uuid|char\(36\))(?=\s|,|\))/i',
            implode(' ', $statements),
            $matches,
        );

        if ($matchCount !== 1 || ! isset($matches[1][0])) {
            throw new RuntimeException(
                'Unable to determine the expected MariaDB UUID storage from the configured schema grammar.',
            );
        }

        return strtolower($matches[1][0]);
    }

    /**
     * @return array{type: string, nullable: bool, auto_increment: bool, defaults: list<string|null>}
     */
    private function expectedDefinition(string $column, bool $allowHistoricalUpgradeDefaults): array
    {
        $driver = Schema::getConnection()->getDriverName();
        $types = match ($driver) {
            'sqlite' => [
                'id' => 'integer',
                'resource_type' => 'varchar',
                'resource_key_hash' => 'varchar',
                'resource_key_type' => 'varchar',
                'resource_key' => 'varchar',
                'incarnation' => 'varchar',
                'version' => 'integer',
                'created_at' => 'datetime',
                'updated_at' => 'datetime',
            ],
            'mysql' => [
                'id' => 'bigint unsigned',
                'resource_type' => 'varchar(255)',
                'resource_key_hash' => 'char(64)',
                'resource_key_type' => 'varchar(16)',
                'resource_key' => 'varchar(191)',
                'incarnation' => 'char(36)',
                'version' => 'bigint unsigned',
                'created_at' => 'timestamp',
                'updated_at' => 'timestamp',
            ],
            'mariadb' => [
                'id' => 'bigint unsigned',
                'resource_type' => 'varchar(255)',
                'resource_key_hash' => 'char(64)',
                'resource_key_type' => 'varchar(16)',
                'resource_key' => 'varchar(191)',
                'incarnation' => $this->expectedMariaDbUuidType(),
                'version' => 'bigint unsigned',
                'created_at' => 'timestamp',
                'updated_at' => 'timestamp',
            ],
            'pgsql' => [
                'id' => 'bigint',
                'resource_type' => 'character varying(255)',
                'resource_key_hash' => 'character(64)',
                'resource_key_type' => 'character varying(16)',
                'resource_key' => 'character varying(191)',
                'incarnation' => 'uuid',
                'version' => 'bigint',
                'created_at' => 'timestamp(0) without time zone',
                'updated_at' => 'timestamp(0) without time zone',
            ],
            default => throw new RuntimeException("Unsupported embedded incarnation schema driver [{$driver}]."),
        };

        if (! array_key_exists($column, $types)) {
            throw new RuntimeException("Unknown embedded incarnation column [{$column}].");
        }

        $defaults = match ($column) {
            'id' => $driver === 'pgsql' ? ['__auto_increment__'] : [null],
            'version' => ['1'],
            'resource_key_type' => $allowHistoricalUpgradeDefaults
                ? [null, 'string', self::LEGACY_KEY_TYPE]
                : [null],
            'resource_key' => $allowHistoricalUpgradeDefaults ? [null, ''] : [null],
            default => [null],
        };

        return [
            'type' => $types[$column],
            'nullable' => in_array($column, ['created_at', 'updated_at'], true),
            'auto_increment' => $column === 'id',
            'defaults' => $defaults,
        ];
    }

    private function expectedMariaDbUuidType(): string
    {
        return $this->reliableMariaDbUuidType();
    }

    private function grammarConnection(MariaDbGrammar $grammar): mixed
    {
        return (new ReflectionProperty(Grammar::class, 'connection'))->getValue($grammar);
    }

    /**
     * @param  array{name: string, type: string, type_name: string, collation: string|null, nullable: bool, default: mixed, auto_increment: bool, comment: string|null, generation: array{type: string, expression: string|null}|null}  $actual
     */
    private function hasExpectedDefinition(
        string $column,
        array $actual,
        bool $allowHistoricalUpgradeDefaults,
    ): bool {
        $expected = $this->expectedDefinition($column, $allowHistoricalUpgradeDefaults);

        return $this->normalizeType((string) $actual['type']) === $expected['type']
            && (bool) $actual['nullable'] === $expected['nullable']
            && (bool) $actual['auto_increment'] === $expected['auto_increment']
            && $actual['generation'] === null
            && in_array(
                $this->normalizeDefault(
                    $actual['default'],
                    $expected['nullable'] && $expected['defaults'] === [null],
                ),
                $expected['defaults'],
                true,
            );
    }

    private function normalizeDefault(mixed $default, bool $acceptMariaDbNullLiteral): ?string
    {
        if ($default === null) {
            return null;
        }

        $default = trim((string) $default);

        if ($acceptMariaDbNullLiteral
            && Schema::getConnection()->getDriverName() === 'mariadb'
            && $default === 'NULL'
        ) {
            return null;
        }

        if (str_starts_with($default, 'nextval(')) {
            return '__auto_increment__';
        }

        $default = preg_replace('/::[a-z0-9_ ]+(?:\([0-9, ]+\))?\z/i', '', $default) ?? $default;

        if (strlen($default) >= 2 && $default[0] === "'" && $default[-1] === "'") {
            $default = str_replace("''", "'", substr($default, 1, -1));
        }

        return $default;
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower(preg_replace('/\s+/', ' ', trim($type)) ?? $type);

        if (Schema::getConnection()->getDriverName() === 'mariadb') {
            if ($type === 'bigint(20) unsigned') {
                return 'bigint unsigned';
            }
        }

        return $type;
    }

    private function parseMariaDbServerVersion(mixed $metadata): ?string
    {
        if (! is_string($metadata)
            || preg_match(
                '/\A(?:5\.5\.5-)?(?<version>\d+\.\d+\.\d+)-MariaDB(?:[-+][0-9A-Za-z._~]+)*\z/D',
                $metadata,
                $matches,
            ) !== 1
        ) {
            return null;
        }

        return $matches['version'];
    }

    private function reliableMariaDbUuidType(): string
    {
        $connection = Schema::getConnection();
        $grammar = $connection->getSchemaGrammar();

        if ($connection::class !== MariaDbConnection::class
            || $connection->getConfig('driver') !== 'mariadb'
            || $grammar::class !== MariaDbGrammar::class
            || $this->grammarConnection($grammar) !== $connection
        ) {
            throw new RuntimeException(
                'Unable to determine the expected MariaDB UUID storage from trusted framework connection metadata.',
            );
        }

        $serverVersion = $this->verifiedMariaDbServerVersion($connection);
        $compiledUuidType = $this->compiledMariaDbUuidType($connection);
        $expectedUuidType = version_compare($serverVersion, '10.7.0', '<') ? 'char(36)' : 'uuid';

        if ($compiledUuidType !== $expectedUuidType) {
            throw new RuntimeException(
                'The configured MariaDB schema grammar does not match the verified server UUID capability.',
            );
        }

        return $expectedUuidType;
    }

    private function verifiedMariaDbServerVersion(MariaDbConnection $connection): string
    {
        $pdo = $connection->getPdo();
        $attributeVersion = $this->parseMariaDbServerVersion($pdo->getAttribute(PDO::ATTR_SERVER_VERSION));
        $statement = $pdo->query('select version()');
        $queryVersion = $statement === false
            ? null
            : $this->parseMariaDbServerVersion($statement->fetchColumn());
        $frameworkVersion = $connection->getServerVersion();

        if (! in_array($pdo::class, [PDO::class, PdoMySql::class], true)
            || $attributeVersion === null
            || $queryVersion === null
            || $queryVersion !== $attributeVersion
            || $frameworkVersion !== $attributeVersion
        ) {
            throw new RuntimeException(
                'Unable to determine the expected MariaDB UUID storage from independently verified server metadata.',
            );
        }

        return $attributeVersion;
    }
}
