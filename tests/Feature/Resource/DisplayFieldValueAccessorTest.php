<?php

use Aura\Base\Resource;

class SnakeCaseAccessorResource extends Resource
{
    public static ?string $slug = 'snake-case-accessor';

    public static string $type = 'SnakeCaseAccessor';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Sub Title',
                'slug' => 'sub_title',
                'type' => 'Aura\Base\Fields\Text',
            ],
        ];
    }

    public function getSubTitleField($value)
    {
        return 'accessor: '.$value;
    }
}

test('displayFieldValue uses the get{Studly}Field accessor for snake_case slugs', function () {
    expect((new SnakeCaseAccessorResource)->displayFieldValue('sub_title', 'hello'))
        ->toBe('accessor: hello');
});

class FalsyAccessorResource extends Resource
{
    public static ?string $slug = 'falsy-accessor';

    public static string $type = 'FalsyAccessor';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Views',
                'slug' => 'views',
                'type' => 'Aura\Base\Fields\Number',
            ],
            [
                'name' => 'Label',
                'slug' => 'label',
                'type' => 'Aura\Base\Fields\Text',
                'display' => fn ($value) => 'display: '.var_export($value, true),
            ],
        ];
    }

    public function getViewsField($value)
    {
        return 'accessor: '.var_export($value, true);
    }
}

test('the get{Studly}Field accessor also runs for falsy values', function ($value) {
    expect((new FalsyAccessorResource)->displayFieldValue('views', $value))
        ->toBe('accessor: '.var_export($value, true));
})->with([0, '', false, null]);

test('a display closure also runs for falsy values', function ($value) {
    expect((new FalsyAccessorResource)->displayFieldValue('label', $value))
        ->toBe('display: '.var_export($value, true));
})->with([0, '', false, null]);
