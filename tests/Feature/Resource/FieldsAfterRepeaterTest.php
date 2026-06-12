<?php

use Aura\Base\Resource;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

class FieldsAfterRepeaterModel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'name' => 'Options',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'validation' => '',
                'slug' => 'options',
            ],
            [
                'name' => 'Value',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'value',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'name',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Multiple',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'exclude_level' => 1,
                'slug' => 'multiple',
                'instructions' => 'Allow multiple selections?',
            ],
        ];
    }
}

class FieldsWithMultipleRepeatersModel extends Resource
{
    public static ?string $slug = 'multi-repeater';

    public static string $type = 'MultiRepeater';

    public static function getFields()
    {
        return [
            [
                'name' => 'First Repeater',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'slug' => 'first-repeater',
            ],
            [
                'name' => 'First Field',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'first-field',
            ],
            [
                'name' => 'Second Repeater',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'slug' => 'second-repeater',
            ],
            [
                'name' => 'Second Field',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'second-field',
            ],
            [
                'name' => 'Outside Field',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'outside-field',
                'exclude_level' => 1,
            ],
        ];
    }
}

test('multiple should be after the repeater', function () {
    $model = new FieldsAfterRepeaterModel;

    $fields = $model->createFields();

    expect($fields)->toHaveCount(2);
    expect($fields[0]['fields'])->toHaveCount(2);
    expect($fields[1]['slug'])->toBe('multiple');
});

test('exclude_level moves field outside of repeater nesting', function () {
    $model = new FieldsAfterRepeaterModel;

    $fields = $model->createFields();

    // The 'multiple' field has exclude_level=1, so it should be at root level
    expect($fields[1]['slug'])->toBe('multiple');
    expect($fields[1]['type'])->toBe('Aura\\Base\\Fields\\Boolean');
});

test('repeater contains nested fields', function () {
    $model = new FieldsAfterRepeaterModel;

    $fields = $model->createFields();

    // First element should be the repeater wrapper
    expect($fields[0]['slug'])->toBe('options');

    // Repeater should contain value and name fields
    $repeaterFields = $fields[0]['fields'];
    $slugs = collect($repeaterFields)->pluck('slug')->toArray();
    expect($slugs)->toContain('value');
    expect($slugs)->toContain('name');
});

test('multiple repeaters maintain correct field grouping', function () {
    $model = new FieldsWithMultipleRepeatersModel;

    $fields = $model->createFields();

    // Helper to recursively collect all slugs at each level
    $collectSlugs = function ($items) use (&$collectSlugs) {
        $slugs = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                if (isset($item['slug'])) {
                    $slugs[] = $item['slug'];
                }
                if (isset($item['fields'])) {
                    $slugs = array_merge($slugs, $collectSlugs($item['fields']));
                }
            }
        }

        return $slugs;
    };

    $allSlugs = $collectSlugs($fields);

    // All fields should be present
    expect($allSlugs)->toContain('first-repeater');
    expect($allSlugs)->toContain('first-field');
    expect($allSlugs)->toContain('second-repeater');
    expect($allSlugs)->toContain('second-field');
    expect($allSlugs)->toContain('outside-field');
});

test('fields with style width are preserved', function () {
    $model = new FieldsAfterRepeaterModel;

    $fields = $model->createFields();

    // Find the value field and check its style
    $repeaterFields = $fields[0]['fields'];
    $valueField = collect($repeaterFields)->firstWhere('slug', 'value');

    expect($valueField)->not->toBeNull();
    expect($valueField['style']['width'])->toBe('50');
});
