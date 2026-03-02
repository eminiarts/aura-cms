<?php

use Aura\Base\Pipeline\AddIdsToFields;
use Aura\Base\Pipeline\ApplyParentConditionalLogic;
use Aura\Base\Pipeline\ApplyParentDisplayAttributes;
use Aura\Base\Pipeline\ApplyTabs;
use Aura\Base\Pipeline\FilterCreateFields;
use Aura\Base\Pipeline\MapFields;
use Aura\Base\Resource;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

class CreateFieldsTestModel extends Resource
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
                'on_create' => false,
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

class CreateFieldsAllVisibleModel extends Resource
{
    public static ?string $slug = 'all-visible';

    public static string $type = 'AllVisible';

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

class CreateFieldsNestedPanelModel extends Resource
{
    public static ?string $slug = 'nested-panel';

    public static string $type = 'NestedPanel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Panel 1',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel-1',
                'on_create' => false,
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
    $model = new CreateFieldsTestModel;

    $fields = $model->createFields();

    expect($fields)->toHaveCount(1);
    expect($fields[0]['fields'])->toHaveCount(1);
    expect($fields[0]['fields'][0]['slug'])->toBe('tab-2');
    expect($fields[0]['fields'][0]['fields'])->toHaveCount(1);
});

test('check on_create inheritance', function () {
    $model = new CreateFieldsTestModel;

    $fields = $model->sendThroughPipeline($model->fieldsCollection(), [
        ApplyTabs::class,
        MapFields::class,
        AddIdsToFields::class,
        ApplyParentConditionalLogic::class,
        ApplyParentDisplayAttributes::class,
        FilterCreateFields::class,
    ]);

    expect($fields)->toHaveCount(3);
    expect($fields->where('slug', 'tab-1')->count())->toBe(0);
    expect($fields->where('slug', 'text1')->count())->toBe(0);
    expect($fields->where('slug', 'tab-2')->count())->toBe(1);
    expect($fields->where('slug', 'text2')->count())->toBe(1);
});

test('createFields returns all visible fields when no on_create restrictions', function () {
    $model = new CreateFieldsAllVisibleModel;

    $fields = $model->createFields();

    expect($fields)->toBeArray();
    expect($fields)->not->toBeEmpty();

    // Check the nested structure contains our fields
    // Fields are wrapped in a tab structure: fields[0]['fields'][0] is the tab
    $tabFields = $fields[0]['fields'][0]['fields'] ?? [];
    $slugs = collect($tabFields)->pluck('slug')->toArray();
    expect($slugs)->toContain('text1');
    expect($slugs)->toContain('text2');
});

test('createFields respects on_create false for panels', function () {
    $model = new CreateFieldsNestedPanelModel;

    $fields = $model->createFields();

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

test('createFields returns proper field structure with required keys', function () {
    $model = new CreateFieldsAllVisibleModel;

    $fields = $model->createFields();

    // First level should have wrapper fields
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

test('fieldsCollection returns collection of fields', function () {
    $model = new CreateFieldsTestModel;

    $fields = $model->fieldsCollection();

    expect($fields)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($fields)->not->toBeEmpty();
});
