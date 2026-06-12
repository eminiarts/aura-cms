<?php

use Aura\Base\Pipeline\AddIdsToFields;
use Aura\Base\Pipeline\ApplyParentConditionalLogic;
use Aura\Base\Pipeline\ApplyParentDisplayAttributes;
use Aura\Base\Pipeline\ApplyTabs;
use Aura\Base\Pipeline\FilterEditFields;
use Aura\Base\Pipeline\MapFields;
use Aura\Base\Resource;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

class EditFieldsTestModel extends Resource
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
                'on_edit' => false,
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

class EditFieldsAllVisibleModel extends Resource
{
    public static ?string $slug = 'edit-all-visible';

    public static string $type = 'EditAllVisible';

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

class EditFieldsNestedPanelModel extends Resource
{
    public static ?string $slug = 'edit-nested-panel';

    public static string $type = 'EditNestedPanel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Panel 1',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel-1',
                'on_edit' => false,
            ],
            [
                'name' => 'Nested Text',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'nested-text',
            ],
            [
                'name' => 'Panel 2',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel-2',
            ],
            [
                'name' => 'Visible Text',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'visible-text',
            ],
        ];
    }
}

test('if the first tab is hidden, tabs should be applied correctly to second tab', function () {
    $model = new EditFieldsTestModel;

    $fields = $model->editFields();

    expect($fields)->toHaveCount(1);
    expect($fields[0]['fields'])->toHaveCount(1);
    expect($fields[0]['fields'][0]['slug'])->toBe('tab-2');
    expect($fields[0]['fields'][0]['fields'])->toHaveCount(1);
});

test('check on_edit inheritance', function () {
    $model = new EditFieldsTestModel;

    $fields = $model->sendThroughPipeline($model->fieldsCollection(), [
        ApplyTabs::class,
        MapFields::class,
        AddIdsToFields::class,
        ApplyParentConditionalLogic::class,
        ApplyParentDisplayAttributes::class,
        FilterEditFields::class,
    ]);

    expect($fields)->toHaveCount(3);
    expect($fields->where('slug', 'tab-1')->count())->toBe(0);
    expect($fields->where('slug', 'text1')->count())->toBe(0);
    expect($fields->where('slug', 'tab-2')->count())->toBe(1);
    expect($fields->where('slug', 'text2')->count())->toBe(1);
});

test('editFields returns all visible fields when no on_edit restrictions', function () {
    $model = new EditFieldsAllVisibleModel;

    $fields = $model->editFields();

    expect($fields)->toBeArray();
    expect($fields)->not->toBeEmpty();

    // Check the nested structure contains our fields
    $tabFields = $fields[0]['fields'][0]['fields'] ?? [];
    $slugs = collect($tabFields)->pluck('slug')->toArray();
    expect($slugs)->toContain('text1');
    expect($slugs)->toContain('text2');
});

test('editFields respects on_edit false for panels', function () {
    $model = new EditFieldsNestedPanelModel;

    $fields = $model->editFields();

    expect($fields)->toBeArray();

    // Helper to recursively collect all slugs from the nested structure
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

    // panel-1 and its nested-text should be filtered out
    expect($allSlugs)->not->toContain('panel-1');
    expect($allSlugs)->not->toContain('nested-text');
    // panel-2 and visible-text should be present
    expect($allSlugs)->toContain('panel-2');
    expect($allSlugs)->toContain('visible-text');
});

test('editFields returns proper field structure with required keys', function () {
    $model = new EditFieldsAllVisibleModel;

    $fields = $model->editFields();

    expect($fields[0])->toHaveKey('fields');

    // Navigate to text fields in nested structure
    $tabFields = $fields[0]['fields'][0]['fields'] ?? [];
    $textFields = collect($tabFields)->filter(fn ($item) => isset($item['type']) && $item['type'] === 'Aura\\Base\\Fields\\Text');

    foreach ($textFields as $field) {
        expect($field)->toHaveKey('slug');
        expect($field)->toHaveKey('type');
        expect($field)->toHaveKey('name');
    }
});

test('editFields removes closure attributes for serialization safety', function () {
    $model = new EditFieldsAllVisibleModel;

    $fields = $model->editFields();

    // Helper to check if any field has closure attributes
    $hasClosure = function ($items) use (&$hasClosure) {
        foreach ($items as $item) {
            if (is_array($item)) {
                foreach ($item as $value) {
                    if ($value instanceof Closure) {
                        return true;
                    }
                    if (is_array($value) && $hasClosure([$value])) {
                        return true;
                    }
                }
                if (isset($item['fields']) && $hasClosure($item['fields'])) {
                    return true;
                }
            }
        }

        return false;
    };

    // After pipeline, closure attributes should be removed
    expect($hasClosure($fields))->toBeFalse();
});
