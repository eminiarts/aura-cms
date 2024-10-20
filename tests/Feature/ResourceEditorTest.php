<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\ResourceEditor;
use Aura\Base\Resource;
use Livewire\Livewire;

class ResourceEditorFake extends ResourceEditor
{
    public $toSave = [];

    public function saveFields($fields)
    {
        $this->toSave = $fields;
    }
}

class ResourceEditorTestModel extends Resource
{
    public static ?string $slug = 'model';

    public static string $type = 'Model';

    public static function getFields()
    {
        return [
            [
                'name' => 'Tab 1',
                'global' => true,
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-1',
                'style' => [
                ],
            ],
            [
                'name' => 'Panel 1',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel-1',
                'style' => [
                ],
            ],
            [
                'name' => 'Total',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'total',
            ],
        ];
    }

    public function isAppResource(): bool
    {
        return true;
    }
}

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    $appResource = new ResourceEditorTestModel;

    $this->assertTrue($appResource->isAppResource());
    $this->assertFalse($appResource->isVendorResource());

    Aura::fake();
    Aura::setModel($appResource);
});

it('can mount the resource component', function () {
    Livewire::test(ResourceEditorFake::class, ['slug' => 'Model'])->assertStatus(200);
});

it('can add new tab', function () {
    Livewire::test(ResourceEditorFake::class, ['slug' => 'Model'])
        ->call('addNewTab')
        ->assertDispatched('openSlideOver');
});

it('current resource fields', function () {
    $component = Livewire::test(ResourceEditorFake::class, ['slug' => 'Model']);

    expect($component->fieldsArray)->toBeArray();
    expect($component->fieldsArray)->toHaveCount(3);
});

it('can add fields', function () {
    $component = Livewire::test(ResourceEditorFake::class, ['slug' => 'Model'])
        ->call('saveNewField', ['type' => "Aura\Base\Fields\Text",
            'slug' => 'description',
            'name' => 'Description', ], 0, 'tab-1');

    expect($component->fieldsArray)->toBeArray();
    expect($component->fieldsArray)->toHaveCount(4);

    $component->call('saveNewField', ['type' => "Aura\Base\Fields\Text",
        'slug' => 'description2',
        'name' => 'Description2', ], 0, 'tab-1');

    expect($component->fieldsArray)->toBeArray();
    expect($component->fieldsArray)->toHaveCount(5);

    $component->call('saveNewField', ['type' => "Aura\Base\Fields\Text",
        'slug' => 'description3',
        'name' => 'Description3', ], 0, 'tab-1');

    expect($component->fieldsArray)->toBeArray();
    expect($component->fieldsArray)->toHaveCount(6);
});

it('can delete fields', function () {
    $component = Livewire::test(ResourceEditorFake::class, ['slug' => 'Model']);

    $component->call('deleteField', ['slug' => 'panel-1', 'type' => "Aura\Base\Fields\Text", 'name' => 'Description3']);

    expect($component->fieldsArray)->toBeArray();
    expect($component->fieldsArray)->toHaveCount(2);

    $component->call('deleteField', ['slug' => 'total', 'type' => "Aura\Base\Fields\Text", 'name' => 'Description3']);

    expect($component->fieldsArray)->toHaveCount(1);
});

it('can add template fields', function () {
    // Livewire::test(ResourceEditor::class, ['slug' => 'Model'])
    //     ->call('addTemplateFields', ['slug' => 'my-template'])
    //     ->assertSet('fieldsArray', [['name' => 'Name 1', 'type' => 'Type 1'], ['name' => 'Name 2', 'type' => 'Type 2']])
    //     ->assertSet('newFields', [['name' => 'Name 1', 'type' => 'Type 1'], ['name' => 'Name 2', 'type' => 'Type 2']]);
});

it('can save the resource component', function () {
    $resource = [
        'type' => 'my-type',
        'slug' => 'my-slug',
        'icon' => 'my-icon',
    ];

    Livewire::test(ResourceEditorFake::class, ['slug' => 'Model'])
        ->set('resource', $resource)
        ->call('save')
        ->assertSet('resource', $resource);
});
