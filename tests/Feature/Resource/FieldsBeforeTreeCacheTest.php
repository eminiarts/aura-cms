<?php

use Aura\Base\Resource;

/**
 * getFieldsBeforeTree() caches under the class name. A call that supplies its
 * own definitions must therefore not populate — or read from — that cache.
 */
class FieldsBeforeTreeCacheResource extends Resource
{
    public static ?string $slug = 'fields-before-tree-cache';

    public static string $type = 'FieldsBeforeTreeCache';

    public static function getFields(): array
    {
        return [
            ['name' => 'Title', 'slug' => 'title', 'type' => 'Aura\Base\Fields\Text'],
        ];
    }
}

$alternateFields = [
    ['name' => 'Alternate', 'slug' => 'alternate', 'type' => 'Aura\Base\Fields\Text'],
];

test('explicit fields do not poison the per-class cache', function () use ($alternateFields) {
    $model = new FieldsBeforeTreeCacheResource;

    expect(collect($model->getFieldsBeforeTree())->pluck('slug')->all())->toBe(['title']);

    expect(collect($model->getFieldsBeforeTree($alternateFields))->pluck('slug')->all())
        ->toBe(['alternate']);

    expect(collect($model->getFieldsBeforeTree())->pluck('slug')->all())->toBe(['title']);
});

test('explicit fields are honoured even before the cache is warm', function () use ($alternateFields) {
    $model = new FieldsBeforeTreeCacheResource;

    expect(collect($model->getFieldsBeforeTree($alternateFields))->pluck('slug')->all())
        ->toBe(['alternate']);

    expect(collect($model->getFieldsBeforeTree())->pluck('slug')->all())->toBe(['title']);
});
