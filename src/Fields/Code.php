<?php

namespace Eminiarts\Aura\Fields;

class Code extends Field
{
    public string $component = 'fields.code';

    protected string $view = 'components.fields.code';

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
                'type' => 'App\\Aura\\Fields\\Tab',
                'slug' => 'code',
                'style' => [],
            ],
            [
                'label' => 'Language',
                'name' => 'Language',
                'type' => 'App\\Aura\\Fields\\Select',
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
