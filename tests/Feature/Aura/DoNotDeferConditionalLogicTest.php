<?php

use Aura\Base\ConditionalLogic;
use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Resource;
use Livewire\Livewire;

uses()->group('current');

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new DoNotDeferConditionalLogicTestModel);
});

class DoNotDeferConditionalLogicTestModel extends Resource
{
    public static string $type = 'TestModel';

    protected static ?string $slug = 'test';

    public static function getFields()
    {
        return [
            [
                'name' => 'Title',
                'type' => 'Aura\\Base\\Fields\\Text',
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
                'type' => 'Aura\\Base\\Fields\\Text',
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
                'type' => 'Aura\\Base\\Fields\\Select',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'select_type',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                // 'live' => true,
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
                'type' => 'Aura\\Base\\Fields\\Text',
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
    $model = new DoNotDeferConditionalLogicTestModel;

    $editFields = $model->editFields();

    $this->assertCount(4, $editFields);

    $this->assertArrayHasKey('conditional_logic', $editFields[3]);

    expect($editFields[2]['defer'])->toBeFalse();

    $createFields = $model->createFields();

    expect($createFields[2]['defer'])->toBeFalse();

});

test('defer should be false on field that is in conditional logic - create view', function () {
    $model = new DoNotDeferConditionalLogicTestModel;
    $createFields = $model->createFields();

    Aura::fake();
    Aura::setModel($model);

    $component = Livewire::test(Create::class, ['slug' => 'test'])
        ->assertSee('Title*')
        ->assertSee('Type')
        ->assertSeeHtml('wire:model="form.fields.select_type"')
        ->assertDontSee('Advanced Text')
        ->set('form.fields.select_type', 'advanced');

    $modelData = [
        'title' => null,
        'slug' => null,
        'select_type' => 'advanced',
    ];

    $show = ConditionalLogic::checkCondition($modelData, $createFields[3]);

    expect($show)->toBeTrue();

    $modelData['select_type'] = null;
    $show = ConditionalLogic::checkCondition($modelData, $createFields[3]);

    expect($show)->toBeFalse();
});

test('defer should be false on field that is in conditional logic - edit view', function () {
    $model = new DoNotDeferConditionalLogicTestModel;
    $editFields = $model->editFields();

    $post = $model->create([
        'title' => 'Test',
        'slug' => 'test',
        'select_type' => 'simple',
        'advanced_text' => 'test',
    ]);

    Aura::fake();
    Aura::setModel($post);

    $component = Livewire::test(Edit::class, ['slug' => 'TestModel', 'id' => $post->id])
        ->assertSee('Title')
        ->assertSee('Type')
        ->assertSeeHtml('wire:model="form.fields.select_type"')
        ->assertDontSee('Advanced Text')
        ->set('form.fields.select_type', 'advanced');

    $modelData = [
        'title' => 'Test',
        'slug' => 'test',
        'select_type' => 'advanced',
        'advanced_text' => 'test',
    ];

    $show = ConditionalLogic::checkCondition($modelData, $editFields[3]);

    expect($show)->toBeTrue();

    $modelData['select_type'] = 'simple';
    $show = ConditionalLogic::checkCondition($modelData, $editFields[3]);

    expect($show)->toBeFalse();
});
