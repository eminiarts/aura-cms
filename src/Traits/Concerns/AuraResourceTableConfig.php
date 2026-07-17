<?php

namespace Aura\Base\Traits\Concerns;

use Aura\Base\ConditionalLogic;

trait AuraResourceTableConfig
{
    public function getHeaders()
    {
        $fields = $this->indexFields();

        // Filter $fields based on Conditional Logic for roles
        $fields = $fields->filter(function ($field) {
            return ConditionalLogic::fieldIsVisibleTo($field, auth()->user());
        });

        $fields = $fields->pluck('name', 'slug')
            ->when($this->usesTitle(), function ($collection, $value) {
                return $collection->prepend('title', 'title');
            })
            ->prepend('ID', 'id');

        return $fields;
    }

    public function indexTableSettings()
    {
        return [];
    }

    public function isNumberField($key)
    {
        if ($this->fieldBySlug($key)['type'] == 'Aura\\Base\\Fields\\Number') {
            return true;
        }

        return false;
    }
}
