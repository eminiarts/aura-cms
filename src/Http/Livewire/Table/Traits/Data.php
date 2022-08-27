<?php

namespace Aura\Http\Livewire\Table\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Rappasoft\LaravelLivewireTables\Views\Column;

trait WithData
{
    // TODO: Test
    public function getRows()
    {
        $this->baseQuery();

        return $this->executeQuery();
    }

    protected function baseQuery(): Builder
    {
        $this->setBuilder($this->joinRelations());

        $this->setBuilder($this->selectFields());

        $this->setBuilder($this->applySearch());

        $this->setBuilder($this->applyFilters());

        // if ($this->currentlyReorderingIsEnabled()) {
        //     $this->setBuilder($this->getBuilder()->orderBy($this->getDefaultReorderColumn(), $this->getDefaultReorderDirection()));

        //     return $this->getBuilder();
        // }

        return $this->applySorting();
    }

    protected function executeQuery()
    {
        return $this->paginationIsEnabled() ?
            $this->getBuilder()->paginate($this->getPerPage() === -1 ? $this->getBuilder()->count() : $this->getPerPage(), ['*'], $this->getComputedPageName()) :
            $this->getBuilder()->get();
    }

    protected function joinRelations(): Builder
    {
        foreach ($this->getSelectableColumns() as $column) {
            if ($column->hasRelations()) {
                $this->setBuilder($this->joinRelation($column));
            }
        }

        return $this->getBuilder();
    }

    protected function getTableForColumn(Column $column): ?string
    {
        $table = null;
        $lastQuery = clone $this->getBuilder();

        foreach ($column->getRelations() as $relationPart) {
            $model = $lastQuery->getRelation($relationPart);

            if ($model instanceof HasOne || $model instanceof BelongsTo) {
                $table = $model->getRelated()->getTable();
            }

            $lastQuery = $model->getQuery();
        }

        return $table;
    }

    protected function getQuerySql(): string
    {
        return (clone $this->getBuilder())->toSql();
    }
}
