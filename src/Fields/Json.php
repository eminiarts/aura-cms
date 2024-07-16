<?php

namespace Aura\Base\Fields;

class Json extends Field
{
    public $component = 'aura::fields.json';

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

    // public function getFields()
    // {
    //     return array_merge(parent::getFields(), [
    //         [
    //             'label' => 'JSON',
    //             'name' => 'JSON',
    //             'type' => 'Aura\\Base\\Fields\\Tab',
    //             'slug' => 'json',
    //             'style' => [],
    //         ],
    //         [
    //             'label' => 'Language',
    //             'name' => 'Language',
    //             'type' => 'Aura\\Base\\Fields\\Select',
    //             'validation' => 'required',
    //             'slug' => 'language',
    //             'options' => [
    //                 'html' => 'HTML',
    //                 'css' => 'CSS',
    //                 'javascript' => 'JavaScript',
    //                 'php' => 'PHP',
    //                 'json' => 'JSON',
    //                 'yaml' => 'YAML',
    //                 'markdown' => 'Markdown',
    //             ],
    //         ],
    //     ]);
    // }

    public function set($post, $field, $value)
    {
        // dd('hier', $value);

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
