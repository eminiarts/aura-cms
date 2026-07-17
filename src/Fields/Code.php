<?php

namespace Aura\Base\Fields;

class Code extends Field
{
    public $edit = 'aura::fields.code';

    public $optionGroup = 'JS Fields';

    // public $view = 'components.fields.code';

    public function get($class, $value, $field = null)
    {
        // If value is a JSON encoded string, decode it
        $decodedValue = json_decode($value, true);

        // Check if decoding was successful and re-encode for formatting
        if (json_last_error() === JSON_ERROR_NONE) {
            return json_encode($decodedValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return $value;
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
            [
                'label' => 'Line Numbers',
                'name' => 'line_numbers',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'slug' => 'line_numbers',
            ],
            [
                'label' => 'Minimum Height',
                'name' => 'min_height',
                'type' => 'Aura\\Base\\Fields\\Number',
                'slug' => 'min_height',
                'validation' => 'nullable|numeric|min:100', // Assuming a reasonable minimum height of 100px
            ],
        ]);
    }

    public function set($post, $field, $value)
    {
        return $value;
    }
}
