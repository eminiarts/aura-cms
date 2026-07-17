<?php

namespace Aura\Base\Traits\Concerns;

trait AuraResourceNavigation
{
    public static $contextMenu = true;

    protected static $dropdown = false;

    protected static ?string $group = 'Resources';

    protected static ?string $icon = null;

    protected static bool $showInNavigation = true;

    protected static ?int $sort = 100;

    public function getBadge() {}

    public function getBadgeColor() {}

    public static function getContextMenu()
    {
        return static::$contextMenu;
    }

    public static function getDropdown()
    {
        return static::$dropdown;
    }

    public static function getGroup(): ?string
    {
        return static::$group;
    }

    public function getIcon()
    {
        return '<svg class="w-5 h-5" viewBox="0 0 18 18" fill="none" stroke="currentColor" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.75 9a6.75 6.75 0 1 1-13.5 0 6.75 6.75 0 0 1 13.5 0Z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    public static function getShowInNavigation(): bool
    {
        return static::$showInNavigation;
    }

    public static function getSort(): ?int
    {
        return static::$sort;
    }

    public function icon()
    {
        return $this->getIcon();
    }

    public function navigation()
    {
        return [
            'icon' => $this->icon(),
            'resource' => get_class($this),
            'type' => $this->getType(),
            'name' => $this->pluralName(),
            'slug' => $this->getSlug(),
            'sort' => $this->getSort(),
            'group' => $this->getGroup(),
            'route' => $this->getIndexRoute(),
            'dropdown' => $this->getDropdown(),
            'showInNavigation' => $this->getShowInNavigation(),
            'badge' => $this->getBadge(),
            'badgeColor' => $this->getBadgeColor(),
        ];
    }
}
