<?php

namespace Aura\Base\Traits\Concerns;

trait AuraResourceActions
{
    public array $actions = [];

    public array $bulkActions = [];

    public static $showActionsAsButtons = false;

    public function allowedToPerformActions()
    {
        return false;
    }

    public function getActions()
    {
        if (method_exists($this, 'actions')) {
            return $this->actions();
        }

        if (property_exists($this, 'actions')) {
            return $this->actions;
        }
    }

    public function getBulkActions()
    {
        if (method_exists($this, 'bulkActions')) {
            return $this->bulkActions();
        }

        if (property_exists($this, 'bulkActions')) {
            return $this->bulkActions;
        }
    }
}
