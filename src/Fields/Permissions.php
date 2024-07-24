<?php

namespace Aura\Base\Fields;

class Permissions extends Field
{
    public $edit = 'aura::fields.permissions';

    public $view = 'aura::fields.permissions-view';

    public function get($field, $value)
    {
        if (is_array($value)) {
            return $value;
        }

        return json_decode($value, true);
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Permissions',
                'name' => 'Permissions',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'select',
                'style' => [],
            ],
            [
                'label' => 'Resource',
                'name' => 'Resource',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'resource',
            ],
        ]);
    }

    public function set($post, $field, $value)
    {
        return json_encode($value);
    }
}
