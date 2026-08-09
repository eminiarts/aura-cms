<?php

use Aura\Base\Fields\Text;
use Aura\Base\Resource;

class MutableFieldCacheResource extends Resource
{
    public static array $fieldDefinition = [
        ['name' => 'First', 'slug' => 'first', 'type' => 'Aura\\Base\\Fields\\Text'],
    ];

    public static function getFields(): array
    {
        return static::$fieldDefinition;
    }
}

beforeEach(function () {
    MutableFieldCacheResource::$fieldDefinition = [
        ['name' => 'First', 'slug' => 'first', 'type' => 'Aura\\Base\\Fields\\Text'],
    ];

    Resource::flushFieldCache();
});

it('flushes every field definition cache including the parsed container binding', function () {
    $resource = new MutableFieldCacheResource;
    $containerCacheKey = MutableFieldCacheResource::class.'-getFieldsBeforeTree';

    expect($resource->fieldsCollection()->pluck('slug')->all())->toBe(['first'])
        ->and($resource->fieldBySlug('first')['slug'])->toBe('first')
        ->and($resource->fieldClassBySlug('first'))->toBeInstanceOf(Text::class)
        ->and($resource->inputFieldsSlugs())->toBe(['first'])
        ->and($resource->mappedFields()->pluck('slug')->all())->toBe(['first'])
        ->and($resource->getFieldsBeforeTree()->pluck('slug')->all())->toBe(['first'])
        ->and(app()->bound($containerCacheKey))->toBeTrue();

    MutableFieldCacheResource::$fieldDefinition = [
        ['name' => 'Second', 'slug' => 'second', 'type' => 'Aura\\Base\\Fields\\Text'],
    ];

    expect($resource->fieldsCollection()->pluck('slug')->all())->toBe(['first'])
        ->and($resource->getFieldsBeforeTree()->pluck('slug')->all())->toBe(['first']);

    Resource::flushFieldCache();

    expect(app()->bound($containerCacheKey))->toBeFalse()
        ->and($resource->fieldsCollection()->pluck('slug')->all())->toBe(['second'])
        ->and($resource->fieldBySlug('second')['slug'])->toBe('second')
        ->and($resource->fieldClassBySlug('second'))->toBeInstanceOf(Text::class)
        ->and($resource->inputFieldsSlugs())->toBe(['second'])
        ->and($resource->mappedFields()->pluck('slug')->all())->toBe(['second'])
        ->and($resource->getFieldsBeforeTree()->pluck('slug')->all())->toBe(['second']);
});
