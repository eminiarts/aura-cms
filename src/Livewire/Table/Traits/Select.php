<?php

namespace Aura\Base\Livewire\Table\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait for bulk actions in Livewire table component
 */
trait Select
{
    /**
     * Indicates if all rows should be selected
     *
     * @var bool
     */
    public $selectAll = false;

    /**
     * Rows explicitly removed from the current select-all scope.
     *
     * @var array<int, int|string>
     */
    public array $selectAllExclusions = [];

    /**
     * Array of selected row IDs
     *
     * @var array
     */
    public $selected = [];

    /**
     * Indicates if all rows in the current page should be selected
     *
     * @var bool
     */
    public $selectPage = false;

    /**
     * @return array{selected: array<int, int|string>, selectAll: bool, exclusions: array<int, int|string>}
     */
    public function clearSelection(): array
    {
        $this->resetSelectionForScopeChange();

        return $this->selectionState();
    }

    /**
     * Gets a query for selected rows
     *
     * @return Builder
     */
    public function getSelectedRowsQueryProperty()
    {
        return (clone $this->query())
            ->unless($this->selectAll, fn ($query) => $query->whereKey($this->selected));
    }

    public function resetSelectionForScopeChange(): void
    {
        $this->selected = [];
        $this->selectAll = false;
        $this->selectAllExclusions = [];
        $this->selectPage = false;
    }

    // /**
    //  * Handles selecting all or page rows
    //  *
    //  * @return void
    //  */
    // public function renderingWithBulkActions()
    // {
    //     if ($this->selectAll) {
    //         $this->selectPageRows();
    //     }
    // }

    /**
     * Selects all rows
     *
     * @return array{selected: array<int, int|string>, selectAll: bool, exclusions: array<int, int|string>}
     */
    public function selectAll(): array
    {
        return $this->selectAllRows();
    }

    /**
     * Enter select-all mode without materializing every identifier in the browser.
     *
     * @return array{selected: array<int, int|string>, selectAll: bool, exclusions: array<int, int|string>}
     */
    public function selectAllRows(): array
    {
        if ($this->field) {
            abort(422, 'Select all is unavailable for field selections.');
        }

        $this->selected = [];
        $this->selectAll = true;
        $this->selectAllExclusions = [];
        $this->selectPage = true;

        return $this->selectionState();
    }

    /**
     * Selects all rows in the current page
     */
    public function selectPageRows(): void
    {
        $this->selected = collect($this->selected)
            ->merge($this->rows()->pluck('id')->map(fn ($id) => (string) $id))
            ->unique()
            ->values()
            ->all();
    }

    // when page is updated, reset selectPage
    public function updatedPage()
    {
        $this->selectPage = false;
    }

    public function updatedSelectAll(bool $value): void
    {
        if (! $value) {
            $this->selectAllExclusions = [];
        }
    }

    /**
     * Handles updates to selected rows
     *
     * @return void
     */
    public function updatedSelected()
    {
        $this->selectAll = false;
        $this->selectAllExclusions = [];
        $this->selectPage = false;
    }

    /**
     * Handles updates to selecting all rows in the current page
     *
     * @param  bool  $value
     * @return void
     */
    public function updatedSelectPage($value)
    {
        if ($value) {
            return $this->selectPageRows();
        }

        $this->selectAll = false;
        $this->selectAllExclusions = [];
        $this->selected = [];
    }

    /**
     * Update only identifiers rendered on the current effective page.
     *
     * @param  array<int, mixed>  $ids
     * @return array{selected: array<int, int|string>, selectAll: bool, exclusions: array<int, int|string>}
     */
    public function updateRowSelection(array $ids, bool $selected): array
    {
        if ($ids === []) {
            return $this->selectionState();
        }

        $visibleIds = collect($this->rowIds())
            ->filter(fn (mixed $id): bool => is_int($id) || (is_string($id) && $id !== ''))
            ->mapWithKeys(fn (int|string $id): array => [(string) $id => $id]);
        $requested = [];

        foreach ($ids as $id) {
            if ((! is_int($id) && ! is_string($id)) || ! $visibleIds->has((string) $id)) {
                abort(422, 'The selected table rows are invalid.');
            }

            $requested[(string) $id] = $visibleIds->get((string) $id);
        }

        if ($this->selectAll) {
            $exclusions = collect($this->selectAllExclusions)
                ->mapWithKeys(fn (int|string $id): array => [(string) $id => $id]);

            foreach ($requested as $identity => $id) {
                if ($selected) {
                    $exclusions->forget($identity);
                } else {
                    $exclusions->put($identity, $id);
                }
            }

            $this->selectAllExclusions = $exclusions->values()->all();
        } else {
            $selection = collect($this->selected)
                ->mapWithKeys(fn (int|string $id): array => [(string) $id => $id]);
            $configuredMaximum = is_array($this->field)
                ? ($this->field['max_files'] ?? $this->field['max'] ?? 0)
                : 0;
            $maximum = is_numeric($configuredMaximum) ? max(0, (int) $configuredMaximum) : 0;

            if ($selected && $maximum === 1) {
                $selection = collect();
            }

            foreach ($requested as $identity => $id) {
                if ($selected) {
                    $selection->put($identity, $id);
                } else {
                    $selection->forget($identity);
                }
            }

            if ($maximum > 0 && $selection->count() > $maximum) {
                $selection = $selection->take($maximum);
                $this->dispatch(
                    'notify',
                    message: 'You can only select '.$maximum.' items.',
                    type: 'error',
                );
            }

            $this->selected = $selection->values()->all();
        }

        $this->selectPage = collect($this->rowIds())->every(function (mixed $id): bool {
            if ($this->selectAll) {
                return ! collect($this->selectAllExclusions)->contains(
                    fn (mixed $excluded): bool => (string) $excluded === (string) $id,
                );
            }

            return collect($this->selected)->contains(
                fn (mixed $selectedId): bool => (string) $selectedId === (string) $id,
            );
        });

        return $this->selectionState();
    }

    /**
     * @return array{selected: array<int, int|string>, selectAll: bool, exclusions: array<int, int|string>}
     */
    private function selectionState(): array
    {
        return [
            'selected' => array_values($this->selected),
            'selectAll' => (bool) $this->selectAll,
            'exclusions' => array_values($this->selectAllExclusions),
        ];
    }
}
