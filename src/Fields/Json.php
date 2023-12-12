<?php

namespace Eminiarts\Aura\Fields;

class Json extends Field
{
    public $component = 'aura::fields.code';

    public $view = 'aura::fields.view-value';

    public function display($field, $value, $model)
    {
        return json_encode($value);
    }

    public function get($field, $value)
    {
        if (is_array($value) || $value === null) {
            return $value;
        }

        return json_decode($value, true);
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Code',
                'name' => 'Code',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'code',
                'style' => [],
            ],
            [
                'label' => 'Language',
                'name' => 'Language',
                'type' => 'Eminiarts\\Aura\\Fields\\Select',
                'validation' => 'required',
                'slug' => 'language',
                'options' => [
                    'html' => 'HTML',
                    'css' => 'CSS',
                    'javascript' => 'JavaScript',
                    'php' => 'PHP',
                    'json' => 'JSON',
                    'yaml' => 'YAML',
                    'markdown' => 'Markdown',
                ],
            ],
        ]);
    }

    public function set($value)
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
