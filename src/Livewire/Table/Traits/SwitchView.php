<?php

namespace Aura\Base\Livewire\Table\Traits;

trait SwitchView
{
    public $currentView;

    public function mountSwitchView()
    {
        $userPreference = auth()->user()->getOption('table_view.'.$this->model()->getType());

        $this->currentView = $userPreference ?? $this->settings['default_view'];
    }

    public function switchView($view)
    {
        if (in_array($view, ['list', 'kanban', 'grid'])) {
            $this->currentView = $view;
            $this->saveViewPreference();
        }
    }

    protected function saveViewPreference()
    {
        auth()->user()->updateOption('table_view.'.$this->model()->getType(), $this->currentView);
    }
}
