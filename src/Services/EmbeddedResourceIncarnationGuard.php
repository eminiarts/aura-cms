<?php

namespace Aura\Base\Services;

use Aura\Base\Contracts\DefinesFields;
use Aura\Base\Exceptions\MissingEmbeddedResourceIncarnationGuard;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use RuntimeException;

final class EmbeddedResourceIncarnationGuard
{
    private const CONTRACT_VERSION = 2;

    /** @var array<string, true> */
    private array $verified = [];

    public function assertInstalled(Model $resource): void
    {
        $identity = $this->identity($resource);

        if (isset($this->verified[$identity])) {
            return;
        }

        if (! $this->isInstalled($resource)) {
            throw new MissingEmbeddedResourceIncarnationGuard(sprintf(
                'Secure embedded components require an incarnation guard for %s. Install it in a deployment migration.',
                $resource::class,
            ));
        }

        $this->verified[$identity] = true;
    }

    /**
     * Install database triggers for a resource from a deployment migration.
     *
     * @param  class-string<Model>|Model  $resource
     */
    public function install(string|Model $resource): void
    {
        $resource = $this->resource($resource);
        $connection = $resource->getConnection();
        $this->assertSchema($connection, $resource);

        if ($this->isInstalled($resource)) {
            return;
        }

        $this->dropStatements($connection, $resource);

        foreach ($this->createStatements($connection, $resource) as $statement) {
            $connection->unprepared($statement);
        }

        unset($this->verified[$this->identity($resource)]);

        if (! $this->isInstalled($resource)) {
            throw new RuntimeException('Unable to install the embedded resource incarnation guard.');
        }
    }

    public function isInstalled(Model $resource): bool
    {
        $connection = $resource->getConnection();
        $names = $this->triggerNames($resource);
        $driver = $connection->getDriverName();

        $rows = match ($driver) {
            'sqlite' => $connection->select(
                'select name from sqlite_master where type = ? and name in (?, ?, ?)',
                ['trigger', $names['delete'], $names['insert'], $names['update']],
            ),
            'mysql', 'mariadb' => $connection->select(
                'select TRIGGER_NAME as name from information_schema.TRIGGERS where TRIGGER_SCHEMA = database() and TRIGGER_NAME in (?, ?, ?)',
                [$names['delete'], $names['insert'], $names['update']],
            ),
            'pgsql' => $connection->select(
                'select trigger_name as name from information_schema.triggers where trigger_schema = current_schema() and trigger_name in (?, ?, ?)',
                [$names['delete'], $names['insert'], $names['update']],
            ),
            default => throw new RuntimeException("Unsupported embedded incarnation guard driver [{$driver}]."),
        };

        $installed = collect($rows)
            ->map(fn (object $row): string => (string) ($row->name ?? $row->TRIGGER_NAME ?? ''))
            ->unique()
            ->all();

        return in_array($names['delete'], $installed, true)
            && in_array($names['insert'], $installed, true)
            && in_array($names['update'], $installed, true);
    }

    /**
     * @param  class-string<Model>|Model  $resource
     */
    public function uninstall(string|Model $resource): void
    {
        $resource = $this->resource($resource);
        $connection = $resource->getConnection();
        $this->dropStatements($connection, $resource);
        unset($this->verified[$this->identity($resource)]);
    }

    private function assertSchema(Connection $connection, Model $resource): void
    {
        $schema = $connection->getSchemaBuilder();

        if (! $schema->hasTable(EmbeddedResourceIncarnationStore::TABLE)
            || ! $schema->hasColumns(EmbeddedResourceIncarnationStore::TABLE, [
                'resource_type',
                'resource_key_hash',
                'resource_key_type',
                'resource_key',
                'incarnation',
                'version',
            ])
            || ! $schema->hasIndex(
                EmbeddedResourceIncarnationStore::TABLE,
                'aura_embedded_incarnation_guard_identity_unique',
                'unique',
            )
        ) {
            throw new RuntimeException('Run the embedded resource incarnation migrations before installing guards.');
        }

        if (! $schema->hasTable($resource->getTable())
            || ! $schema->hasColumn($resource->getTable(), $resource->getKeyName())
        ) {
            throw new RuntimeException(sprintf(
                'Cannot guard missing owner table or key [%s.%s].',
                $resource->getTable(),
                $resource->getKeyName(),
            ));
        }
    }

    /**
     * @return list<string>
     */
    private function createStatements(Connection $connection, Model $resource): array
    {
        $grammar = $connection->getQueryGrammar();
        $names = $this->triggerNames($resource);
        $ownerTable = $grammar->wrapTable($resource->getTable());
        $keyColumn = $grammar->wrap($resource->getKeyName());
        $deleteTrigger = $grammar->wrap($names['delete']);
        $insertTrigger = $grammar->wrap($names['insert']);
        $updateTrigger = $grammar->wrap($names['update']);
        $castType = in_array($connection->getDriverName(), ['mysql', 'mariadb'], true) ? 'CHAR' : 'TEXT';
        $oldRowTouch = $this->touchStatement($connection, $resource, 'OLD');
        $newRowTouch = $this->touchStatement($connection, $resource, 'NEW');
        $keyChanged = sprintf(
            'CAST(OLD.%s AS %s) <> CAST(NEW.%s AS %s)',
            $keyColumn,
            $castType,
            $keyColumn,
            $castType,
        );

        if ($connection->getDriverName() === 'sqlite') {
            $destinationRowTouch = $this->touchStatement($connection, $resource, 'NEW', $keyChanged);

            return [
                "create trigger {$deleteTrigger} after delete on {$ownerTable} for each row begin {$oldRowTouch}; end",
                "create trigger {$insertTrigger} after insert on {$ownerTable} for each row begin {$newRowTouch}; end",
                "create trigger {$updateTrigger} after update on {$ownerTable} for each row begin {$oldRowTouch}; {$destinationRowTouch}; end",
            ];
        }

        if (in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            return [
                "create trigger {$deleteTrigger} after delete on {$ownerTable} for each row {$oldRowTouch}",
                "create trigger {$insertTrigger} after insert on {$ownerTable} for each row {$newRowTouch}",
                "create trigger {$updateTrigger} after update on {$ownerTable} for each row begin {$oldRowTouch}; if {$keyChanged} then {$newRowTouch}; end if; end",
            ];
        }

        if ($connection->getDriverName() === 'pgsql') {
            $function = $grammar->wrap($this->functionName($resource));
            $insertFunction = $grammar->wrap($this->insertFunctionName($resource));
            $functionStatement = "create or replace function {$function}() returns trigger as \$aura\$ begin {$oldRowTouch}; if TG_OP = 'UPDATE' and {$keyChanged} then {$newRowTouch}; end if; return OLD; end; \$aura\$ language plpgsql";
            $insertFunctionStatement = "create or replace function {$insertFunction}() returns trigger as \$aura\$ begin {$newRowTouch}; return NEW; end; \$aura\$ language plpgsql";

            return [
                $functionStatement,
                $insertFunctionStatement,
                "create trigger {$deleteTrigger} after delete on {$ownerTable} for each row execute function {$function}()",
                "create trigger {$insertTrigger} after insert on {$ownerTable} for each row execute function {$insertFunction}()",
                "create trigger {$updateTrigger} after update on {$ownerTable} for each row execute function {$function}()",
            ];
        }

        throw new RuntimeException(sprintf(
            'Unsupported embedded incarnation guard driver [%s].',
            $connection->getDriverName(),
        ));
    }

    private function dropStatements(Connection $connection, Model $resource): void
    {
        $grammar = $connection->getQueryGrammar();
        $ownerTable = $grammar->wrapTable($resource->getTable());
        $versions = array_values(array_unique([1, self::CONTRACT_VERSION]));

        if ($connection->getDriverName() === 'pgsql') {
            foreach ($versions as $version) {
                $names = $this->triggerNames($resource, $version);

                foreach ($names as $name) {
                    $connection->unprepared(sprintf(
                        'drop trigger if exists %s on %s',
                        $grammar->wrap($name),
                        $ownerTable,
                    ));
                }

                $connection->unprepared(sprintf(
                    'drop function if exists %s()',
                    $grammar->wrap($this->functionName($resource, $version)),
                ));
                $connection->unprepared(sprintf(
                    'drop function if exists %s()',
                    $grammar->wrap($this->insertFunctionName($resource, $version)),
                ));
            }

            return;
        }

        if (! in_array($connection->getDriverName(), ['sqlite', 'mysql', 'mariadb'], true)) {
            throw new RuntimeException(sprintf(
                'Unsupported embedded incarnation guard driver [%s].',
                $connection->getDriverName(),
            ));
        }

        foreach ($versions as $version) {
            foreach ($this->triggerNames($resource, $version) as $name) {
                $connection->unprepared('drop trigger if exists '.$grammar->wrap($name));
            }
        }
    }

    private function functionName(Model $resource, int $version = self::CONTRACT_VERSION): string
    {
        return 'aura_emb_fn_'.$this->guardHash($resource, $version);
    }

    private function guardHash(Model $resource, int $version = self::CONTRACT_VERSION): string
    {
        return substr(hash('sha256', implode('|', [
            $version,
            $resource::class,
            $resource->getConnection()->getDatabaseName(),
            $resource->getTable(),
            $resource->getKeyName(),
        ])), 0, 40);
    }

    private function identity(Model $resource): string
    {
        return $resource->getConnectionName().'|'.$this->guardHash($resource);
    }

    private function insertFunctionName(Model $resource, int $version = self::CONTRACT_VERSION): string
    {
        return 'aura_emb_ins_'.$this->guardHash($resource, $version);
    }

    private function literal(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }

    private function randomHashExpression(string $driver): string
    {
        return match ($driver) {
            'sqlite' => 'lower(hex(randomblob(32)))',
            'mysql', 'mariadb' => 'sha2(concat(uuid(), rand(), current_timestamp(6)), 256)',
            'pgsql' => 'md5(random()::text || clock_timestamp()::text) || md5(random()::text || clock_timestamp()::text)',
            default => throw new RuntimeException("Unsupported embedded incarnation guard driver [{$driver}]."),
        };
    }

    private function randomIncarnationExpression(string $driver): string
    {
        return match ($driver) {
            'sqlite' => "lower(hex(randomblob(4)) || '-' || hex(randomblob(2)) || '-' || hex(randomblob(2)) || '-' || hex(randomblob(2)) || '-' || hex(randomblob(6)))",
            'mysql', 'mariadb' => 'uuid()',
            'pgsql' => 'gen_random_uuid()',
            default => throw new RuntimeException("Unsupported embedded incarnation guard driver [{$driver}]."),
        };
    }

    /**
     * @param  class-string<Model>|Model  $resource
     * @return Model&DefinesFields
     */
    private function resource(string|Model $resource): Model
    {
        if (is_string($resource)) {
            if (! class_exists($resource)
                || ! is_subclass_of($resource, Model::class)
                || ! is_subclass_of($resource, DefinesFields::class)
            ) {
                throw new InvalidArgumentException('Incarnation guards require an Aura resource class.');
            }

            $resource = new $resource;
        }

        if (! $resource instanceof DefinesFields) {
            throw new InvalidArgumentException('Incarnation guards require an Aura resource model.');
        }

        return $resource;
    }

    private function resourceKeyType(Model $resource): string
    {
        return $resource->getKeyType() === 'int' ? 'integer' : 'string';
    }

    private function touchStatement(
        Connection $connection,
        Model $resource,
        string $rowReference,
        ?string $condition = null,
    ): string {
        $grammar = $connection->getQueryGrammar();
        $driver = $connection->getDriverName();
        $incarnationTable = $grammar->wrapTable(EmbeddedResourceIncarnationStore::TABLE);
        $keyColumn = $grammar->wrap($resource->getKeyName());
        $keyCast = in_array($driver, ['mysql', 'mariadb'], true) ? 'CHAR' : 'TEXT';
        $columns = collect([
            'resource_type',
            'resource_key_hash',
            'resource_key_type',
            'resource_key',
            'incarnation',
            'version',
            'created_at',
            'updated_at',
        ])->map($grammar->wrap(...))->implode(', ');
        $identityColumns = collect([
            'resource_type',
            'resource_key_type',
            'resource_key',
        ])->map($grammar->wrap(...))->implode(', ');
        $version = $grammar->wrap('version');
        $updatedAt = $grammar->wrap('updated_at');
        $existingVersion = $driver === 'pgsql'
            ? $incarnationTable.'.'.$version
            : $version;
        $values = implode(', ', [
            $this->literal($resource::class),
            $this->randomHashExpression($driver),
            $this->literal($this->resourceKeyType($resource)),
            sprintf('CAST(%s.%s AS %s)', $rowReference, $keyColumn, $keyCast),
            $this->randomIncarnationExpression($driver),
            '1',
            'CURRENT_TIMESTAMP',
            'CURRENT_TIMESTAMP',
        ]);

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            return sprintf(
                'insert into %s (%s) values (%s) on duplicate key update %s = %s + 1, %s = CURRENT_TIMESTAMP',
                $incarnationTable,
                $columns,
                $values,
                $version,
                $version,
                $updatedAt,
            );
        }

        $source = $condition === null
            ? sprintf('values (%s)', $values)
            : sprintf('select %s where %s', $values, $condition);

        return sprintf(
            'insert into %s (%s) %s on conflict (%s) do update set %s = %s + 1, %s = CURRENT_TIMESTAMP',
            $incarnationTable,
            $columns,
            $source,
            $identityColumns,
            $version,
            $existingVersion,
            $updatedAt,
        );
    }

    /**
     * @return array{delete: string, insert: string, update: string}
     */
    private function triggerNames(Model $resource, int $version = self::CONTRACT_VERSION): array
    {
        $hash = $this->guardHash($resource, $version);

        return [
            'delete' => 'aura_emb_'.$hash.'_d',
            'insert' => 'aura_emb_'.$hash.'_i',
            'update' => 'aura_emb_'.$hash.'_u',
        ];
    }
}
