<?php

namespace Aura\Base\Livewire\Table\Traits;

trait SwitchView
{
    public $currentView;

    public function mountSwitchView()
    {
        $userPreference = auth()->user()->getOption('table_view.'.$this->model()->getType());
        $defaultView = is_string($this->settings['default_view'] ?? null)
            ? $this->settings['default_view']
            : 'list';

        $this->currentView = $this->supportedView($userPreference)
            ?? $this->supportedView($defaultView)
            ?? 'list';
    }

    public function switchView($view)
    {
        $view = $this->supportedView($view);

        if ($view === null || $view === $this->currentView) {
            return;
        }

        $this->currentView = $view;
        $this->prepareKanban();
        $this->saveViewPreference();
    }

    protected function saveViewPreference()
    {
        auth()->user()->updateOption('table_view.'.$this->model()->getType(), $this->currentView);
    }

    protected function supportedView(mixed $view): ?string
    {
        if (! is_string($view)) {
            return null;
        }

        $views = ['list'];

        if (is_string($this->settings['views']['grid'] ?? null) && $this->settings['views']['grid'] !== '') {
            $views[] = 'grid';
        }

        if (
            ($this->resolvedKanbanConfiguration()['enabled'] ?? false)
            && is_string($this->settings['views']['kanban'] ?? null)
            && $this->settings['views']['kanban'] !== ''
        ) {
            $views[] = 'kanban';
        }

        return in_array($view, $views, true) ? $view : null;
    }
}
