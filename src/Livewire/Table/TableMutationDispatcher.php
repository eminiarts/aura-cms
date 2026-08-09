<?php

namespace Aura\Base\Livewire\Table;

use Aura\Base\Contracts\TableResource;
use BackedEnum;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Database\Query\Expression as ExpressionContract;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DetectsConcurrencyErrors;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Grammars\MariaDbGrammar;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Stringable;
use Throwable;
use UnitEnum;

/**
 * Executes table mutations in one locked authorization transaction.
 */
final class TableMutationDispatcher
{
    use DetectsConcurrencyErrors;

    private const BULK_MODE_COLLECTION = 'collection';

    private const BULK_MODE_RECORD = 'record';

    private const DEADLOCK_RETRY_ATTEMPTS = 3;

    /** @var array<string, string> */
    private const DEFAULT_ABILITIES = [
        'delete' => 'delete',
        'forceDelete' => 'forceDelete',
        'restore' => 'restore',
        'update' => 'update',
    ];

    /** @var list<string> */
    private const SAFE_CALLBACK_HAVING_TYPES = [
        'basic',
        'between',
        'bitwise',
        'nested',
        'notnull',
        'null',
    ];

    /** @var list<string> */
    private const SAFE_CALLBACK_WHERE_TYPES = [
        'basic',
        'between',
        'betweencolumns',
        'bitwise',
        'column',
        'date',
        'day',
        'in',
        'inraw',
        'like',
        'month',
        'nested',
        'notin',
        'notinraw',
        'notnull',
        'null',
        'nullsafeequals',
        'rowvalues',
        'time',
        'valuebetween',
        'year',
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
     * Resolve and authorize a declared modal action without invoking a model
     * handler. The returned component and identifiers originate only from the
     * server-side resource declaration and authoritative mutation scope.
     *
     * @param  array<string, mixed>  $declaredActions
     * @return array{component: string, ids: array<int, int|string>}
     */
    public function authorizeBulkModal(
        Builder $scope,
        TableMutationModelDescriptor $modelDescriptor,
        string $action,
        array $declaredActions,
        mixed $selected,
        bool $selectAll,
    ): array {
        if (! array_key_exists($action, $declaredActions)) {
            abort(403, 'This bulk action is not allowed.');
        }

        $definition = $declaredActions[$action];

        if (
            ! is_array($definition)
            || ! is_string($definition['modal'] ?? null)
            || preg_match('/\A[A-Za-z0-9][A-Za-z0-9._:-]*\z/', $definition['modal']) !== 1
        ) {
            abort(422, 'The declared bulk modal action is invalid.');
        }

        $descriptor = $this->descriptor($action, $declaredActions, bulk: true);
        $this->assertConditionAvailable($descriptor);

        return $this->transactionWithPreLockRetries($modelDescriptor->connectionInstance(), function (
            Closure $markLockAcquired,
        ) use (
            $definition,
            $descriptor,
            $modelDescriptor,
            $scope,
            $selectAll,
            $selected,
        ): array {
            $modelDescriptor->assertMatches($scope);
            $records = $this->resolveExactSelection(
                $scope,
                $modelDescriptor,
                $selected,
                $selectAll,
                $descriptor['trashed'],
                $markLockAcquired,
            );

            $records->each(function (Model $record) use ($descriptor, $modelDescriptor): void {
                $modelDescriptor->assertModelMatches($record);
                $this->authorize($record, $descriptor['ability']);
            });

            return [
                'component' => $definition['modal'],
                'ids' => $records->map(fn (Model $record): mixed => $record->getKey())->all(),
            ];
        });
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

        return $this->transactionWithPreLockRetries($modelDescriptor->connectionInstance(), function (
            Closure $markLockAcquired,
        ) use (
            $action,
            $descriptor,
            $id,
            $modelDescriptor,
            $scope,
        ): mixed {
            $modelDescriptor->assertMatches($scope);
            $record = $this->findRecord(
                $scope,
                $modelDescriptor,
                $id,
                $descriptor['trashed'],
                lockForUpdate: true,
                markLockAcquired: $markLockAcquired,
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
     * A dispatch is constrained to one mounted model and connection; scope
     * membership and row locking execute in one SQL statement. External effects
     * from custom handlers cannot be rolled back by the database transaction.
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

        return $this->transactionWithPreLockRetries($modelDescriptor->connectionInstance(), function (
            Closure $markLockAcquired,
        ) use (
            $action,
            $descriptor,
            $modelDescriptor,
            $scope,
            $selectAll,
            $selected,
        ): mixed {
            $modelDescriptor->assertMatches($scope);
            $records = $this->resolveExactSelection(
                $scope,
                $modelDescriptor,
                $selected,
                $selectAll,
                $descriptor['trashed'],
                $markLockAcquired,
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
        $this->transactionWithPreLockRetries($modelDescriptor->connectionInstance(), function (
            Closure $markLockAcquired,
        ) use (
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
                markLockAcquired: $markLockAcquired,
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
        ?Closure $markLockAcquired = null,
    ): Model {
        $modelDescriptor->assertMatches($scope);
        $expectedIdentity = $modelDescriptor->canonicalIdentity($id);
        $records = $this->authoritativeRecords(
            (clone $scope)->whereKey($id),
            $modelDescriptor,
            [$expectedIdentity => $id],
            $trashed,
            $lockForUpdate,
            $markLockAcquired,
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

    private function applyVerifiedBeforeQueryCallbacks(QueryBuilder $query): void
    {
        $callbacks = $query->beforeQueryCallbacks;
        $query->beforeQueryCallbacks = [];

        foreach ($callbacks as $callback) {
            if (! is_callable($callback)) {
                abort(422, 'The table mutation query contains an invalid callback.');
            }

            $connection = $query->getConnection();
            $grammar = $query->getGrammar();
            $processor = $query->getProcessor();
            $mandatoryConstraints = $this->mutationConstraintSnapshot($query);
            $mandatoryHavings = $query->havings ?? [];
            $mandatoryHavingBindings = $query->bindings['having'];
            $mandatoryWheres = $query->wheres;
            $mandatoryWhereBindings = $query->bindings['where'];

            $callback($query);

            if (
                $query->getConnection() !== $connection
                || $query->getGrammar() !== $grammar
                || $query->getProcessor() !== $processor
                || $query->beforeQueryCallbacks !== []
            ) {
                abort(422, 'The table mutation query callback changed its trusted query context.');
            }

            $this->assertMandatoryConstraintsPreserved($query, $mandatoryConstraints);
            $this->sealCallbackConstraints(
                $query,
                $mandatoryWheres,
                $mandatoryWhereBindings,
                $mandatoryHavings,
                $mandatoryHavingBindings,
            );
            $this->assertMandatoryConstraintsPreserved($query, $mandatoryConstraints);
        }
    }

    /**
     * @param  list<mixed>  $constraints
     * @param  list<mixed>  $bindings
     */
    private function assertCallbackBindingParity(
        array $constraints,
        array $bindings,
        bool $having,
    ): void {
        $expectedBindings = $this->callbackConstraintBindingCount($constraints, $having);

        if ($expectedBindings !== count($bindings)) {
            abort(422, 'The table mutation query callback added ambiguous bindings.');
        }
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
     * @param  array{
     *     fixed: mixed,
     *     wheres: list<mixed>,
     *     where_bindings: list<mixed>,
     *     havings: list<mixed>,
     *     having_bindings: list<mixed>
     * }  $mandatoryConstraints
     */
    private function assertMandatoryConstraintsPreserved(
        QueryBuilder $query,
        array $mandatoryConstraints,
    ): void {
        $currentConstraints = $this->mutationConstraintSnapshot($query);

        if (
            $currentConstraints['fixed'] !== $mandatoryConstraints['fixed']
            || ! $this->hasConstraintPrefix($currentConstraints['wheres'], $mandatoryConstraints['wheres'])
            || ! $this->hasConstraintPrefix(
                $currentConstraints['where_bindings'],
                $mandatoryConstraints['where_bindings'],
            )
            || ! $this->hasConstraintPrefix($currentConstraints['havings'], $mandatoryConstraints['havings'])
            || ! $this->hasConstraintPrefix(
                $currentConstraints['having_bindings'],
                $mandatoryConstraints['having_bindings'],
            )
        ) {
            abort(422, 'The table mutation query callback removed a mandatory scope constraint.');
        }
    }

    /**
     * @param  list<mixed>  $constraints
     */
    private function assertSupportedCallbackConstraints(
        array $constraints,
        QueryBuilder $query,
        bool $having,
    ): void {
        $supportedTypes = $having
            ? self::SAFE_CALLBACK_HAVING_TYPES
            : self::SAFE_CALLBACK_WHERE_TYPES;

        foreach ($constraints as $constraint) {
            if (
                ! is_array($constraint)
                || ! is_string($constraint['type'] ?? null)
                || ! is_string($constraint['boolean'] ?? null)
                || ! in_array(strtolower($constraint['boolean']), ['and', 'or'], true)
                || ! in_array(strtolower($constraint['type']), $supportedTypes, true)
                || $this->containsCallbackSqlExpression($constraint)
            ) {
                abort(422, 'The table mutation query callback added an ambiguous scope constraint.');
            }

            if (array_key_exists('operator', $constraint)) {
                $operator = $constraint['operator'];
                $operators = array_map(
                    static fn (string $supportedOperator): string => strtolower($supportedOperator),
                    array_merge(
                        $query->operators,
                        $query->bitwiseOperators,
                        $query->getGrammar()->getOperators(),
                        $query->getGrammar()->getBitwiseOperators(),
                    ),
                );

                if (! is_string($operator) || ! in_array(strtolower($operator), $operators, true)) {
                    abort(422, 'The table mutation query callback added an ambiguous scope constraint.');
                }
            }

            $this->assertSupportedCallbackConstraintShape(
                $constraint,
                strtolower($constraint['type']),
                $having,
            );

            if (strtolower($constraint['type']) !== 'nested') {
                continue;
            }

            $nestedQuery = $constraint['query'] ?? null;

            if (
                ! $nestedQuery instanceof QueryBuilder
                || $nestedQuery instanceof JoinClause
                || $nestedQuery->getConnection() !== $query->getConnection()
                || $nestedQuery->getGrammar() !== $query->getGrammar()
                || $nestedQuery->getProcessor() !== $query->getProcessor()
                || $nestedQuery->beforeQueryCallbacks !== []
            ) {
                abort(422, 'The table mutation query callback added an ambiguous scope constraint.');
            }

            $this->assertSupportedCallbackConstraints(
                $having ? ($nestedQuery->havings ?? []) : $nestedQuery->wheres,
                $nestedQuery,
                $having,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $constraint
     */
    private function assertSupportedCallbackConstraintShape(
        array $constraint,
        string $type,
        bool $having,
    ): void {
        if ($type === 'nested') {
            return;
        }

        $columnTypes = $having
            ? ['basic', 'between', 'bitwise', 'notnull', 'null']
            : [
                'basic',
                'between',
                'betweencolumns',
                'bitwise',
                'date',
                'day',
                'in',
                'inraw',
                'like',
                'month',
                'notin',
                'notinraw',
                'notnull',
                'null',
                'nullsafeequals',
                'time',
                'year',
            ];

        if (in_array($type, $columnTypes, true) && ! is_string($constraint['column'] ?? null)) {
            abort(422, 'The table mutation query callback added an ambiguous scope constraint.');
        }

        if ($type === 'column' && (
            ! is_string($constraint['first'] ?? null)
            || ! is_string($constraint['second'] ?? null)
        )) {
            abort(422, 'The table mutation query callback added an ambiguous scope constraint.');
        }

        if (in_array($type, ['between', 'betweencolumns'], true)) {
            $values = $constraint['values'] ?? null;

            if (! is_array($values) || count($values) !== 2) {
                abort(422, 'The table mutation query callback added an ambiguous scope constraint.');
            }

            if ($type === 'betweencolumns' && ! collect($values)->every(is_string(...))) {
                abort(422, 'The table mutation query callback added an ambiguous scope constraint.');
            }
        }

        if (in_array($type, ['in', 'inraw', 'notin', 'notinraw'], true)) {
            $values = $constraint['values'] ?? null;

            if (! is_array($values) || collect($values)->contains(is_array(...))) {
                abort(422, 'The table mutation query callback added an ambiguous scope constraint.');
            }

            if (in_array($type, ['inraw', 'notinraw'], true) && ! collect($values)->every(is_int(...))) {
                abort(422, 'The table mutation query callback added an ambiguous scope constraint.');
            }
        }

        if ($type === 'rowvalues') {
            $columns = $constraint['columns'] ?? null;
            $values = $constraint['values'] ?? null;

            if (
                ! is_array($columns)
                || ! is_array($values)
                || count($columns) !== count($values)
                || ! collect($columns)->every(is_string(...))
            ) {
                abort(422, 'The table mutation query callback added an ambiguous scope constraint.');
            }
        }

        if ($type === 'valuebetween') {
            $columns = $constraint['columns'] ?? null;

            if (
                ! is_array($columns)
                || count($columns) !== 2
                || ! collect($columns)->every(is_string(...))
            ) {
                abort(422, 'The table mutation query callback added an ambiguous scope constraint.');
            }
        }

        if (in_array($type, ['basic', 'bitwise', 'date', 'day', 'month', 'time', 'year'], true)) {
            if (! array_key_exists('value', $constraint) || ! is_string($constraint['operator'] ?? null)) {
                abort(422, 'The table mutation query callback added an ambiguous scope constraint.');
            }
        }

        if (in_array($type, ['like', 'nullsafeequals'], true) && ! array_key_exists('value', $constraint)) {
            abort(422, 'The table mutation query callback added an ambiguous scope constraint.');
        }
    }

    /**
     * Resolve exact effective-scope membership, lock only trusted base rows,
     * then revalidate membership after the lock and before authorization. This
     * keeps PostgreSQL-prohibited DISTINCT/GROUP BY/HAVING/set shapes off the
     * FOR UPDATE statement while closing the stale pre-lock snapshot window.
     * MySQL/MariaDB use a shared locking recheck so repeatable-read transactions
     * observe the committed row version that the base-row lock waited for.
     *
     * @param  array<string, int|string>|null  $expectedKeys
     * @return Collection<int, Model&TableResource>
     */
    private function authoritativeRecords(
        Builder $scope,
        TableMutationModelDescriptor $modelDescriptor,
        ?array $expectedKeys,
        ?string $trashed,
        bool $lockForUpdate = false,
        ?Closure $markLockAcquired = null,
    ): Collection {
        $modelDescriptor->assertMatches($scope);
        $model = $modelDescriptor->model();
        $keyAlias = '__aura_mutation_key';
        $qualifiedKey = $modelDescriptor->table.'.'.$modelDescriptor->keyName;

        $effectiveQuery = $model->registerGlobalScopes(clone $scope);
        $effectiveQuery = $this->applyTrashedMode($effectiveQuery, $trashed);
        $effectiveQuery = $this->applyScopesOnce($effectiveQuery);
        $modelDescriptor->assertMatches($effectiveQuery);
        $eagerLoads = $effectiveQuery->getEagerLoads();
        $effectiveQuery->setEagerLoads([]);

        if ($effectiveQuery->getQuery()->aggregate !== null) {
            abort(422, 'Aggregate table mutation scopes cannot identify authoritative records.');
        }

        $effectiveQuery->select($qualifiedKey.' as '.$keyAlias);
        $effectiveQuery->reorder($qualifiedKey);

        $effectiveBaseQuery = $effectiveQuery->getQuery();
        $this->applyVerifiedBeforeQueryCallbacks($effectiveBaseQuery);
        $modelDescriptor->assertMatches($effectiveQuery);
        $candidateRows = $effectiveBaseQuery->getConnection()->select(
            $effectiveBaseQuery->toSql(),
            $effectiveBaseQuery->getBindings(),
            ! $effectiveBaseQuery->useWritePdo,
        );
        $candidateKeys = $this->mutationKeysFromRows(
            $candidateRows,
            $keyAlias,
            $modelDescriptor,
            $expectedKeys,
        );

        if ($candidateKeys === []) {
            return $model->newCollection();
        }

        $candidateIds = array_values($candidateKeys);
        $lockQuery = $effectiveBaseQuery->getConnection()
            ->table($modelDescriptor->table)
            ->select($modelDescriptor->table.'.*')
            ->whereIn($qualifiedKey, $candidateIds)
            ->orderBy($qualifiedKey);

        if ($lockForUpdate) {
            $lockQuery->lockForUpdate();
        }

        $lockedRows = $lockQuery->get();

        $markLockAcquired?->__invoke();

        $revalidationQuery = clone $effectiveBaseQuery;
        $revalidationQuery->whereIn($qualifiedKey, $candidateIds);
        $revalidationGrammar = $revalidationQuery->getGrammar();

        if ($revalidationGrammar instanceof MariaDbGrammar) {
            $revalidationQuery->lock('lock in share mode');
        } elseif ($revalidationGrammar instanceof MySqlGrammar) {
            $revalidationQuery->lock('for share');
        }

        $revalidatedRows = $revalidationQuery->getConnection()->select(
            $revalidationQuery->toSql(),
            $revalidationQuery->getBindings(),
            ! $revalidationQuery->useWritePdo,
        );
        $revalidatedKeys = $this->mutationKeysFromRows(
            $revalidatedRows,
            $keyAlias,
            $modelDescriptor,
            $expectedKeys,
        );

        if ($expectedKeys === null && array_keys($candidateKeys) !== array_keys($revalidatedKeys)) {
            throw ValidationException::withMessages([
                'selected' => 'The selected records are invalid.',
            ]);
        }

        $lockedByIdentity = [];

        foreach ($lockedRows as $row) {
            if (! is_object($row)) {
                abort(422, 'The authoritative table mutation query returned an invalid record.');
            }

            $record = $modelDescriptor->hydrate((array) $row);
            $identity = $modelDescriptor->canonicalIdentity($record->getKey());

            if (! array_key_exists($identity, $candidateKeys)) {
                abort(422, 'The authoritative table mutation query returned an invalid record.');
            }

            $lockedByIdentity[$identity] = $record;
        }

        $hydratedRecords = [];

        foreach ($revalidatedKeys as $identity => $id) {
            if (! array_key_exists($identity, $lockedByIdentity)) {
                abort(422, 'The authoritative table mutation query returned an invalid record.');
            }

            $hydratedRecords[] = $lockedByIdentity[$identity];
        }

        $effectiveQuery->setEagerLoads($eagerLoads);
        $hydratedRecords = $effectiveQuery->eagerLoadRelations($hydratedRecords);
        $records = $model->newCollection($hydratedRecords);

        $records->each(function (Model $record) use ($modelDescriptor): void {
            $modelDescriptor->assertModelMatches($record);
        });

        return $this->canonicalizeRecords($records, $modelDescriptor);
    }

    /**
     * @param  list<mixed>  $constraints
     */
    private function callbackConstraintBindingCount(array $constraints, bool $having): int
    {
        $count = 0;

        foreach ($constraints as $constraint) {
            if (! is_array($constraint) || ! is_string($constraint['type'] ?? null)) {
                abort(422, 'The table mutation query callback added ambiguous bindings.');
            }

            $type = strtolower($constraint['type']);

            if ($type === 'nested') {
                $childQuery = $constraint['query'] ?? null;

                if (! $childQuery instanceof QueryBuilder) {
                    abort(422, 'The table mutation query callback added ambiguous bindings.');
                }

                $count += $this->callbackConstraintBindingCount(
                    $having ? ($childQuery->havings ?? []) : $childQuery->wheres,
                    $having,
                );

                continue;
            }

            $count += match ($type) {
                'basic',
                'bitwise',
                'date',
                'day',
                'like',
                'month',
                'nullsafeequals',
                'time',
                'valuebetween',
                'year' => 1,
                'between' => 2,
                'in',
                'notin',
                'rowvalues' => count($constraint['values']),
                'betweencolumns',
                'column',
                'inraw',
                'notinraw',
                'notnull',
                'null' => 0,
                default => abort(422, 'The table mutation query callback added ambiguous bindings.'),
            };
        }

        return $count;
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

    private function containsCallbackSqlExpression(mixed $value): bool
    {
        if ($value instanceof ExpressionContract) {
            return true;
        }

        if ($value instanceof QueryBuilder || ! is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if ($this->containsCallbackSqlExpression($item)) {
                return true;
            }
        }

        return false;
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
     * @param  list<mixed>  $constraints
     * @param  list<mixed>  $mandatoryConstraints
     */
    private function hasConstraintPrefix(array $constraints, array $mandatoryConstraints): bool
    {
        return array_slice($constraints, 0, count($mandatoryConstraints)) === $mandatoryConstraints;
    }

    /**
     * @return array{
     *     fixed: mixed,
     *     wheres: list<mixed>,
     *     where_bindings: list<mixed>,
     *     havings: list<mixed>,
     *     having_bindings: list<mixed>
     * }
     */
    private function mutationConstraintSnapshot(QueryBuilder $query): array
    {
        $whereBindings = $query->bindings['where'];
        $havingBindings = $query->bindings['having'];

        if (
            ! array_is_list($query->wheres)
            || ! array_is_list($whereBindings)
            || ($query->havings !== null && ! array_is_list($query->havings))
            || ! array_is_list($havingBindings)
        ) {
            abort(422, 'The table mutation query contains ambiguous constraints.');
        }

        return [
            'fixed' => $this->normalizeMutationConstraint([
                'aggregate' => $query->aggregate,
                'distinct' => $query->distinct,
                'from' => $query->from,
                'index_hint' => $query->indexHint,
                'joins' => $query->joins,
                'groups' => $query->groups,
                'orders' => $query->orders,
                'limit' => $query->limit,
                'group_limit' => $query->groupLimit,
                'offset' => $query->offset,
                'unions' => $query->unions,
                'union_limit' => $query->unionLimit,
                'union_offset' => $query->unionOffset,
                'union_orders' => $query->unionOrders,
                'bindings' => [
                    'from' => $query->bindings['from'],
                    'join' => $query->bindings['join'],
                    'group_by' => $query->bindings['groupBy'],
                    'order' => $query->bindings['order'],
                    'union' => $query->bindings['union'],
                    'union_order' => $query->bindings['unionOrder'],
                ],
            ], $query),
            'wheres' => $this->normalizeMutationConstraintList($query->wheres, $query),
            'where_bindings' => $this->normalizeMutationConstraintList($whereBindings, $query),
            'havings' => $this->normalizeMutationConstraintList($query->havings ?? [], $query),
            'having_bindings' => $this->normalizeMutationConstraintList($havingBindings, $query),
        ];
    }

    /**
     * @param  array<int, object>  $rows
     * @param  array<string, int|string>|null  $expectedKeys
     * @return array<string, int|string>
     */
    private function mutationKeysFromRows(
        array $rows,
        string $keyAlias,
        TableMutationModelDescriptor $modelDescriptor,
        ?array $expectedKeys,
    ): array {
        $keys = [];

        foreach ($rows as $row) {
            if (! is_object($row) || ! property_exists($row, $keyAlias)) {
                abort(422, 'The authoritative table mutation query returned an invalid identifier.');
            }

            $id = $row->{$keyAlias};

            if (! is_int($id) && ! is_string($id)) {
                abort(422, 'The authoritative table mutation query returned an invalid identifier.');
            }

            $identity = $modelDescriptor->canonicalIdentity($id);

            if ($expectedKeys !== null && ! array_key_exists($identity, $expectedKeys)) {
                abort(422, 'The authoritative table mutation query returned an invalid record.');
            }

            $keys[$identity] = $id;
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

    private function normalizeMutationConstraint(mixed $value, QueryBuilder $query): mixed
    {
        if ($value instanceof JoinClause) {
            return [
                'class' => $value::class,
                'type' => $this->normalizeMutationConstraint($value->type, $value),
                'table' => $this->normalizeMutationConstraint($value->table, $value),
                'query' => $this->mutationConstraintSnapshot($value),
            ];
        }

        if ($value instanceof Builder) {
            return [
                'class' => $value::class,
                'model' => $value->getModel()::class,
                'query' => $this->mutationConstraintSnapshot($value->getQuery()),
            ];
        }

        if ($value instanceof QueryBuilder) {
            return [
                'class' => $value::class,
                'query' => $this->mutationConstraintSnapshot($value),
            ];
        }

        if ($value instanceof ExpressionContract) {
            return [
                'expression' => $this->normalizeMutationConstraint(
                    $value->getValue($query->getGrammar()),
                    $query,
                ),
            ];
        }

        if ($value instanceof DateTimeInterface) {
            return [
                'date' => $value->format('Y-m-d H:i:s.uP'),
                'timezone' => $value->getTimezone()->getName(),
            ];
        }

        if ($value instanceof BackedEnum) {
            return [
                'enum' => $value::class,
                'value' => $value->value,
            ];
        }

        if ($value instanceof UnitEnum) {
            return [
                'enum' => $value::class,
                'name' => $value->name,
            ];
        }

        if ($value instanceof Stringable) {
            return [
                'stringable' => $value::class,
                'value' => (string) $value,
            ];
        }

        if (is_array($value)) {
            $normalized = [];

            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeMutationConstraint($item, $query);
            }

            return $normalized;
        }

        if (is_object($value) || is_resource($value)) {
            abort(422, 'The table mutation query contains an ambiguous scope constraint.');
        }

        return $value;
    }

    /**
     * @param  array<mixed>  $values
     * @return list<mixed>
     */
    private function normalizeMutationConstraintList(array $values, QueryBuilder $query): array
    {
        return array_values(array_map(
            fn (mixed $value): mixed => $this->normalizeMutationConstraint($value, $query),
            $values,
        ));
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
        ?Closure $markLockAcquired = null,
    ): Collection {
        if ($selectAll) {
            $records = $this->authoritativeRecords(
                $scope,
                $modelDescriptor,
                null,
                $trashed,
                lockForUpdate: true,
                markLockAcquired: $markLockAcquired,
            );

            if ($records->isEmpty()) {
                throw ValidationException::withMessages([
                    'selected' => 'Select at least one record.',
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

        $expectedKeys = collect($normalized)
            ->mapWithKeys(
                fn (int|string $id): array => [$modelDescriptor->canonicalIdentity($id) => $id]
            )
            ->all();

        $records = $this->authoritativeRecords(
            (clone $scope)->whereKey(array_values($normalized)),
            $modelDescriptor,
            $expectedKeys,
            $trashed,
            lockForUpdate: true,
            markLockAcquired: $markLockAcquired,
        );
        $resolvedIdentities = $this->canonicalIdentities($records, $modelDescriptor);

        if (
            array_diff_key($expectedKeys, $resolvedIdentities) !== []
            || array_diff_key($resolvedIdentities, $expectedKeys) !== []
        ) {
            throw ValidationException::withMessages([
                'selected' => 'The selected records are invalid.',
            ]);
        }

        return $records;
    }

    /**
     * @param  list<mixed>  $mandatoryWheres
     * @param  list<mixed>  $mandatoryWhereBindings
     * @param  list<mixed>  $mandatoryHavings
     * @param  list<mixed>  $mandatoryHavingBindings
     */
    private function sealCallbackConstraints(
        QueryBuilder $query,
        array $mandatoryWheres,
        array $mandatoryWhereBindings,
        array $mandatoryHavings,
        array $mandatoryHavingBindings,
    ): void {
        $appendedWheres = array_slice($query->wheres, count($mandatoryWheres));
        $appendedWhereBindings = array_slice(
            $query->bindings['where'],
            count($mandatoryWhereBindings),
        );
        $appendedHavings = array_slice($query->havings ?? [], count($mandatoryHavings));
        $appendedHavingBindings = array_slice(
            $query->bindings['having'],
            count($mandatoryHavingBindings),
        );

        if (
            ($appendedWheres === [] && $appendedWhereBindings !== [])
            || ($appendedHavings === [] && $appendedHavingBindings !== [])
        ) {
            abort(422, 'The table mutation query callback added ambiguous bindings.');
        }

        $this->assertSupportedCallbackConstraints($appendedWheres, $query, having: false);
        $this->assertSupportedCallbackConstraints($appendedHavings, $query, having: true);
        $this->assertCallbackBindingParity($appendedWheres, $appendedWhereBindings, having: false);
        $this->assertCallbackBindingParity($appendedHavings, $appendedHavingBindings, having: true);

        $query->wheres = $mandatoryWheres;
        $query->bindings['where'] = $mandatoryWhereBindings;
        $query->havings = $mandatoryHavings === [] ? null : $mandatoryHavings;
        $query->bindings['having'] = $mandatoryHavingBindings;

        if ($appendedWheres !== []) {
            $nestedWhere = $query->forNestedWhere();
            $nestedWhere->wheres = $appendedWheres;
            $nestedWhere->bindings['where'] = $appendedWhereBindings;

            $query->addNestedWhereQuery($nestedWhere, 'and');
        }

        if ($appendedHavings !== []) {
            $nestedHaving = $query->forNestedWhere();
            $nestedHaving->havings = $appendedHavings;
            $nestedHaving->bindings['having'] = $appendedHavingBindings;

            $query->addNestedHavingQuery($nestedHaving, 'and');
        }
    }

    /**
     * @param  Closure(Closure(): void): mixed  $callback
     */
    private function transactionWithPreLockRetries(
        ConnectionInterface $connection,
        Closure $callback,
    ): mixed {
        $maximumAttempts = $connection->transactionLevel() === 0
            ? self::DEADLOCK_RETRY_ATTEMPTS
            : 1;
        $attempt = 0;

        while (true) {
            $attempt++;
            $lockAcquired = false;

            try {
                return $connection->transaction(function () use ($callback, &$lockAcquired): mixed {
                    $markLockAcquired = static function () use (&$lockAcquired): void {
                        $lockAcquired = true;
                    };

                    return $callback($markLockAcquired);
                }, 1);
            } catch (Throwable $exception) {
                if (
                    $lockAcquired
                    || $attempt >= $maximumAttempts
                    || ! $this->causedByConcurrencyError($exception)
                ) {
                    throw $exception;
                }
            }
        }
    }
}
