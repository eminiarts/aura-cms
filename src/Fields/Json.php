<?php

namespace Aura\Base\Fields;

class Json extends Field
{
    public $edit = 'aura::fields.json';

    public $view = 'aura::fields.view-value';

    public function display($field, $value, $model)
    {
        // The encoded payload is database-backed and view-value renders this
        // raw, so neutralise stored markup. ENT_NOQUOTES: the JSON quotes are
        // text content here, not an attribute value, and escaping them would
        // only make the rendered payload unreadable.
        return htmlspecialchars((string) json_encode($value), ENT_NOQUOTES, 'UTF-8', false);
    }

    public function get($class, $value, $field = null)
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
        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
