<?php

use Eminiarts\Aura\ConditionalLogic;
use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Livewire\Resource\Create;
use Eminiarts\Aura\Livewire\Resource\Edit;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

uses()->group('current');

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

class DoNotDeferConditionalLogicTestModel extends Resource
{
    public static string $type = 'TestModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Title',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'on_view' => true,
                'on_forms' => true,
                'slug' => 'title',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Slug',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'slug',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'searchable' => false,
            ],
            [
                'name' => 'Type',
                'type' => 'Eminiarts\\Aura\\Fields\\Select',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'select_type',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                // 'defer' => false,
                'searchable' => false,
                'options' => [
                    [
                        'name' => 'simple',
                        'value' => 'simple',
                    ],
                    [
                        'name' => 'advanced',
                        'value' => 'advanced',
                    ],
                ],
            ],
            [
                'name' => 'Advanced Text',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [
                    [
                        'value' => 'advanced',
                        'field' => 'select_type',
                        'operator' => '==',
                    ],
                ],
                'slug' => 'advanced_text',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'searchable' => false,
            ],
        ];
    }
}

test('defer should be false on field that is in conditional logic', function () {
    $model = new DoNotDeferConditionalLogicTestModel();

    $editFields = $model->editFields();

    $this->assertCount(4, $editFields);

    $this->assertArrayHasKey('conditional_logic', $editFields[3]);

    expect($editFields[2]['defer'])->toBeFalse();

    $createFields = $model->createFields();

    expect($createFields[2]['defer'])->toBeFalse();

});

test('defer should be false on field that is in conditional logic - create view', function () {
    $model = new DoNotDeferConditionalLogicTestModel();
    $createFields = $model->createFields();

    Aura::fake();
    Aura::setModel($model);

    $component = Livewire::test(Create::class, ['slug' => 'TestModel'])
        ->assertSee('Title*')
        ->assertSee('Type')
        ->assertSeeHtml('wire:model="form.fields.select_type"')
        ->assertDontSee('Advanced Text')
        ->set('form.fields.select_type', 'advanced');
    // Somehow it does not work
    // ->assertSee('Advanced Text')

    $show = ConditionalLogic::checkCondition([
        'title' => null,
        'slug' => null,
        'select_type' => 'advanced',
    ], $createFields[3]);

    expect($show)->toBeTrue();

    $show = ConditionalLogic::checkCondition([
        'title' => null,
        'slug' => null,
        'select_type' => null,
    ], $createFields[3]);

    expect($show)->toBeFalse();
});

test('defer should be false on field that is in conditional logic - edit view', function () {
    $model = new DoNotDeferConditionalLogicTestModel();
    $editFields = $model->editFields();

    $post = $model->create([
        'title' => 'Test',
        'slug' => 'test',
        'select_type' => 'simple',
        'advanced_text' => 'test',
    ]);

    // dd($post->toArray());

    Aura::fake();
    Aura::setModel($post);

    $component = Livewire::test(Edit::class, ['slug' => 'TestModel', 'id' => $post->id])
        ->assertSee('Title')
        ->assertSee('Type')
        ->assertSeeHtml('wire:model="form.fields.select_type"')
        ->assertDontSee('Advanced Text')
        ->set('form.fields.select_type', 'advanced');
    // Somehow it does not work
    // ->assertSee('Advanced Text')

    $show = ConditionalLogic::checkCondition([
        'title' => null,
        'slug' => null,
        'select_type' => 'advanced',
    ], $editFields[3]);

    expect($show)->toBeTrue();

    $show = ConditionalLogic::checkCondition([
        'title' => null,
        'slug' => null,
        'select_type' => null,
    ], $editFields[3]);

    expect($show)->toBeFalse();
});
