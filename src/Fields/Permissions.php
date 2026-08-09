<?php

namespace Aura\Base\Fields;

use Aura\Base\Contracts\FieldValueStorage;
use Illuminate\Database\Eloquent\Model;

class Permissions extends Field
{
    public $edit = 'aura::fields.permissions';

    public $view = 'aura::fields.permissions-view';

    public function get($class, $value, $field = null)
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

    public function normalizeForStorage(
        mixed $value,
        array $field,
        ?Model $model,
        FieldValueStorage $storage,
    ): mixed {
        $slug = $field['slug'] ?? null;

        if ($storage === FieldValueStorage::Physical
            && is_string($slug)
            && $model?->hasCast($slug, ['array', 'json', 'object', 'collection'])) {
            return $value;
        }

        return parent::normalizeForStorage($value, $field, $model, $storage);
    }

    public function set($post, $field, $value)
    {
        return json_encode($value);
    }
}
