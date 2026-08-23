<?php

use Aura\Base\Resource;

class SnakeCaseAccessorResource extends Resource
{
    public static ?string $slug = 'snake-case-accessor';

    public static string $type = 'SnakeCaseAccessor';

    public static function getFields()
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
