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
    private const CONTRACT_VERSION = 1;

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
        $incarnationTable = $grammar->wrapTable(EmbeddedResourceIncarnationStore::TABLE);
        $keyColumn = $grammar->wrap($resource->getKeyName());
        $resourceType = $this->literal($resource::class);
        $keyType = $this->literal($this->resourceKeyType($resource));
        $deleteTrigger = $grammar->wrap($names['delete']);
        $insertTrigger = $grammar->wrap($names['insert']);
        $updateTrigger = $grammar->wrap($names['update']);
        $castType = in_array($connection->getDriverName(), ['mysql', 'mariadb'], true) ? 'CHAR' : 'TEXT';
        $oldRowUpdate = sprintf(
            'update %s set %s = %s + 1, %s = CURRENT_TIMESTAMP where %s = %s and %s = %s and %s = CAST(OLD.%s AS %s)',
            $incarnationTable,
            $grammar->wrap('version'),
            $grammar->wrap('version'),
            $grammar->wrap('updated_at'),
            $grammar->wrap('resource_type'),
            $resourceType,
            $grammar->wrap('resource_key_type'),
            $keyType,
            $grammar->wrap('resource_key'),
            $keyColumn,
            $castType,
        );
        $newRowUpdate = str_replace('CAST(OLD.', 'CAST(NEW.', $oldRowUpdate);
        $destinationRowUpdate = sprintf(
            '%s and CAST(OLD.%s AS %s) <> CAST(NEW.%s AS %s)',
            $newRowUpdate,
            $keyColumn,
            $castType,
            $keyColumn,
            $castType,
        );

        if ($connection->getDriverName() === 'sqlite') {
            return [
                "create trigger {$deleteTrigger} after delete on {$ownerTable} for each row begin {$oldRowUpdate}; end",
                "create trigger {$insertTrigger} after insert on {$ownerTable} for each row begin {$newRowUpdate}; end",
                "create trigger {$updateTrigger} after update on {$ownerTable} for each row begin {$oldRowUpdate}; {$destinationRowUpdate}; end",
            ];
        }

        if (in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            return [
                "create trigger {$deleteTrigger} after delete on {$ownerTable} for each row {$oldRowUpdate}",
                "create trigger {$insertTrigger} after insert on {$ownerTable} for each row {$newRowUpdate}",
                "create trigger {$updateTrigger} after update on {$ownerTable} for each row begin {$oldRowUpdate}; {$destinationRowUpdate}; end",
            ];
        }

        if ($connection->getDriverName() === 'pgsql') {
            $function = $grammar->wrap($this->functionName($resource));
            $insertFunction = $grammar->wrap($this->insertFunctionName($resource));
            $functionStatement = "create or replace function {$function}() returns trigger as \$aura\$ begin {$oldRowUpdate}; if TG_OP = 'UPDATE' then {$destinationRowUpdate}; end if; return OLD; end; \$aura\$ language plpgsql";
            $insertFunctionStatement = "create or replace function {$insertFunction}() returns trigger as \$aura\$ begin {$newRowUpdate}; return NEW; end; \$aura\$ language plpgsql";

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
        $names = $this->triggerNames($resource);
        $ownerTable = $grammar->wrapTable($resource->getTable());

        if ($connection->getDriverName() === 'pgsql') {
            $connection->unprepared(sprintf(
                'drop trigger if exists %s on %s',
                $grammar->wrap($names['delete']),
                $ownerTable,
            ));
            $connection->unprepared(sprintf(
                'drop trigger if exists %s on %s',
                $grammar->wrap($names['insert']),
                $ownerTable,
            ));
            $connection->unprepared(sprintf(
                'drop trigger if exists %s on %s',
                $grammar->wrap($names['update']),
                $ownerTable,
            ));
            $connection->unprepared(sprintf(
                'drop function if exists %s()',
                $grammar->wrap($this->functionName($resource)),
            ));
            $connection->unprepared(sprintf(
                'drop function if exists %s()',
                $grammar->wrap($this->insertFunctionName($resource)),
            ));

            return;
        }

        if (! in_array($connection->getDriverName(), ['sqlite', 'mysql', 'mariadb'], true)) {
            throw new RuntimeException(sprintf(
                'Unsupported embedded incarnation guard driver [%s].',
                $connection->getDriverName(),
            ));
        }

        $connection->unprepared('drop trigger if exists '.$grammar->wrap($names['delete']));
        $connection->unprepared('drop trigger if exists '.$grammar->wrap($names['insert']));
        $connection->unprepared('drop trigger if exists '.$grammar->wrap($names['update']));
    }

    private function functionName(Model $resource): string
    {
        return 'aura_emb_fn_'.$this->guardHash($resource);
    }

    private function guardHash(Model $resource): string
    {
        return substr(hash('sha256', implode('|', [
            self::CONTRACT_VERSION,
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

    private function insertFunctionName(Model $resource): string
    {
        return 'aura_emb_ins_'.$this->guardHash($resource);
    }

    private function literal(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
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

    /**
     * @return array{delete: string, insert: string, update: string}
     */
    private function triggerNames(Model $resource): array
    {
        $hash = $this->guardHash($resource);

        return [
            'delete' => 'aura_emb_'.$hash.'_d',
            'insert' => 'aura_emb_'.$hash.'_i',
            'update' => 'aura_emb_'.$hash.'_u',
        ];
    }
}
