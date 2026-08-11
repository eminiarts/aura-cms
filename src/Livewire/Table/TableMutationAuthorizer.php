<?php

namespace Aura\Base\Livewire\Table;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Small auth contract for table row/bulk mutations.
 *
 * declare → ability → exact keys in table scope → per-record Gate
 *
 * Does not include query AST sealing, lock retries, or signed downloads.
 */
final class TableMutationAuthorizer
{
    private const DEFAULT_ABILITIES = [
        'delete' => 'delete',
        'deleteAttachment' => 'delete',
        'deleteSelected' => 'delete',
        'forceDelete' => 'forceDelete',
        'restore' => 'restore',
        'update' => 'update',
        'view' => 'view',
        'edit' => 'update',
    ];

    public function __construct(
        private readonly int $maxSelection = 500,
    ) {}

    /**
     * @param  array<string, mixed>  $declared
     */
    public function abilityFor(string $action, mixed $definition): string
    {
        if (is_array($definition) && array_key_exists('ability', $definition)) {
            $ability = $definition['ability'];

            if (! is_string($ability) || $ability === '') {
                throw ValidationException::withMessages([
                    'action' => 'Bulk action ability must be a non-empty string.',
                ]);
            }

            return $ability;
        }

        if (isset(self::DEFAULT_ABILITIES[$action])) {
            return self::DEFAULT_ABILITIES[$action];
        }

        // Fail closed: custom actions must declare an ability explicitly.
        throw ValidationException::withMessages([
            'action' => "Action [{$action}] must declare an ability.",
        ]);
    }

    /**
     * @param  array<string, mixed>  $declared
     * @return array<string, mixed>|string
     */
    public function assertDeclared(string $action, array $declared): mixed
    {
        if (! array_key_exists($action, $declared)) {
            throw new HttpException(403, 'This action is not allowed.');
        }

        return $declared[$action];
    }

    /**
     * @param  array<string, mixed>  $declared
     * @param  list<int|string>|null  $selected
     * @return Collection<int, Model>
     */
    public function authorizeBulk(
        Builder $scope,
        string $action,
        array $declared,
        ?array $selected,
        bool $selectAll = false,
    ): Collection {
        $definition = $this->assertDeclared($action, $declared);
        $ability = $this->abilityFor($action, $definition);
        $records = $this->resolveExactSelection($scope, $selected, $selectAll);
        $this->authorizeEach($records, $ability);

        return $records;
    }

    /**
     * @param  Collection<int, Model>  $records
     */
    public function authorizeEach(Collection $records, string $ability): void
    {
        foreach ($records as $record) {
            Gate::authorize($ability, $record);
        }
    }

    /**
     * @param  array<string, mixed>  $declared
     */
    public function authorizeRow(
        Builder $scope,
        int|string $id,
        string $action,
        array $declared,
    ): Model {
        $definition = $this->assertDeclared($action, $declared);
        $ability = $this->abilityFor($action, $definition);
        $records = $this->resolveExactSelection($scope, [$id], selectAll: false);
        $record = $records->first();

        if (! $record instanceof Model) {
            throw ValidationException::withMessages([
                'id' => 'The selected row is unavailable.',
            ]);
        }

        Gate::authorize($ability, $record);

        return $record;
    }

    /**
     * @param  list<int|string>|null  $selected
     * @return Collection<int, Model>
     */
    public function resolveExactSelection(Builder $scope, ?array $selected, bool $selectAll): Collection
    {
        if ($selectAll) {
            $records = (clone $scope)->limit($this->maxSelection + 1)->get();

            if ($records->count() > $this->maxSelection) {
                throw ValidationException::withMessages([
                    'selected' => "Selection exceeds the maximum of {$this->maxSelection} rows.",
                ]);
            }

            if ($records->isEmpty()) {
                throw ValidationException::withMessages([
                    'selected' => 'No rows are selected.',
                ]);
            }

            return $records;
        }

        $keys = $this->normalizeKeys($selected ?? []);

        if ($keys === []) {
            throw ValidationException::withMessages([
                'selected' => 'No rows are selected.',
            ]);
        }

        if (count($keys) > $this->maxSelection) {
            throw ValidationException::withMessages([
                'selected' => "Selection exceeds the maximum of {$this->maxSelection} rows.",
            ]);
        }

        $records = (clone $scope)->whereKey($keys)->get()
            ->keyBy(fn (Model $model): string => (string) $model->getKey());

        if ($records->count() !== count($keys)) {
            throw ValidationException::withMessages([
                'selected' => 'One or more selected rows are unavailable in the current table scope.',
            ]);
        }

        $ordered = new Collection;

        foreach ($keys as $key) {
            $record = $records->get($key);

            if (! $record instanceof Model) {
                throw ValidationException::withMessages([
                    'selected' => 'One or more selected rows are unavailable in the current table scope.',
                ]);
            }

            $ordered->push($record);
        }

        return $ordered;
    }

    /**
     * @param  list<int|string>  $keys
     * @return list<string>
     */
    private function normalizeKeys(array $keys): array
    {
        if (! array_is_list($keys)) {
            throw new InvalidArgumentException('Selected row IDs must be a list.');
        }

        $normalized = [];

        foreach ($keys as $key) {
            if ((! is_int($key) && ! is_string($key)) || (string) $key === '') {
                throw ValidationException::withMessages([
                    'selected' => 'Selected row IDs must be non-empty integers or strings.',
                ]);
            }

            $normalized[] = (string) $key;
        }

        $unique = array_values(array_unique($normalized, SORT_STRING));

        if (count($unique) !== count($normalized)) {
            throw ValidationException::withMessages([
                'selected' => 'Selected row IDs must not contain duplicates.',
            ]);
        }

        return $unique;
    }
}
