<?php

namespace Aura\Base\Services;

use Illuminate\Support\Facades\Schema;
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

    public function hasColumn(string $column, bool $allowHistoricalUpgradeDefaults = false): bool
    {
        if (! Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, $column)) {
            return false;
        }

        $this->assertColumns([$column], $allowHistoricalUpgradeDefaults);

        return true;
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
            'mysql', 'mariadb' => [
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
            && in_array($this->normalizeDefault($actual['default']), $expected['defaults'], true);
    }

    private function normalizeDefault(mixed $default): ?string
    {
        if ($default === null) {
            return null;
        }

        $default = trim((string) $default);

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
        return strtolower(preg_replace('/\s+/', ' ', trim($type)) ?? $type);
    }
}
