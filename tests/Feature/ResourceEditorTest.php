<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\ResourceEditor;
use Aura\Base\Resource;
use Livewire\Livewire;

class ResourceEditorFake extends ResourceEditor
{
    public $toSave = [];

    public function mount($slug)
    {
        $this->slug = $slug;
        $this->model = Aura::findResourceBySlug($slug);
        $this->checkAuthorization();

        // Initialize with exactly the fields from getFields()
        $this->fieldsArray = $this->model->getFields();
        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);

        // Initialize resource array
        $this->resource = [
            'type' => $this->model->getType(),
            'slug' => $this->model->getSlug(),
            'icon' => 'icon-class',
            'group' => 'default',
            'dropdown' => false,
            'sort' => 0,
        ];
    }

    public function saveFields($fields)
    {
        $this->toSave = $fields;
        $this->fieldsArray = $fields;
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

    config(['aura.features.resource_editor' => true]);

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

it('can edit tab', function () {
    $component = Livewire::test(ResourceEditorFake::class, ['slug' => 'Model'])
        ->call('openSidebar', 'tab-1', 'Model')
        ->assertDispatched('openSlideOver');
});

it('current resource fields', function () {
    $component = Livewire::test(ResourceEditorFake::class, ['slug' => 'Model']);

    expect($component->fieldsArray)->toBeArray();
    // We have 3 initial fields from ResourceEditorTestModel::getFields()
    expect($component->fieldsArray)->toHaveCount(3);
});

it('can add fields', function () {
    $component = Livewire::test(ResourceEditorFake::class, ['slug' => 'Model']);

    // Verify initial state
    expect($component->fieldsArray)->toHaveCount(3);

    $component->call('saveNewField', [
        'type' => "Aura\Base\Fields\Text",
        'slug' => 'description',
        'name' => 'Description',
    ], 0, 'tab-1');

    expect($component->fieldsArray)->toBeArray();
    expect($component->fieldsArray)->toHaveCount(4);

    $component->call('saveNewField', [
        'type' => "Aura\Base\Fields\Text",
        'slug' => 'description2',
        'name' => 'Description2',
    ], 0, 'tab-1');

    expect($component->fieldsArray)->toHaveCount(5);

    $component->call('saveNewField', [
        'type' => "Aura\Base\Fields\Text",
        'slug' => 'description3',
        'name' => 'Description3',
    ], 0, 'tab-1');

    expect($component->fieldsArray)->toHaveCount(6);
});

it('can delete fields', function () {
    $component = Livewire::test(ResourceEditorFake::class, ['slug' => 'Model']);

    // Verify initial state
    expect($component->fieldsArray)->toHaveCount(3);

    $component->call('deleteField', [
        'slug' => 'panel-1',
    ]);

    expect($component->fieldsArray)->toBeArray();
    expect($component->fieldsArray)->toHaveCount(2);

    $component->call('deleteField', [
        'slug' => 'total',
    ]);

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
