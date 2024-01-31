<?php

namespace Eminiarts\Aura\Livewire;

use Livewire\Component;
use Eminiarts\Aura\Facades\Aura;

class Navigation extends Component
{
    public $toggledGroups = [];
    public $sidebarType;
    public $iconClass;
    public $sidebarToggled;

    public function isToggled($group)
    {
        return ! in_array($group, $this->toggledGroups);
    }

    public function mount($query = null)
    {
        $this->dispatch('NavigationMounted');

        // ray('mount', auth()->user()->getOptionSidebarToggled());

        if (auth()->check() && auth()->user()->getOptionSidebar()) {
            $this->toggledGroups = auth()->user()->getOptionSidebar();
        } else {
            $this->toggledGroups = [];
            // $this->sidebarToggled = true;
        }

        if (auth()->check()) {
            $this->sidebarToggled = auth()->user()->getOptionSidebarToggled();
        }

        // ray('mount2', auth()->user()->getOptionSidebar(), $this->sidebarToggled);

        $this->sidebarType = $this->getSidebarType();
        $this->iconClass = $this->getIconClass($this->sidebarType);
    }

    public function render()
    {
        return view('aura::livewire.navigation');
    }

    public function toggleSidebar()
    {
        ray('toggleSidebar1', $this->sidebarToggled);

        $this->sidebarToggled = !$this->sidebarToggled;

        ray('toggleSidebar2', $this->sidebarToggled);

        auth()->user()->updateOption('sidebarToggled', $this->sidebarToggled);
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
