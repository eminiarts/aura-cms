<?php

use Aura\Base\Resource;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

class ViewFieldsTestModel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'name' => 'Tab 1',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-1',
                'global' => true,
            ],
            [
                'name' => 'Text 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text1',
            ],
            [
                'name' => 'Tab 2',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-2',
                'global' => true,
                'on_view' => false,
            ],
            [
                'label' => 'Text 2',
                'name' => 'Text 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text2',
            ],
        ];
    }
}

class ViewFieldsTestModel2 extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'name' => 'Tab 1',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-1',
                'global' => true,
            ],
            [
                'name' => 'Text 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text1',
                'on_view' => true,
            ],
            [
                'name' => 'Tab 2',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-2',
                'global' => true,
            ],
            [
                'label' => 'Text 2',
                'name' => 'Text 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text2',
                'on_view' => false,
            ],
        ];
    }
}

class ViewFieldsAllVisibleModel extends Resource
{
    public static ?string $slug = 'view-all-visible';

    public static string $type = 'ViewAllVisible';

    public static function getFields()
    {
        return [
            [
                'name' => 'Tab 1',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-1',
                'global' => true,
            ],
            [
                'name' => 'Text 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'text1',
            ],
            [
                'name' => 'Text 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'text2',
            ],
        ];
    }
}

test('field inherits on_view from parent', function () {
    $model = new ViewFieldsTestModel;

    $fields = $model->viewFields();

    expect($fields)->toHaveCount(1);
    expect($fields[0]['fields'])->toHaveCount(1);
    expect($fields[0]['fields'][0]['fields'])->toHaveCount(1);
    expect($fields[0]['fields'][0]['fields'][0]['slug'])->toBe('text1');
    expect($fields[0]['fields'][0]['fields'][0]['slug'])->not->toBe('text2');
});

test('field is hidden when on_view is false', function () {
    $model = new ViewFieldsTestModel2;

    $fields = $model->viewFields();

    expect($fields)->toHaveCount(1);
    expect($fields[0]['fields'])->toHaveCount(2);
    expect($fields[0]['fields'][0]['fields'])->toHaveCount(1);
    expect($fields[0]['fields'][0]['fields'][0]['slug'])->toBe('text1');
    expect($fields[0]['fields'][0]['fields'][0]['slug'])->not->toBe('text2');
    expect(optional($fields[0]['fields'][1])['fields'])->toBeEmpty();
});

test('viewFields returns all visible fields when no on_view restrictions', function () {
    $model = new ViewFieldsAllVisibleModel;

    $fields = $model->viewFields();

    expect($fields)->toBeArray();
    expect($fields)->not->toBeEmpty();

    // Check the nested structure contains our fields
    $tabFields = $fields[0]['fields'][0]['fields'] ?? [];
    $slugs = collect($tabFields)->pluck('slug')->toArray();
    expect($slugs)->toContain('text1');
    expect($slugs)->toContain('text2');
});

test('viewFields returns proper nested structure', function () {
    $model = new ViewFieldsAllVisibleModel;

    $fields = $model->viewFields();

    // Verify the structure: wrapper -> tab -> fields
    expect($fields[0])->toHaveKey('fields');
    expect($fields[0]['fields'][0])->toHaveKey('slug');
    expect($fields[0]['fields'][0]['slug'])->toBe('tab-1');
    expect($fields[0]['fields'][0])->toHaveKey('fields');
});

test('fieldsForView removes validation attribute', function () {
    $model = new ViewFieldsTestModel;

    $fields = $model->fieldsForView();

    // Helper to check if any field has validation attribute
    $hasValidation = function ($items) use (&$hasValidation) {
        foreach ($items as $item) {
            if (is_array($item)) {
                if (isset($item['validation']) && ! empty($item['validation'])) {
                    return true;
                }
                if (isset($item['fields']) && $hasValidation($item['fields'])) {
                    return true;
                }
            }
        }

        return false;
    };

    // fieldsForView should have validation removed by RemoveValidationAttribute pipeline
    expect($hasValidation($fields))->toBeFalse();
});

test('viewFields handles empty tabs gracefully', function () {
    $model = new ViewFieldsTestModel;

    $fields = $model->viewFields();

    // Tab 2 has on_view=false, so its child text2 should be hidden
    // The structure should still be valid
    expect($fields)->toBeArray();

    // Helper to recursively collect all slugs
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
    expect($allSlugs)->toContain('text1');
    expect($allSlugs)->not->toContain('text2');
});
