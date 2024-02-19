<?php

namespace Aura\Base\Fields;

class Code extends Field
{
    public $component = 'aura::fields.code';

    public $optionGroup = 'JS Fields';

    // public $view = 'components.fields.code';

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
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'code',
                'style' => [],
            ],
            [
                'label' => 'Language',
                'name' => 'Language',
                'type' => 'Aura\\Base\\Fields\\Select',
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
        return json_encode($value);
    }
}
