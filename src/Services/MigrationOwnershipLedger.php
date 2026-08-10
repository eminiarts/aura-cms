<?php

namespace Aura\Base\Services;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class MigrationOwnershipLedger
{
    public const CREATE_KEY = 'create_embedded_resource_incarnations';

    public const MARKER_RESOURCE_TYPE = '__aura_internal_migration_generation__';

    public const TABLE = 'aura_migration_ownership';

    public const UPGRADE_KEY = 'upgrade_embedded_resource_incarnations';

    /** @var list<string> */
    private const CREATE_STATES = ['creating', 'owned', 'table_drop_started', 'registry_drop_started'];

    private const FORMAT_VERSION = 2;

    private const MARKER_COLUMN_PREFIX = 'aura_migration_owned_';

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

    public function assertTargetMarker(string $generation): void
    {
        $this->assertGeneration($generation);
        $expectedColumn = self::markerColumn($generation);
        $markerColumns = array_values(array_filter(
            Schema::getColumnListing(EmbeddedResourceIncarnationStore::TABLE),
            static fn (string $column): bool => str_starts_with($column, self::MARKER_COLUMN_PREFIX),
        ));

        if ($markerColumns !== [$expectedColumn]) {
            throw new RuntimeException('The CORE-12 migration target generation marker is missing or unexpected.');
        }

        $markers = DB::table(EmbeddedResourceIncarnationStore::TABLE)
            ->where('resource_type', self::MARKER_RESOURCE_TYPE)
            ->get([
                'resource_key_hash',
                'resource_key_type',
                'resource_key',
                'incarnation',
                'version',
                $expectedColumn,
            ]);

        if ($markers->count() !== 1) {
            throw new RuntimeException('The CORE-12 migration target generation marker is missing or duplicated.');
        }

        $marker = $markers->first();

        if ((string) $marker->resource_key_hash !== hash('sha256', $generation)
            || (string) $marker->resource_key_type !== 'internal'
            || (string) $marker->resource_key !== $generation
            || (string) $marker->incarnation !== $this->markerIncarnation($generation)
            || (int) $marker->version !== 1
            || (string) $marker->{$expectedColumn} !== $generation
        ) {
            throw new RuntimeException('The CORE-12 migration target generation marker is invalid.');
        }
    }

    public function checkpoint(string $checkpoint): void
    {
        if ($this->checkpoint !== null) {
            ($this->checkpoint)($checkpoint);
        }
    }

    public static function markerColumn(string $generation): string
    {
        return self::MARKER_COLUMN_PREFIX.$generation;
    }

    /**
     * @return array{state: string, created_table: true, owns_registry: bool, generation: string}|null
     */
    public function readCreate(): ?array
    {
        $record = $this->read(self::CREATE_KEY, self::CREATE_STATES);

        if ($record === null) {
            return null;
        }

        $this->assertExactKeys(
            $record['payload'],
            ['created_table', 'owns_registry', 'generation'],
            self::CREATE_KEY,
        );

        if (($record['payload']['created_table'] ?? null) !== true
            || ! is_bool($record['payload']['owns_registry'] ?? null)
            || ! $this->isGeneration($record['payload']['generation'] ?? null)
        ) {
            throw $this->invalidRecord(self::CREATE_KEY, 'payload types');
        }

        return [
            'state' => $record['state'],
            'created_table' => true,
            'owns_registry' => $record['payload']['owns_registry'],
            'generation' => $record['payload']['generation'],
        ];
    }

    /**
     * @return array{state: string, added_columns: list<string>, created_indexes: list<string>, owns_registry: bool, generation: string}|null
     */
    public function readUpgrade(): ?array
    {
        $record = $this->read(self::UPGRADE_KEY, self::UPGRADE_STATES);

        if ($record === null) {
            return null;
        }

        $this->assertExactKeys(
            $record['payload'],
            ['added_columns', 'created_indexes', 'owns_registry', 'generation'],
            self::UPGRADE_KEY,
        );

        $columns = $record['payload']['added_columns'] ?? null;
        $indexes = $record['payload']['created_indexes'] ?? null;

        if (! is_array($columns)
            || ! array_is_list($columns)
            || ! is_array($indexes)
            || ! array_is_list($indexes)
            || ! is_bool($record['payload']['owns_registry'] ?? null)
            || ! $this->isGeneration($record['payload']['generation'] ?? null)
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
            'generation' => $record['payload']['generation'],
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

    private function assertGeneration(string $generation): void
    {
        if (! $this->isGeneration($generation)) {
            throw new RuntimeException('Invalid CORE-12 migration target generation.');
        }
    }

    private function invalidRecord(string $migration, string $reason): RuntimeException
    {
        return new RuntimeException("Invalid Aura migration ownership record for [{$migration}]: {$reason}.");
    }

    private function isGeneration(mixed $generation): bool
    {
        return is_string($generation) && preg_match('/\A[0-9a-f]{32}\z/D', $generation) === 1;
    }

    private function markerIncarnation(string $generation): string
    {
        return substr($generation, 0, 8)
            .'-'.substr($generation, 8, 4)
            .'-'.substr($generation, 12, 4)
            .'-'.substr($generation, 16, 4)
            .'-'.substr($generation, 20, 12);
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
}
