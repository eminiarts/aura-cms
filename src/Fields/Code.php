<?php

namespace Eminiarts\Aura\Fields;

class Code extends Field
{
    public $component = 'aura::fields.code';

    // public $view = 'components.fields.code';

    public function get($field, $value)
    {
        return json_encode($value, true);
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
        return $value;
    }
}
