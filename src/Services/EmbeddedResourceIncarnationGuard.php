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

    private const IDENTITY_INDEX = 'aura_embedded_incarnation_guard_identity_unique';

    public function assertInstalled(Model $resource): void
    {
        if (! $this->isInstalled($resource)) {
            throw new MissingEmbeddedResourceIncarnationGuard(sprintf(
                'Secure embedded components require an incarnation guard for %s. Install it in a deployment migration.',
                $resource::class,
            ));
        }
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

        $this->assertNoForeignTriggerNameConflicts($connection, $resource);
        $this->assertNoForeignPostgresFunctionDependencies($connection, $resource);
        $this->dropStatements($connection, $resource);

        foreach ($this->createStatements($connection, $resource) as $statement) {
            $connection->unprepared($statement);
        }

        if (! $this->isInstalled($resource)) {
            throw new RuntimeException('Unable to install the embedded resource incarnation guard.');
        }
    }

    public function isInstalled(Model $resource): bool
    {
        $connection = $resource->getConnection();

        return match ($connection->getDriverName()) {
            'sqlite' => $this->sqliteContractIsInstalled($connection, $resource),
            'mysql', 'mariadb' => $this->mysqlContractIsInstalled($connection, $resource),
            'pgsql' => $this->postgresContractIsInstalled($connection, $resource),
            default => throw new RuntimeException(sprintf(
                'Unsupported embedded incarnation guard driver [%s].',
                $connection->getDriverName(),
            )),
        };
    }

    /**
     * @param  class-string<Model>|Model  $resource
     */
    public function uninstall(string|Model $resource): void
    {
        $resource = $this->resource($resource);
        $connection = $resource->getConnection();
        $this->dropStatements($connection, $resource);
    }

    private function assertNoForeignPostgresFunctionDependencies(Connection $connection, Model $resource): void
    {
        if ($connection->getDriverName() !== 'pgsql') {
            return;
        }

        $allowedDependencies = [];

        foreach (array_values(array_unique([1, self::CONTRACT_VERSION])) as $version) {
            $names = $this->triggerNames($resource, $version);
            $allowedDependencies[$this->functionName($resource, $version)] = [
                $names['delete'],
                $names['update'],
            ];
            $allowedDependencies[$this->insertFunctionName($resource, $version)] = [$names['insert']];
        }

        $functions = array_keys($allowedDependencies);
        $ownerRelation = $connection->getQueryGrammar()->wrapTable($resource->getTable());
        $dependencies = $connection->select(
            <<<'SQL'
                select p.proname as function_name, t.tgname as trigger_name,
                       t.tgrelid = pg_catalog.to_regclass(?) as owns_relation
                from pg_catalog.pg_proc p
                join pg_catalog.pg_namespace n on n.oid = p.pronamespace
                join pg_catalog.pg_trigger t on t.tgfoid = p.oid
                where n.nspname = current_schema()
                  and p.proname in (?, ?, ?, ?)
                  and pg_catalog.pg_get_function_identity_arguments(p.oid) = ''
                SQL,
            [$ownerRelation, ...$functions],
        );
        $foreign = collect($dependencies)->first(function (object $dependency) use ($allowedDependencies): bool {
            return ! (bool) $dependency->owns_relation
                || ! in_array(
                    (string) $dependency->trigger_name,
                    $allowedDependencies[(string) $dependency->function_name] ?? [],
                    true,
                );
        });

        if ($foreign !== null) {
            throw new RuntimeException(sprintf(
                'Cannot install embedded incarnation guard because function [%s] is used by unowned trigger [%s].',
                (string) $foreign->function_name,
                (string) $foreign->trigger_name,
            ));
        }
    }

    private function assertNoForeignTriggerNameConflicts(Connection $connection, Model $resource): void
    {
        if (! in_array($connection->getDriverName(), ['sqlite', 'mysql', 'mariadb'], true)) {
            return;
        }

        $names = array_values($this->triggerNames($resource));
        $ownerTable = $this->ownerTable($connection, $resource);
        $rows = $connection->getDriverName() === 'sqlite'
            ? $connection->select(
                'select name, tbl_name from sqlite_master where type = ? and name in (?, ?, ?)',
                ['trigger', ...$names],
            )
            : $connection->select(
                'select TRIGGER_NAME as name, EVENT_OBJECT_TABLE as owner_table from information_schema.TRIGGERS where TRIGGER_SCHEMA = database() and TRIGGER_NAME in (?, ?, ?)',
                $names,
            );

        $foreign = collect($rows)->first(function (object $row) use ($ownerTable): bool {
            $actualTable = (string) ($row->tbl_name ?? $row->owner_table ?? '');

            return $actualTable !== $ownerTable;
        });

        if ($foreign !== null) {
            throw new RuntimeException(sprintf(
                'Cannot install embedded incarnation guard because trigger [%s] belongs to another table.',
                (string) ($foreign->name ?? ''),
            ));
        }
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
            || ! collect($schema->getIndexes(EmbeddedResourceIncarnationStore::TABLE))->contains(
                static fn (array $index): bool => $index['name'] === self::IDENTITY_INDEX
                    && $index['columns'] === ['resource_type', 'resource_key_type', 'resource_key']
                    && (bool) $index['unique'],
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

    private function canonicalSql(string $sql): string
    {
        $canonical = '';
        $length = strlen($sql);
        $quote = null;
        $spacePending = false;

        for ($index = 0; $index < $length; $index++) {
            $character = $sql[$index];

            if ($quote !== null) {
                $canonical .= $character;
                $closingQuote = $quote === '[' ? ']' : $quote;

                if ($character === $closingQuote) {
                    if ($quote !== '[' && ($sql[$index + 1] ?? null) === $closingQuote) {
                        $canonical .= $sql[++$index];
                    } else {
                        $quote = null;
                    }
                }

                continue;
            }

            if (in_array($character, ["'", '"', '`', '['], true)) {
                if ($spacePending && $canonical !== '') {
                    $canonical .= ' ';
                }

                $spacePending = false;
                $quote = $character;
                $canonical .= $character;

                continue;
            }

            if (ctype_space($character)) {
                $spacePending = true;

                continue;
            }

            if (str_contains('(),;.=<>+', $character)) {
                $canonical = rtrim($canonical);
                $canonical .= $character;
                $spacePending = false;

                continue;
            }

            if ($spacePending && $canonical !== '') {
                $canonical .= ' ';
            }

            $spacePending = false;
            $canonical .= strtolower($character);
        }

        return trim($canonical);
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
            $functionStatement = "create or replace function {$function}() returns trigger as \$aura\$ begin {$oldRowTouch}; if TG_OP = 'UPDATE' and {$keyChanged} then {$newRowTouch}; end if; return OLD; end; \$aura\$ language plpgsql volatile parallel unsafe security invoker";
            $insertFunctionStatement = "create or replace function {$insertFunction}() returns trigger as \$aura\$ begin {$newRowTouch}; return NEW; end; \$aura\$ language plpgsql volatile parallel unsafe security invoker";

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

    private function dropPostgresFunctionIfUnused(Connection $connection, string $function): void
    {
        $unused = $connection->selectOne(
            <<<'SQL'
                select p.oid
                from pg_catalog.pg_proc p
                join pg_catalog.pg_namespace n on n.oid = p.pronamespace
                where n.nspname = current_schema()
                  and p.proname = ?
                  and pg_catalog.pg_get_function_identity_arguments(p.oid) = ''
                  and not exists (
                      select 1 from pg_catalog.pg_trigger t where t.tgfoid = p.oid
                  )
                SQL,
            [$function],
        );

        if ($unused !== null) {
            $connection->unprepared(sprintf(
                'drop function %s()',
                $connection->getQueryGrammar()->wrap($function),
            ));
        }
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

                $this->dropPostgresFunctionIfUnused(
                    $connection,
                    $this->functionName($resource, $version),
                );
                $this->dropPostgresFunctionIfUnused(
                    $connection,
                    $this->insertFunctionName($resource, $version),
                );
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
            foreach ($this->ownedTriggerNames($connection, $resource, $version) as $name) {
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

    private function insertFunctionName(Model $resource, int $version = self::CONTRACT_VERSION): string
    {
        return 'aura_emb_ins_'.$this->guardHash($resource, $version);
    }

    private function literal(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }

    private function mysqlContractIsInstalled(Connection $connection, Model $resource): bool
    {
        $names = $this->triggerNames($resource);
        $rows = collect($connection->select(
            'select TRIGGER_NAME as name, EVENT_MANIPULATION as event, EVENT_OBJECT_TABLE as owner_table, ACTION_STATEMENT as action_statement, ACTION_ORIENTATION as orientation, ACTION_TIMING as timing from information_schema.TRIGGERS where TRIGGER_SCHEMA = database() and EVENT_OBJECT_TABLE = ? and TRIGGER_NAME in (?, ?, ?)',
            [$this->ownerTable($connection, $resource), ...array_values($names)],
        ))->keyBy(fn (object $row): string => (string) $row->name);
        $actions = $this->mysqlExpectedActions($connection, $resource);

        foreach (['delete', 'insert', 'update'] as $event) {
            $row = $rows->get($names[$event]);

            if (! is_object($row)
                || strtoupper((string) $row->event) !== strtoupper($event)
                || strtoupper((string) $row->timing) !== 'AFTER'
                || strtoupper((string) $row->orientation) !== 'ROW'
                || $this->canonicalSql((string) $row->action_statement) !== $this->canonicalSql($actions[$event])
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{delete: string, insert: string, update: string}
     */
    private function mysqlExpectedActions(Connection $connection, Model $resource): array
    {
        $actions = [];

        foreach (array_slice($this->createStatements($connection, $resource), -3) as $statement) {
            if (preg_match('/create trigger .+ after (delete|insert|update) on .+ for each row (.+)\z/is', $statement, $matches) !== 1) {
                throw new RuntimeException('Unable to derive the embedded incarnation trigger contract.');
            }

            $actions[strtolower($matches[1])] = $matches[2];
        }

        if (! isset($actions['delete'], $actions['insert'], $actions['update'])) {
            throw new RuntimeException('Unable to derive the complete embedded incarnation trigger contract.');
        }

        return [
            'delete' => $actions['delete'],
            'insert' => $actions['insert'],
            'update' => $actions['update'],
        ];
    }

    /**
     * @return list<string>
     */
    private function ownedTriggerNames(Connection $connection, Model $resource, int $version): array
    {
        $names = array_values($this->triggerNames($resource, $version));
        $rows = $connection->getDriverName() === 'sqlite'
            ? $connection->select(
                'select name from sqlite_master where type = ? and tbl_name = ? and name in (?, ?, ?)',
                ['trigger', $this->ownerTable($connection, $resource), ...$names],
            )
            : $connection->select(
                'select TRIGGER_NAME as name from information_schema.TRIGGERS where TRIGGER_SCHEMA = database() and EVENT_OBJECT_TABLE = ? and TRIGGER_NAME in (?, ?, ?)',
                [$this->ownerTable($connection, $resource), ...$names],
            );

        return collect($rows)
            ->map(fn (object $row): string => (string) ($row->name ?? ''))
            ->all();
    }

    private function ownerTable(Connection $connection, Model $resource): string
    {
        $table = $connection->getTablePrefix().$resource->getTable();

        return str_contains($table, '.') ? (string) str($table)->afterLast('.') : $table;
    }

    private function postgresContractIsInstalled(Connection $connection, Model $resource): bool
    {
        $names = $this->triggerNames($resource);
        $rows = collect($connection->select(
            <<<'SQL'
                select t.tgname as name, t.tgtype as trigger_type, t.tgenabled as enabled,
                       t.tgqual as condition,
                       p.proname as function_name, pn.nspname as function_schema, p.prosrc as function_source,
                       p.prosecdef as security_definer, p.provolatile as volatility,
                       p.proparallel as parallel_safety, p.proleakproof as leakproof,
                       p.proconfig as runtime_config, l.lanname as language
                from pg_catalog.pg_trigger t
                join pg_catalog.pg_class c on c.oid = t.tgrelid
                join pg_catalog.pg_proc p on p.oid = t.tgfoid
                join pg_catalog.pg_namespace pn on pn.oid = p.pronamespace
                join pg_catalog.pg_language l on l.oid = p.prolang
                where not t.tgisinternal
                  and c.oid = pg_catalog.to_regclass(?)
                  and t.tgname in (?, ?, ?)
                SQL,
            [$connection->getQueryGrammar()->wrapTable($resource->getTable()), ...array_values($names)],
        ))->keyBy(fn (object $row): string => (string) $row->name);
        $sources = $this->postgresExpectedFunctionSources($connection, $resource);
        $functionSchema = (string) ($connection->selectOne('select current_schema() as name')->name ?? '');
        $eventBits = ['delete' => 8, 'insert' => 4, 'update' => 16];

        foreach ($eventBits as $event => $eventBit) {
            $row = $rows->get($names[$event]);
            $expectedFunction = $event === 'insert'
                ? $this->insertFunctionName($resource)
                : $this->functionName($resource);

            if (! is_object($row)
                || (int) $row->trigger_type !== (1 | $eventBit)
                || (string) $row->enabled !== 'O'
                || $row->condition !== null
                || (string) $row->function_name !== $expectedFunction
                || (string) $row->function_schema !== $functionSchema
                || (bool) $row->security_definer
                || (string) $row->volatility !== 'v'
                || (string) $row->parallel_safety !== 'u'
                || (bool) $row->leakproof
                || $row->runtime_config !== null
                || (string) $row->language !== 'plpgsql'
                || $this->canonicalSql((string) $row->function_source) !== $this->canonicalSql($sources[$expectedFunction])
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    private function postgresExpectedFunctionSources(Connection $connection, Model $resource): array
    {
        $sources = [];

        foreach (array_slice($this->createStatements($connection, $resource), 0, 2) as $statement) {
            if (preg_match('/create or replace function ["`]?([^"`()]+)["`]?\(\) returns trigger as \$aura\$(.+)\$aura\$ language plpgsql volatile parallel unsafe security invoker\z/is', $statement, $matches) !== 1) {
                throw new RuntimeException('Unable to derive the embedded incarnation function contract.');
            }

            $sources[$matches[1]] = $matches[2];
        }

        return $sources;
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

    private function sqliteContractIsInstalled(Connection $connection, Model $resource): bool
    {
        $names = $this->triggerNames($resource);
        $rows = collect($connection->select(
            'select name, tbl_name, sql from sqlite_master where type = ? and tbl_name = ? and name in (?, ?, ?)',
            ['trigger', $this->ownerTable($connection, $resource), ...array_values($names)],
        ))->keyBy(fn (object $row): string => (string) $row->name);
        $statements = collect($this->createStatements($connection, $resource))
            ->keyBy(function (string $statement): string {
                preg_match('/create trigger ["`]?([^"` ]+)/i', $statement, $matches);

                return $matches[1] ?? '';
            });

        foreach ($names as $name) {
            $row = $rows->get($name);

            if (! is_object($row)
                || $this->canonicalSql((string) $row->sql) !== $this->canonicalSql((string) $statements->get($name))
            ) {
                return false;
            }
        }

        return true;
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
