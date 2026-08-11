<?php

namespace Aura\Base\Livewire\Table\Traits;

use Aura\Base\Models\SavedView;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\SavedViews\SavedViewState;
use Aura\Base\SavedViews\SavedViewVisibility;
use Aura\Base\Services\SavedViewManager;
use Aura\Base\Table\TableQueryState;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

trait SavedViews
{
    #[Locked]
    public int|string|null $savedViewId = null;

    public string $savedViewName = '';

    public bool $savedViewShared = false;

    public function applySavedView(int|string $id, SavedViewManager $manager): void
    {
        $savedView = $manager->resolve($id, $this->model(), $this->savedViewUser(), $this->savedViewTeam());
        $this->applyValidatedSavedView($savedView, $manager);
    }

    public function deleteSavedView(SavedViewManager $manager): void
    {
        $savedView = $this->selectedSavedView($manager);
        $manager->delete($savedView, $this->model(), $this->savedViewUser(), $this->savedViewTeam());
        $this->savedViewId = null;
        $this->savedViewName = '';
        unset($this->savedViews);
    }

    public function duplicateSavedView(SavedViewManager $manager): void
    {
        $name = Validator::make(['name' => $this->savedViewName], [
            'name' => ['required', 'string', 'max:120'],
        ])->validate()['name'];
        $duplicate = $manager->duplicate(
            $this->selectedSavedView($manager),
            $this->model(),
            $this->savedViewUser(),
            $this->savedViewTeam(),
            $name,
        );
        $this->savedViewId = $duplicate->getKey();
        $this->savedViewName = $duplicate->name;
        unset($this->savedViews);
    }

    public function initializeSavedViews(SavedViewManager $manager): void
    {
        if (! $manager->available($this->model())) {
            return;
        }

        if ($this->savedViewId !== null) {
            $this->applySavedView($this->savedViewId, $manager);

            return;
        }

        $default = $manager->resolveDefault($this->model(), $this->savedViewUser(), $this->savedViewTeam());

        if ($default instanceof SavedView) {
            $this->applyValidatedSavedView($default, $manager);
        }
    }

    public function renameSavedView(SavedViewManager $manager): void
    {
        $name = Validator::make(['name' => $this->savedViewName], [
            'name' => ['required', 'string', 'max:120'],
        ])->validate()['name'];
        $savedView = $manager->rename(
            $this->selectedSavedView($manager),
            $this->model(),
            $this->savedViewUser(),
            $this->savedViewTeam(),
            $name,
        );
        $this->savedViewName = $savedView->name;
        unset($this->savedViews);
    }

    public function saveCurrentView(SavedViewManager $manager): void
    {
        $name = Validator::make(['name' => $this->savedViewName], [
            'name' => ['required', 'string', 'max:120'],
        ])->validate()['name'];
        $state = $this->currentSavedViewState();
        $savedView = $this->savedViewShared
            ? $manager->createShared($this->model(), $this->savedViewUser(), $this->savedViewTeam(), $name, $state)
            : $manager->createPrivate($this->model(), $this->savedViewUser(), $this->savedViewTeam(), $name, $state);

        $this->savedViewId = $savedView->getKey();
        $this->savedViewName = $savedView->name;
        unset($this->savedViews);
    }

    #[Computed]
    public function savedViews(): Collection
    {
        $manager = app(SavedViewManager::class);

        return $manager->available($this->model())
            ? $manager->list($this->model(), $this->savedViewUser(), $this->savedViewTeam())
            : new Collection;
    }

    #[Computed]
    public function savedViewsAvailable(): bool
    {
        return app(SavedViewManager::class)->available($this->model());
    }

    public function setSavedViewDefault(SavedViewManager $manager): void
    {
        $manager->setDefault(
            $this->selectedSavedView($manager),
            $this->model(),
            $this->savedViewUser(),
            $this->savedViewTeam(),
        );
    }

    private function applyValidatedSavedView(SavedView $savedView, SavedViewManager $manager): void
    {
        $state = $manager->validatedState($savedView, $this->model(), $this->savedViewUser(), $this->savedViewTeam());
        $headers = array_keys($this->model()->getTableHeaders()->all());

        $this->tableState = $state->query->toQueryString();
        $this->filters = ['custom' => $state->query->filters];
        $this->search = $state->query->search;
        $this->sorts = collect($state->query->sorts)->mapWithKeys(
            fn (array $sort): array => [$sort['key'] => $sort['direction']],
        )->all();
        $this->columns = collect($headers)->mapWithKeys(
            fn (string $key): array => [$key => in_array($key, $state->columns, true)],
        )->all();
        $this->currentView = $state->viewMode;
        $this->kanbanStatuses = $state->grouping === null
            ? []
            : collect($state->grouping['columns'])->mapWithKeys(function (array $column): array {
                $declared = $this->declaredKanbanStatuses()[$column['key']];

                return [$column['key'] => array_replace($declared, ['visible' => $column['visible']])];
            })->all();
        $this->savedViewId = $savedView->getKey();
        $this->savedViewName = $savedView->name;
        $this->savedViewShared = $savedView->visibility === SavedViewVisibility::Team;
        $this->resetSelectionForScopeChange();
        $this->resetPage();
    }

    /** @return array<string, mixed> */
    private function currentSavedViewState(): array
    {
        $query = $this->tableState !== ''
            ? TableQueryState::fromQueryString($this->tableState)
            : TableQueryState::fromLegacy($this->filters, $this->search, $this->sorts, $this->requiredParentScope);
        $columns = collect($this->columns)
            ->filter(fn (mixed $visible): bool => (bool) $visible)
            ->keys()
            ->map(fn (mixed $key): string => (string) $key)
            ->values()
            ->all();
        $grouping = null;

        if ($this->currentView === 'kanban') {
            $configuration = $this->resolvedKanbanConfiguration();
            $statuses = $this->sanitizeKanbanStatuses($this->kanbanStatuses);
            $grouping = [
                'key' => $configuration['group_field'],
                'columns' => collect($statuses)->map(
                    fn (array $status, string $key): array => ['key' => $key, 'visible' => (bool) $status['visible']]
                )->values()->all(),
            ];
        }

        return SavedViewState::fromArray([
            'v' => SavedViewState::VERSION,
            'query' => $query->toArray(),
            'columns' => $columns,
            'view_mode' => $this->currentView,
            'grouping' => $grouping,
        ], $this->model())->toArray();
    }

    private function savedViewTeam(): ?Team
    {
        if (! config('aura.teams')) {
            return null;
        }

        $team = $this->savedViewUser()->currentTeam;

        return $team instanceof Team ? $team : null;
    }

    private function savedViewUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function selectedSavedView(SavedViewManager $manager): SavedView
    {
        abort_if($this->savedViewId === null, 422, 'No saved view is selected.');

        return $manager->resolve(
            $this->savedViewId,
            $this->model(),
            $this->savedViewUser(),
            $this->savedViewTeam(),
        );
    }
}
