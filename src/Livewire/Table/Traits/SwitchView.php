<?php

namespace Aura\Base\Livewire\Table\Traits;

use Illuminate\Support\Str;
use Livewire\Attributes\On;

trait SwitchView
{
    public $currentView;
    
    protected function initializeView()
    {
        $userPreference = auth()->user()->getOption('table_view.' . $this->model()->getType());

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
        ray($this->currentView, $this->model()->getType());
        auth()->user()->updateOption('table_view.' . $this->model()->getType(), $this->currentView);
    }
    
}
