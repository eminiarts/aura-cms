<?php

namespace Aura\Base\Services;

use Closure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class MigrationOwnershipLedger
{
    public const CREATE_KEY = 'create_embedded_resource_incarnations';

    public const TABLE = 'aura_migration_ownership';

    public const UPGRADE_KEY = 'upgrade_embedded_resource_incarnations';

    /** @var list<string> */
    private const CREATE_STATES = ['creating', 'owned', 'table_drop_started', 'registry_drop_started'];

    private const FORMAT_VERSION = 1;

    /** @var list<string> */
    private const UPGRADE_COLUMNS = ['resource_key_type', 'resource_key', 'version'];

    /** @var list<string> */
    private const UPGRADE_INDEXES = [
        'aura_embedded_incarnation_guard_lookup',
        'aura_embedded_incarnation_guard_identity_unique',
    ];

    /** @var list<string> */
    private const UPGRADE_STATES = ['upgrading', 'owned', 'rollback_started', 'registry_drop_started'];

    public function __construct(private readonly ?Closure $checkpoint = null) {}

    public function checkpoint(string $checkpoint): void
    {
        if ($this->checkpoint !== null) {
            ($this->checkpoint)($checkpoint);
        }
    }

    public function delete(string $migration): void
    {
        DB::table(self::TABLE)->where('migration', $migration)->delete();
    }

    public function ensureRegistry(): bool
    {
        if ($this->registryExists()) {
            return false;
        }

        Schema::create(self::TABLE, function (Blueprint $table): void {
            $table->string('migration')->primary();
            $table->longText('ownership');
        });

        return true;
    }

    public function isSoleRecord(string $migration): bool
    {
        return DB::table(self::TABLE)->count() === 1
            && DB::table(self::TABLE)->where('migration', $migration)->exists();
    }

    /**
     * @return array{state: string, created_table: true, owns_registry: bool}|null
     */
    public function readCreate(): ?array
    {
        $record = $this->read(self::CREATE_KEY, self::CREATE_STATES);

        if ($record === null) {
            return null;
        }

        $this->assertExactKeys($record['payload'], ['created_table', 'owns_registry'], self::CREATE_KEY);

        if (($record['payload']['created_table'] ?? null) !== true
            || ! is_bool($record['payload']['owns_registry'] ?? null)
        ) {
            throw $this->invalidRecord(self::CREATE_KEY, 'payload types');
        }

        return [
            'state' => $record['state'],
            'created_table' => true,
            'owns_registry' => $record['payload']['owns_registry'],
        ];
    }

    /**
     * @return array{state: string, added_columns: list<string>, created_indexes: list<string>, owns_registry: bool}|null
     */
    public function readUpgrade(): ?array
    {
        $record = $this->read(self::UPGRADE_KEY, self::UPGRADE_STATES);

        if ($record === null) {
            return null;
        }

        $this->assertExactKeys(
            $record['payload'],
            ['added_columns', 'created_indexes', 'owns_registry'],
            self::UPGRADE_KEY,
        );

        $columns = $record['payload']['added_columns'] ?? null;
        $indexes = $record['payload']['created_indexes'] ?? null;

        if (! is_array($columns)
            || ! array_is_list($columns)
            || ! is_array($indexes)
            || ! array_is_list($indexes)
            || ! is_bool($record['payload']['owns_registry'] ?? null)
        ) {
            throw $this->invalidRecord(self::UPGRADE_KEY, 'payload types');
        }

        $this->assertAllowedList($columns, self::UPGRADE_COLUMNS, self::UPGRADE_KEY, 'added_columns');
        $this->assertAllowedList($indexes, self::UPGRADE_INDEXES, self::UPGRADE_KEY, 'created_indexes');

        if ($columns === [] && $indexes === []) {
            throw $this->invalidRecord(self::UPGRADE_KEY, 'empty ownership payload');
        }

        return [
            'state' => $record['state'],
            'added_columns' => $columns,
            'created_indexes' => $indexes,
            'owns_registry' => $record['payload']['owns_registry'],
        ];
    }

    public function registryExists(): bool
    {
        if (! Schema::hasTable(self::TABLE)) {
            return false;
        }

        if (! Schema::hasColumns(self::TABLE, ['migration', 'ownership'])) {
            throw new RuntimeException('The Aura migration ownership registry has an invalid schema.');
        }

        return true;
    }

    public function writeCreate(string $state, bool $ownsRegistry): void
    {
        if (! in_array($state, self::CREATE_STATES, true)) {
            throw $this->invalidRecord(self::CREATE_KEY, 'state');
        }

        $this->write(self::CREATE_KEY, $state, [
            'created_table' => true,
            'owns_registry' => $ownsRegistry,
        ]);
    }

    /**
     * @param  list<string>  $addedColumns
     * @param  list<string>  $createdIndexes
     */
    public function writeUpgrade(
        string $state,
        array $addedColumns,
        array $createdIndexes,
        bool $ownsRegistry,
    ): void {
        if (! in_array($state, self::UPGRADE_STATES, true)) {
            throw $this->invalidRecord(self::UPGRADE_KEY, 'state');
        }

        $this->assertAllowedList($addedColumns, self::UPGRADE_COLUMNS, self::UPGRADE_KEY, 'added_columns');
        $this->assertAllowedList($createdIndexes, self::UPGRADE_INDEXES, self::UPGRADE_KEY, 'created_indexes');
        $this->write(self::UPGRADE_KEY, $state, [
            'added_columns' => $addedColumns,
            'created_indexes' => $createdIndexes,
            'owns_registry' => $ownsRegistry,
        ]);
    }

    /**
     * @param  array<mixed>  $values
     * @param  list<string>  $allowed
     */
    private function assertAllowedList(array $values, array $allowed, string $migration, string $field): void
    {
        if (! array_is_list($values)
            || count($values) !== count(array_unique($values, SORT_REGULAR))
            || array_filter($values, fn (mixed $value): bool => ! is_string($value) || ! in_array($value, $allowed, true)) !== []
        ) {
            throw $this->invalidRecord($migration, $field);
        }
    }

    /**
     * @param  array<string, mixed>  $value
     * @param  list<string>  $keys
     */
    private function assertExactKeys(array $value, array $keys, string $migration): void
    {
        $actual = array_keys($value);
        sort($actual);
        sort($keys);

        if ($actual !== $keys) {
            throw $this->invalidRecord($migration, 'keys');
        }
    }

    private function invalidRecord(string $migration, string $reason): RuntimeException
    {
        return new RuntimeException("Invalid Aura migration ownership record for [{$migration}]: {$reason}.");
    }

    /**
     * @param  list<string>  $states
     * @return array{state: string, payload: array<string, mixed>}|null
     */
    private function read(string $migration, array $states): ?array
    {
        if (! $this->registryExists()) {
            return null;
        }

        $record = DB::table(self::TABLE)
            ->where('migration', $migration)
            ->first(['ownership']);

        if ($record === null) {
            return null;
        }

        $ownership = $record->ownership;

        if (! is_string($ownership)) {
            throw $this->invalidRecord($migration, 'ownership type');
        }

        $record = json_decode($ownership, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($record) || array_is_list($record)) {
            throw $this->invalidRecord($migration, 'record shape');
        }

        $this->assertExactKeys($record, ['version', 'migration', 'state', 'payload'], $migration);

        if (($record['version'] ?? null) !== self::FORMAT_VERSION
            || ($record['migration'] ?? null) !== $migration
            || ! is_string($record['state'] ?? null)
            || ! in_array($record['state'], $states, true)
            || ! is_array($record['payload'] ?? null)
            || array_is_list($record['payload'])
        ) {
            throw $this->invalidRecord($migration, 'version, migration, state, or payload');
        }

        return [
            'state' => $record['state'],
            'payload' => $record['payload'],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function write(string $migration, string $state, array $payload): void
    {
        DB::table(self::TABLE)->updateOrInsert(
            ['migration' => $migration],
            ['ownership' => json_encode([
                'version' => self::FORMAT_VERSION,
                'migration' => $migration,
                'state' => $state,
                'payload' => $payload,
            ], JSON_THROW_ON_ERROR)],
        );
    }
}
