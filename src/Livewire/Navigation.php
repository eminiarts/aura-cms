<?php

namespace Aura\Base\Livewire;

use Livewire\Component;

class Navigation extends Component
{
    public $iconClass;

    public $sidebarToggled;

    public $sidebarType;

    public $toggledGroups = [];

    public function getIconClass($sidebarType)
    {
        return '';
    }

    public function getSidebarType()
    {
        $settings = app('aura')::getOption('team-settings');

        if ($settings && isset($settings['sidebar-type'])) {
            return $settings['sidebar-type'];
        }

        return 'primary';
    }

    public function isToggled($group)
    {
        return ! in_array($group, $this->toggledGroups);
    }

    public function mount($query = null)
    {
        $this->dispatch('NavigationMounted');

        if (auth()->check() && auth()->user()->getOptionSidebar()) {
            $this->toggledGroups = auth()->user()->getOptionSidebar();
        } else {
            $this->toggledGroups = [];
            // $this->sidebarToggled = true;
        }

        if (auth()->check()) {
            $this->sidebarToggled = auth()->user()->getOptionSidebarToggled();
        }

        $this->sidebarType = $this->getSidebarType();
        $this->iconClass = $this->getIconClass($this->sidebarType);
    }

    public function render()
    {
        return view('aura::livewire.navigation');
    }

    public function toggleGroup($group)
    {
        if (in_array($group, $this->toggledGroups)) {
            $this->toggledGroups = array_diff($this->toggledGroups, [$group]);
        } else {
            $this->toggledGroups[] = $group;
        }

        auth()->user()->updateOption('sidebar', $this->toggledGroups);
    }

    public function toggleSidebar()
    {
        $this->sidebarToggled = ! $this->sidebarToggled;

        auth()->user()->updateOption('sidebarToggled', $this->sidebarToggled);
    }
}
