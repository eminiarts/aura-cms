<?php

namespace Aura\Base\Livewire\Table;

use Aura\Base\Contracts\TableResource;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;

final class TableMutationDispatcher
{
    private const BULK_MODE_COLLECTION = 'collection';

    private const BULK_MODE_RECORD = 'record';

    /** @var array<string, string> */
    private const DEFAULT_ABILITIES = [
        'delete' => 'delete',
        'forceDelete' => 'forceDelete',
        'restore' => 'restore',
        'update' => 'update',
    ];

    public function __construct(private readonly Gate $gate) {}

    public function abilityFor(string $action, mixed $definition = null): string
    {
        if (is_array($definition) && array_key_exists('ability', $definition)) {
            if (
                ! is_string($definition['ability'])
                || preg_match('/\A[A-Za-z][A-Za-z0-9._:-]*\z/', $definition['ability']) !== 1
            ) {
                abort(422, 'The declared table action ability is invalid.');
            }

            return $definition['ability'];
        }

        if (array_key_exists($action, self::DEFAULT_ABILITIES)) {
            return self::DEFAULT_ABILITIES[$action];
        }

        abort(422, 'Custom table actions must declare an ability.');
    }

    public function authorize(Model $record, string $ability): void
    {
        $this->gate->authorize($ability, $record);
    }

    /**
     * @param  array<string, mixed>  $declaredActions
     */
    public function dispatchAction(
        Builder $scope,
        TableMutationModelDescriptor $modelDescriptor,
        int|string $id,
        string $action,
        array $declaredActions,
    ): mixed {
        $descriptor = $this->descriptor($action, $declaredActions);
        $this->assertConditionAvailable($descriptor);

        return $modelDescriptor->connectionInstance()->transaction(function () use (
            $action,
            $descriptor,
            $id,
            $modelDescriptor,
            $scope,
        ): mixed {
            $modelDescriptor->assertMatches($scope);
            $scope = $this->applyTrashedScope($scope, $descriptor);
            $record = $this->findRecord(
                $scope,
                $modelDescriptor,
                $id,
                $descriptor['trashed'],
                lockForUpdate: true,
            );

            if (! $record instanceof TableResource) {
                abort(422, 'Table mutations require an Aura table resource.');
            }

            $this->mutationMethod($record, $action, self::BULK_MODE_RECORD);
            $this->authorize($record, $descriptor['ability']);

            return $record->{$action}();
        });
    }

    /**
     * Resolve, authorize, and execute one declared bulk mutation atomically.
     *
     * A dispatch is constrained to one Eloquent model query and therefore one
     * database connection. External effects from custom handlers cannot be
     * rolled back by the database transaction.
     *
     * @param  array<string, mixed>  $declaredActions
     */
    public function dispatchBulk(
        Builder $scope,
        TableMutationModelDescriptor $modelDescriptor,
        string $action,
        array $declaredActions,
        mixed $selected,
        bool $selectAll,
        string $expectedMode,
    ): mixed {
        $descriptor = $this->descriptor($action, $declaredActions, bulk: true);

        if ($descriptor['mode'] !== $expectedMode) {
            abort(422, 'The declared bulk action execution mode is invalid.');
        }

        $this->assertConditionAvailable($descriptor);

        return $modelDescriptor->connectionInstance()->transaction(function () use (
            $action,
            $descriptor,
            $modelDescriptor,
            $scope,
            $selectAll,
            $selected,
        ): mixed {
            $modelDescriptor->assertMatches($scope);
            $scope = $this->applyTrashedScope($scope, $descriptor);
            $records = $this->resolveExactSelection(
                $scope,
                $modelDescriptor,
                $selected,
                $selectAll,
                $descriptor['trashed'],
            );
            $receiver = $records->first();

            if (! $receiver instanceof Model || ! $receiver instanceof TableResource) {
                abort(422, 'Bulk mutations require an Aura table resource.');
            }

            $this->mutationMethod($receiver, $action, $descriptor['mode']);

            $records->each(function (Model $record) use ($descriptor, $modelDescriptor): void {
                $modelDescriptor->assertModelMatches($record);
                $this->authorize($record, $descriptor['ability']);
            });

            $ids = $records->map(fn (Model $record): mixed => $record->getKey())->all();

            if ($descriptor['mode'] === self::BULK_MODE_COLLECTION) {
                return $receiver->{$action}($ids);
            }

            $result = null;

            foreach ($records as $record) {
                $result = $record->{$action}();
            }

            return $result;
        });
    }

    public function dispatchFieldUpdate(
        Builder $scope,
        TableMutationModelDescriptor $modelDescriptor,
        int|string $id,
        string $fieldSlug,
        mixed $value,
    ): void {
        $modelDescriptor->connectionInstance()->transaction(function () use (
            $fieldSlug,
            $id,
            $modelDescriptor,
            $scope,
            $value,
        ): void {
            $modelDescriptor->assertMatches($scope);
            $record = $this->findRecord(
                $scope,
                $modelDescriptor,
                $id,
                lockForUpdate: true,
            );

            if (! $record instanceof TableResource) {
                abort(422, 'Kanban mutations require an Aura resource.');
            }

            $this->updateField($record, $fieldSlug, $value);
        });
    }

    public function findRecord(
        Builder $scope,
        TableMutationModelDescriptor $modelDescriptor,
        int|string $id,
        ?string $trashed = null,
        bool $lockForUpdate = false,
    ): Model {
        $modelDescriptor->assertMatches($scope);
        $expectedIdentity = $modelDescriptor->canonicalIdentity($id);
        $effectiveKeys = $this->effectiveKeys($scope->whereKey($id), $modelDescriptor);

        if (count($effectiveKeys) !== 1 || ! array_key_exists($expectedIdentity, $effectiveKeys)) {
            abort(404);
        }

        $records = $this->authoritativeRecords(
            $modelDescriptor,
            $effectiveKeys,
            $trashed,
            $lockForUpdate,
        );
        $resolvedIdentities = $this->canonicalIdentities($records, $modelDescriptor);
        $record = $records->first();

        if (
            ! $record instanceof Model
            || count($resolvedIdentities) !== 1
            || ! array_key_exists($expectedIdentity, $resolvedIdentities)
        ) {
            abort(404);
        }

        return $record;
    }

    public function updateField(Model&TableResource $record, string $fieldSlug, mixed $value): void
    {
        $this->authorize($record, 'update');

        $field = $record->fieldBySlug($fieldSlug);
        $fieldClass = $record->fieldClassBySlug($fieldSlug);

        if (
            ! is_array($field)
            || ($field['slug'] ?? null) !== $fieldSlug
            || (! $record->isTableField($fieldSlug) && ! $record->isMetaField($fieldSlug))
            || ! is_object($fieldClass)
            || ! method_exists($fieldClass, 'options')
        ) {
            throw ValidationException::withMessages([
                'kanbanField' => 'The configured Kanban group field is invalid.',
            ]);
        }

        $allowedValues = collect($fieldClass->options($record, $field))->map(function (mixed $option, mixed $key) {
            if (is_array($option) && array_key_exists('key', $option)) {
                return $option['key'];
            }

            return is_int($key) ? $option : $key;
        })->filter(fn (mixed $allowedValue) => is_string($allowedValue) || is_int($allowedValue));

        $matchedValue = $allowedValues->first(
            fn (mixed $allowedValue) => is_string($value) || is_int($value)
                ? (string) $allowedValue === (string) $value
                : false,
        );

        if ($matchedValue === null) {
            throw ValidationException::withMessages([
                'kanbanStatus' => 'The selected Kanban status is invalid.',
            ]);
        }

        $record->setAttribute($fieldSlug, $matchedValue);
        $record->save();
    }

    private function applyScopesOnce(Builder $scope): Builder
    {
        $scope = (clone $scope)->applyScopes();

        return $scope->withoutGlobalScopes();
    }

    private function applyTrashedMode(Builder $scope, ?string $trashed): Builder
    {
        if ($trashed === null) {
            return $scope;
        }

        if (! in_array($trashed, ['only', 'with'], true)) {
            abort(422, 'The declared trashed-record scope is invalid.');
        }

        if (! in_array(SoftDeletes::class, class_uses_recursive($scope->getModel()), true)) {
            abort(422, 'The declared action requires a soft-deleting resource.');
        }

        $model = $scope->getModel();
        $deletedAtConstant = $model::class.'::DELETED_AT';
        $deletedAtColumn = defined($deletedAtConstant) ? constant($deletedAtConstant) : 'deleted_at';

        if (! is_string($deletedAtColumn) || $deletedAtColumn === '') {
            abort(422, 'The soft-delete column declaration is invalid.');
        }

        $scope->withoutGlobalScope(SoftDeletingScope::class);

        return $trashed === 'only'
            ? $scope->whereNotNull($model->qualifyColumn($deletedAtColumn))
            : $scope;
    }

    /**
     * @param  array{ability: string, conditional_logic: mixed, mode: 'collection'|'record', trashed: 'only'|'with'|null}  $descriptor
     */
    private function applyTrashedScope(Builder $scope, array $descriptor): Builder
    {
        return $this->applyTrashedMode($scope, $descriptor['trashed']);
    }

    /**
     * @param  array{ability: string, conditional_logic: mixed, mode: 'collection'|'record', trashed: 'only'|'with'|null}  $descriptor
     */
    private function assertConditionAvailable(array $descriptor): void
    {
        $condition = $descriptor['conditional_logic'];

        if ($condition !== null && (! is_callable($condition) || ! $condition())) {
            abort(403, 'This table action is not available for the record.');
        }
    }

    /**
     * Rehydrate trusted base-table attributes under the model's default scopes.
     *
     * @param  array<string, int|string>  $keys
     * @return Collection<int, Model&TableResource>
     */
    private function authoritativeRecords(
        TableMutationModelDescriptor $modelDescriptor,
        array $keys,
        ?string $trashed,
        bool $lockForUpdate = false,
    ): Collection {
        $model = $modelDescriptor->model();

        if ($keys === []) {
            return $model->newCollection();
        }

        $query = $model->newQuery();
        $query = $this->applyTrashedMode($query, $trashed);
        $query->whereKey(array_values($keys));
        $query = $this->applyScopesOnce($query);
        $modelDescriptor->assertMatches($query);
        $query->select($model->qualifyColumn('*'));

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $baseQuery = $query->getQuery();
        $baseQuery->applyBeforeQueryCallbacks();
        $modelDescriptor->assertMatches($query);
        $baseQuery->select($modelDescriptor->table.'.*');

        if ($lockForUpdate) {
            $baseQuery->lockForUpdate();
        }

        $rows = $baseQuery->getConnection()->select(
            $baseQuery->toSql(),
            $baseQuery->getBindings(),
            ! $baseQuery->useWritePdo,
        );

        $hydratedRecords = [];

        foreach ($rows as $row) {
            if (! is_object($row)) {
                abort(422, 'The authoritative table mutation query returned an invalid record.');
            }

            $record = $modelDescriptor->hydrate((array) $row);
            $identity = $modelDescriptor->canonicalIdentity($record->getKey());

            if (! array_key_exists($identity, $keys)) {
                abort(422, 'The authoritative table mutation query returned an invalid record.');
            }

            $hydratedRecords[] = $record;
        }

        $hydratedRecords = $query->eagerLoadRelations($hydratedRecords);
        $records = $model->newCollection($hydratedRecords);

        $records->each(function (Model $record) use ($modelDescriptor): void {
            $modelDescriptor->assertModelMatches($record);
        });

        $records = $this->canonicalizeRecords($records, $modelDescriptor);
        $recordsByIdentity = [];

        foreach ($records as $record) {
            $recordsByIdentity[$modelDescriptor->canonicalIdentity($record->getKey())] = $record;
        }

        $orderedRecords = [];

        foreach (array_keys($keys) as $identity) {
            if (array_key_exists($identity, $recordsByIdentity)) {
                $orderedRecords[] = $recordsByIdentity[$identity];
            }
        }

        return $model->newCollection($orderedRecords);
    }

    /**
     * @param  Collection<int, Model&TableResource>  $records
     * @return array<string, true>
     */
    private function canonicalIdentities(
        Collection $records,
        TableMutationModelDescriptor $modelDescriptor,
    ): array {
        return $records
            ->mapWithKeys(
                fn (Model $record): array => [$modelDescriptor->canonicalIdentity($record->getKey()) => true]
            )
            ->all();
    }

    /**
     * @param  Collection<int, Model&TableResource>  $records
     * @return Collection<int, Model&TableResource>
     */
    private function canonicalizeRecords(
        Collection $records,
        TableMutationModelDescriptor $modelDescriptor,
    ): Collection {
        return $records
            ->unique(
                fn (Model $record): string => $modelDescriptor->canonicalIdentity($record->getKey()),
                true,
            )
            ->values();
    }

    /**
     * @param  array<string, mixed>  $declaredActions
     * @return array{ability: string, conditional_logic: mixed, mode: 'collection'|'record', trashed: 'only'|'with'|null}
     */
    private function descriptor(string $action, array $declaredActions, bool $bulk = false): array
    {
        if (! array_key_exists($action, $declaredActions)) {
            abort(403, $bulk ? 'This bulk action is not allowed.' : 'This table action is not allowed.');
        }

        $definition = $declaredActions[$action];

        if (! is_array($definition) && ! is_string($definition)) {
            abort(422, 'The declared table action is invalid.');
        }

        $mode = $bulk && is_array($definition) && array_key_exists('method', $definition)
            ? $definition['method']
            : self::BULK_MODE_RECORD;

        if (! is_string($mode) || ! in_array($mode, [self::BULK_MODE_COLLECTION, self::BULK_MODE_RECORD], true)) {
            abort(422, 'The declared bulk action execution mode is invalid.');
        }

        $ability = $this->abilityFor($action, $definition);
        $trashed = is_array($definition) ? ($definition['trashed'] ?? null) : null;

        if ($trashed !== null && ! in_array($trashed, ['only', 'with'], true)) {
            abort(422, 'The declared trashed-record scope is invalid.');
        }

        if ($trashed !== null && ! in_array($ability, ['forceDelete', 'restore'], true)) {
            abort(422, 'Only restore and force-delete actions may include trashed records.');
        }

        return [
            'ability' => $ability,
            'conditional_logic' => is_array($definition) ? ($definition['conditional_logic'] ?? null) : null,
            'mode' => $mode,
            'trashed' => $trashed,
        ];
    }

    /**
     * Resolve only qualified base-table keys from the effective table scope.
     *
     * @return array<string, int|string>
     */
    private function effectiveKeys(
        Builder $scope,
        TableMutationModelDescriptor $modelDescriptor,
    ): array {
        $modelDescriptor->assertMatches($scope);
        $keyAlias = '__aura_mutation_key';
        $keyQuery = $this->applyScopesOnce($scope);
        $modelDescriptor->assertMatches($keyQuery);
        $keyQuery->setEagerLoads([]);
        $keyQuery->select($modelDescriptor->table.'.'.$modelDescriptor->keyName.' as '.$keyAlias);
        $baseQuery = $keyQuery->toBase();

        $baseQuery->applyBeforeQueryCallbacks();
        $modelDescriptor->assertMatches($keyQuery);
        $baseQuery->select($modelDescriptor->table.'.'.$modelDescriptor->keyName.' as '.$keyAlias);

        $rows = $baseQuery->getConnection()->select(
            $baseQuery->toSql(),
            $baseQuery->getBindings(),
            ! $baseQuery->useWritePdo,
        );

        $keys = [];

        foreach ($rows as $row) {
            $key = is_object($row) && property_exists($row, $keyAlias)
                ? $row->{$keyAlias}
                : null;
            $identity = $modelDescriptor->canonicalIdentity($key);

            if (! array_key_exists($identity, $keys)) {
                $keys[$identity] = $key;
            }
        }

        return $keys;
    }

    private function mutationMethod(Model $receiver, string $action, string $mode): ReflectionMethod
    {
        if (! method_exists($receiver, $action)) {
            abort(422, 'The declared table action cannot be executed.');
        }

        $method = new ReflectionMethod($receiver, $action);
        $validParameterCount = $mode === self::BULK_MODE_COLLECTION
            ? $method->getNumberOfParameters() === 1
            : $method->getNumberOfRequiredParameters() === 0;

        if (! $method->isPublic() || $method->isStatic() || ! $validParameterCount) {
            abort(422, 'The declared table action cannot be executed.');
        }

        return $method;
    }

    /**
     * @return Collection<int, Model&TableResource>
     */
    private function resolveExactSelection(
        Builder $scope,
        TableMutationModelDescriptor $modelDescriptor,
        mixed $selected,
        bool $selectAll,
        ?string $trashed,
    ): Collection {
        if ($selectAll) {
            $effectiveKeys = $this->effectiveKeys($scope, $modelDescriptor);

            if ($effectiveKeys === []) {
                throw ValidationException::withMessages([
                    'selected' => 'Select at least one record.',
                ]);
            }

            $records = $this->authoritativeRecords(
                $modelDescriptor,
                $effectiveKeys,
                $trashed,
                lockForUpdate: true,
            );
            $resolvedIdentities = $this->canonicalIdentities($records, $modelDescriptor);

            if (
                array_diff_key($effectiveKeys, $resolvedIdentities) !== []
                || array_diff_key($resolvedIdentities, $effectiveKeys) !== []
            ) {
                throw ValidationException::withMessages([
                    'selected' => 'The selected records are invalid.',
                ]);
            }

            return $records;
        }

        if (! is_array($selected)) {
            throw ValidationException::withMessages([
                'selected' => 'The selected records are invalid.',
            ]);
        }

        $normalized = [];

        foreach ($selected as $id) {
            if ((! is_int($id) && ! is_string($id)) || (is_string($id) && $id === '')) {
                throw ValidationException::withMessages([
                    'selected' => 'The selected records are invalid.',
                ]);
            }

            $normalized[(string) $id] = $id;
        }

        if ($normalized === []) {
            throw ValidationException::withMessages([
                'selected' => 'Select at least one record.',
            ]);
        }

        $effectiveKeys = $this->effectiveKeys(
            $scope->whereKey(array_values($normalized)),
            $modelDescriptor,
        );
        $expectedIdentities = collect($normalized)
            ->mapWithKeys(
                fn (int|string $id): array => [$modelDescriptor->canonicalIdentity($id) => true]
            )
            ->all();

        if (
            array_diff_key($expectedIdentities, $effectiveKeys) !== []
            || array_diff_key($effectiveKeys, $expectedIdentities) !== []
        ) {
            throw ValidationException::withMessages([
                'selected' => 'The selected records are invalid.',
            ]);
        }

        $records = $this->authoritativeRecords(
            $modelDescriptor,
            $effectiveKeys,
            $trashed,
            lockForUpdate: true,
        );
        $resolvedIdentities = $this->canonicalIdentities($records, $modelDescriptor);

        if (
            array_diff_key($expectedIdentities, $resolvedIdentities) !== []
            || array_diff_key($resolvedIdentities, $expectedIdentities) !== []
        ) {
            throw ValidationException::withMessages([
                'selected' => 'The selected records are invalid.',
            ]);
        }

        return $records;
    }
}
