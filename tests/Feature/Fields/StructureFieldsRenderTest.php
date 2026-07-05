<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Resource;
use Livewire\Livewire;

/*
 * These tests prove empirically which authoring pattern the field pipeline
 * accepts for structure fields (Repeater / Group).
 *
 * The pipeline (MapFields -> AddIdsToFields -> BuildTreeFromFields) maps only
 * the TOP-LEVEL field collection and attaches the runtime `field` instance to
 * each item. The parent/child tree is then built from a FLAT list using the
 * `_parent_id` / `_id` computed in AddIdsToFields — i.e. children of a Repeater
 * or Group must be declared as FOLLOWING SIBLINGS, not nested in a `fields` key.
 *
 * A pre-nested `fields` array never passes through MapFields, so its children
 * lack the `field` key and blow up at render with `Undefined array key "field"`.
 */

// ---------------------------------------------------------------------------
// Repeater — FLAT sibling pattern (correct)
// ---------------------------------------------------------------------------
class FlatRepeaterModel extends Resource
{
    public static ?string $slug = 'flat-repeater';

    public static string $type = 'FlatRepeater';

    public static function getFields()
    {
        return [
            [
                'name' => 'FAQ',
                'slug' => 'faq',
                'type' => 'Aura\\Base\\Fields\\Repeater',
            ],
            // children of the repeater = following siblings
            [
                'name' => 'Question',
                'slug' => 'question',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
            [
                'name' => 'Answer',
                'slug' => 'answer',
                'type' => 'Aura\\Base\\Fields\\Textarea',
            ],
            // exclude_level pops back out of the repeater to the parent level
            [
                'name' => 'After Repeater',
                'slug' => 'after_repeater',
                'type' => 'Aura\\Base\\Fields\\Text',
                'exclude_level' => 1,
            ],
        ];
    }
}

// ---------------------------------------------------------------------------
// Repeater — NESTED `fields` pattern (broken)
// ---------------------------------------------------------------------------
class NestedRepeaterModel extends Resource
{
    public static ?string $slug = 'nested-repeater';

    public static string $type = 'NestedRepeater';

    public static function getFields()
    {
        return [
            [
                'name' => 'FAQ',
                'slug' => 'faq',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'fields' => [
                    ['name' => 'Question', 'slug' => 'question', 'type' => 'Aura\\Base\\Fields\\Text'],
                    ['name' => 'Answer', 'slug' => 'answer', 'type' => 'Aura\\Base\\Fields\\Textarea'],
                ],
            ],
        ];
    }
}

// ---------------------------------------------------------------------------
// Group — FLAT sibling pattern (correct)
// ---------------------------------------------------------------------------
class FlatGroupModel extends Resource
{
    public static ?string $slug = 'flat-group';

    public static string $type = 'FlatGroup';

    public static function getFields()
    {
        return [
            [
                'name' => 'Address',
                'slug' => 'address',
                'type' => 'Aura\\Base\\Fields\\Group',
            ],
            [
                'name' => 'Street',
                'slug' => 'street',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
            [
                'name' => 'City',
                'slug' => 'city',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
        ];
    }
}

// ---------------------------------------------------------------------------
// Group — NESTED `fields` pattern (broken)
// ---------------------------------------------------------------------------
class NestedGroupModel extends Resource
{
    public static ?string $slug = 'nested-group';

    public static string $type = 'NestedGroup';

    public static function getFields()
    {
        return [
            [
                'name' => 'Address',
                'slug' => 'address',
                'type' => 'Aura\\Base\\Fields\\Group',
                'fields' => [
                    ['name' => 'Street', 'slug' => 'street', 'type' => 'Aura\\Base\\Fields\\Text'],
                    ['name' => 'City', 'slug' => 'city', 'type' => 'Aura\\Base\\Fields\\Text'],
                ],
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
    Aura::fake();
});

// ---------------------------------------------------------------------------
// Repeater
// ---------------------------------------------------------------------------
test('flat-sibling Repeater builds the correct child tree', function () {
    $fields = (new FlatRepeaterModel)->editFields();

    // repeater is the first root field, its children are nested under it
    expect($fields[0]['slug'])->toBe('faq');
    $childSlugs = collect($fields[0]['fields'])->pluck('slug')->all();
    expect($childSlugs)->toContain('question')
        ->and($childSlugs)->toContain('answer');

    // every child carries the runtime `field` instance (this is what render needs)
    foreach ($fields[0]['fields'] as $child) {
        expect($child)->toHaveKey('field');
    }

    // exclude_level moved this field back out of the repeater
    expect(collect($fields)->pluck('slug')->all())->toContain('after_repeater');
});

test('flat-sibling Repeater renders the edit form without error', function () {
    Aura::setModel(new FlatRepeaterModel);

    $post = FlatRepeaterModel::create([
        'faq' => [['question' => 'What?', 'answer' => 'This.']],
    ]);

    Livewire::test(Edit::class, ['slug' => 'flat-repeater', 'id' => $post->id])
        ->assertOk()
        ->assertSee('Question')
        ->assertSee('Answer');
});

test('nested-fields Repeater children never receive a field instance', function () {
    $fields = (new NestedRepeaterModel)->editFields();

    // The pre-nested children survive verbatim and lack the `field` key.
    foreach ($fields[0]['fields'] as $child) {
        expect($child)->not->toHaveKey('field');
    }
});

test('nested-fields Repeater crashes when rendering rows', function () {
    Aura::setModel(new NestedRepeaterModel);

    $post = NestedRepeaterModel::create([
        'faq' => [['question' => 'What?', 'answer' => 'This.']],
    ]);

    expect(fn () => Livewire::test(Edit::class, ['slug' => 'nested-repeater', 'id' => $post->id]))
        ->toThrow(Exception::class, 'Undefined array key "field"');
});

// ---------------------------------------------------------------------------
// Group
// ---------------------------------------------------------------------------
test('flat-sibling Group builds the correct child tree', function () {
    $fields = (new FlatGroupModel)->editFields();

    expect($fields[0]['slug'])->toBe('address');
    $childSlugs = collect($fields[0]['fields'])->pluck('slug')->all();
    expect($childSlugs)->toContain('street')
        ->and($childSlugs)->toContain('city');

    foreach ($fields[0]['fields'] as $child) {
        expect($child)->toHaveKey('field');
    }
});

test('flat-sibling Group renders the edit form without error', function () {
    Aura::setModel(new FlatGroupModel);

    $post = FlatGroupModel::create(['street' => 'Main St', 'city' => 'Zurich']);

    Livewire::test(Edit::class, ['slug' => 'flat-group', 'id' => $post->id])
        ->assertOk()
        ->assertSee('Street')
        ->assertSee('City');
});

test('nested-fields Group crashes when rendering children', function () {
    Aura::setModel(new NestedGroupModel);

    $post = NestedGroupModel::create([]);

    expect(fn () => Livewire::test(Edit::class, ['slug' => 'nested-group', 'id' => $post->id]))
        ->toThrow(Exception::class, 'Undefined array key "field"');
});

// ---------------------------------------------------------------------------
// Panel / Tab — same flat-sibling requirement (they render children via
// x-aura::fields.fields, which also calls $child['field']->{$mode}()).
// ---------------------------------------------------------------------------
class FlatPanelTabModel extends Resource
{
    public static ?string $slug = 'flat-panel-tab';

    public static string $type = 'FlatPanelTab';

    public static function getFields()
    {
        return [
            ['name' => 'Main', 'slug' => 'tab_main', 'type' => 'Aura\\Base\\Fields\\Tab', 'global' => true],
            ['name' => 'Details', 'slug' => 'panel_details', 'type' => 'Aura\\Base\\Fields\\Panel'],
            ['name' => 'Title', 'slug' => 'title', 'type' => 'Aura\\Base\\Fields\\Text'],
        ];
    }
}

class NestedPanelModel extends Resource
{
    public static ?string $slug = 'nested-panel';

    public static string $type = 'NestedPanel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Details',
                'slug' => 'panel_details',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'fields' => [
                    ['name' => 'Title', 'slug' => 'title', 'type' => 'Aura\\Base\\Fields\\Text'],
                ],
            ],
        ];
    }
}

test('flat-sibling Panel and Tab render the edit form without error', function () {
    Aura::setModel(new FlatPanelTabModel);

    $post = FlatPanelTabModel::create(['title' => 'Hello']);

    Livewire::test(Edit::class, ['slug' => 'flat-panel-tab', 'id' => $post->id])
        ->assertOk()
        ->assertSee('Details')
        ->assertSee('Title');
});

test('nested-fields Panel crashes when rendering children', function () {
    Aura::setModel(new NestedPanelModel);

    $post = NestedPanelModel::create([]);

    expect(fn () => Livewire::test(Edit::class, ['slug' => 'nested-panel', 'id' => $post->id]))
        ->toThrow(Exception::class, 'Undefined array key "field"');
});
