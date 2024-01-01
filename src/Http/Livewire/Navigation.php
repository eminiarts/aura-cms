<?php

namespace Eminiarts\Aura\Http\Livewire;

use Livewire\Component;
use Eminiarts\Aura\Facades\Aura;

class Navigation extends Component
{
    public $toggledGroups = [];
    public $sidebarType;
    public $iconClass;

    public function isToggled($group)
    {
        return ! in_array($group, $this->toggledGroups);
    }

    public function mount($query = null)
    {
        $this->emit('NavigationMounted');

        if (auth()->check() && auth()->user()->getOptionSidebar()) {
            $this->toggledGroups = auth()->user()->getOptionSidebar();
        } else {
            $this->toggledGroups = [];
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

    public function getSidebarType()
    {
        $settings = Aura::getOption('team-settings');

        if ($settings && isset($settings['sidebar-type'])) {
            return $settings['sidebar-type'];
        }

        return 'primary';
    }

    public function getIconClass($sidebarType)
    {
        switch ($sidebarType) {
            case 'primary':
                return 'group-[.is-active]:text-white text-sidebar-icon dark:text-primary-500 group-hover:text-sidebar-icon-hover dark:group-hover:text-primary-500';
            case 'light':
                return 'group-[.is-active]:text-primary-500 text-primary-500 dark:text-primary-500 group-hover:text-primary-500';
            case 'dark':
                return 'group-[.is-active]:text-primary-500 text-primary-500';
            default:
                return 'group-[.is-active]:text-white text-sidebar-icon dark:text-primary-500 group-hover:text-sidebar-icon-hover dark:group-hover:text-primary-500';
        }
    }
}
