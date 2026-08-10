<?php

namespace Aura\Base\Livewire\Table\Traits;

use Aura\Base\Fields\Number;
use Aura\Base\Support\ExactDecimal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Trait to handle sorting functionality.
 */
trait Sorting
{
    /**
     * Collection of sort field and direction.
     *
     * @var array
     */
    public $sorts = [];

    /**
     * Sort by the specified field.
     *
     * @param  string  $field
     * @return void
     */
    public function sortBy($field)
    {
        $this->sorts = collect($this->sorts)->filter(function ($value, $key) use ($field) {
            return $key === $field;
        })->toArray();

        if (! isset($this->sorts[$field])) {
            $this->sorts[$field] = 'asc';

            return;
        }

        if ($this->sorts[$field] === 'asc') {
            $this->sorts[$field] = 'desc';

            return;
        }

        unset($this->sorts[$field]);
    }

    /**
     * Apply sorting to the query.
     *
     * @param  Builder  $query
     * @return Builder
     */
    protected function applySorting($query)
    {
        if ($this->sorts) {
            $query->getQuery()->orders = null;
        }

        foreach ($this->sorts as $field => $direction) {
            // Normalize direction to a strict allow-list: it is interpolated into
            // orderByRaw() below and $this->sorts is client-controllable.
            $direction = strtolower((string) $direction) === 'desc' ? 'desc' : 'asc';

            $qualifiedKeyName = $this->model->getQualifiedKeyName();
            $table = $this->model->getTable();

            // We want to add custom Sorting. If the model has a custom sorting method, we want to use that instead of the default one. Name of the method is sort_{$field}
            if (method_exists($this->model, 'sort_'.$field)) {
                $this->model->{'sort_'.$field}($query, $direction);

                return $query;
            }

            if ($this->model->isTaxonomyField($field)) {
                $resourceType = $this->model->fieldBySlug($field)['resource'];

                $query->leftJoin('post_relations as pr', function ($join) use ($qualifiedKeyName, $resourceType) {
                    $join->on($qualifiedKeyName, '=', 'pr.related_id')
                        ->where('pr.related_type', '=', $this->model->getMorphClass())
                        ->where('pr.resource_type', '=', $resourceType)
                        ->where('pr.slug', '=', Str::plural(Str::lower(class_basename($resourceType))));
                })
                    ->select($table.'.*')
                    ->groupBy($qualifiedKeyName)
                    ->orderByRaw('MIN(pr.resource_id) '.$direction)
                    ->orderBy($qualifiedKeyName, 'desc');

                return $query;
            }

            if ($this->model->usesMeta() && $this->model->isMetaField($field)) {
                $query->leftJoin('meta', function ($join) use ($field, $qualifiedKeyName) {
                    $join->on($qualifiedKeyName, '=', 'meta.metable_id')
                        ->where('meta.metable_type', '=', $this->model->getMorphClass())
                        ->where('meta.key', '=', "$field");
                })
                    ->select($table.'.*')
                    ->when($this->model->isNumberField($field), function ($query) use ($direction, $field) {
                        $connection = DB::connection($this->model->getConnectionName());

                        if (! ExactDecimal::supportsSql($connection)) {
                            $query->orderByRaw('CAST(meta.value AS DECIMAL(65,30)) '.$direction);

                            return;
                        }

                        $column = $query->getQuery()->getGrammar()->wrap('meta.value');
                        $fieldDefinition = $this->model->fieldBySlug($field);
                        $fieldClass = $this->model->fieldClassBySlug($field);

                        ExactDecimal::applySorting(
                            $query,
                            $connection,
                            $column,
                            $direction,
                            $fieldClass instanceof Number
                                ? $fieldClass->exactQueryConfiguration(is_array($fieldDefinition) ? $fieldDefinition : [])
                                : null,
                        );
                    })
                    ->when(! $this->model->isNumberField($field), function ($query) use ($direction) {
                        $query->orderByRaw('CAST(meta.value AS CHAR) '.$direction);
                    })
                    ->orderBy($qualifiedKeyName, 'desc');

                return $query;
            } else {
                if ($this->model->isNumberField($field) && DB::connection($this->model->getConnectionName())->getDriverName() === 'sqlite') {
                    $connection = DB::connection($this->model->getConnectionName());
                    $column = $query->getQuery()->getGrammar()->wrap($field);
                    $fieldDefinition = $this->model->fieldBySlug($field);
                    $fieldClass = $this->model->fieldClassBySlug($field);
                    ExactDecimal::applySorting(
                        $query,
                        $connection,
                        $column,
                        $direction,
                        $fieldClass instanceof Number
                            ? $fieldClass->exactQueryConfiguration(is_array($fieldDefinition) ? $fieldDefinition : [])
                            : null,
                    );
                    $query->orderBy($qualifiedKeyName, 'desc');

                    return $query;
                }

                $query->orderBy($field, $direction);

                if ($this->model->isNumberField($field)) {
                    $query->orderBy($qualifiedKeyName, 'desc');
                }

                return $query;
            }
        }

        $query->getQuery()->orders = null;

        // default sort
        $query->orderBy($this->model->getTable().'.'.$this->model->defaultTableSort(), $this->model->defaultTableSortDirection());

        return $query;
    }
}
