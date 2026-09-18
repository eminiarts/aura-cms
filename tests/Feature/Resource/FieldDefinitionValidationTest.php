<?php

use Aura\Base\Resource;

/**
 * A malformed field definition used to surface as an "Undefined array key"
 * notice deep inside the field pipeline. MapFields now rejects it up front and
 * names the resource, the index and the offending key.
 */
class MissingSlugFieldResource extends Resource
{
    public static ?string $slug = 'missing-slug-field';

    public static string $type = 'MissingSlugField';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => 'Aura\Base\Fields\Text',
            ],
            [
                'name' => 'Body',
                'type' => 'Aura\Base\Fields\Text',
            ],
        ];
    }
}

class MissingTypeFieldResource extends Resource
{
    public static ?string $slug = 'missing-type-field';

    public static string $type = 'MissingTypeField';

    public static function getFields(): array
    {
        return [
            ['name' => 'Title', 'slug' => 'title'],
        ];
    }
}

class UnknownFieldClassResource extends Resource
{
    public static ?string $slug = 'unknown-field-class';

    public static string $type = 'UnknownFieldClass';

    public static function getFields(): array
    {
        return [
            ['name' => 'Title', 'slug' => 'title', 'type' => 'App\Fields\DoesNotExist'],
        ];
    }
}

test('a field definition without a slug names the resource and the index', function () {
    expect(fn () => (new MissingSlugFieldResource)->createFields())
        ->toThrow(
            InvalidArgumentException::class,
            '[MissingSlugFieldResource] field #1 is missing the required "slug" key.'
        );
});

test('a field definition without a type names the resource and the index', function () {
    expect(fn () => (new MissingTypeFieldResource)->createFields())
        ->toThrow(
            InvalidArgumentException::class,
            '[MissingTypeFieldResource] field #0 is missing the required "type" key.'
        );
});

test('a field definition pointing at an unknown field class is rejected', function () {
    expect(fn () => (new UnknownFieldClassResource)->createFields())
        ->toThrow(
            InvalidArgumentException::class,
            '[UnknownFieldClassResource] field #0 ("title") declares the field class [App\Fields\DoesNotExist], which does not exist.'
        );
});
