<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

afterEach(function () {
    Schema::dropIfExists('custom_projects');

    Aura::clear();
});

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    // Create a custom table for this test
    Schema::create('custom_projects', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->string('status')->nullable();
        $table->boolean('enabled')->nullable();
        $table->text('options')->nullable();
        $table->foreignId('user_id');
        $table->foreignId('team_id');
        $table->timestamps();
    });
});

// Create Resource for this test
class ResourceWithCustomTableModel extends Resource
{
    public static $customTable = true;

    public static $singularName = 'Project';

    public static ?string $slug = 'project';

    public static string $type = 'Project';

    // cast options to array
    protected $casts = [
        'options' => 'array',
        'enabled' => 'boolean',
    ];

    // set fillable fields
    protected $fillable = [
        'name', 'status', 'enabled', 'options', 'user_id', 'team_id',
    ];

    protected $table = 'custom_projects';

    public static function getFields()
    {
        return [
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => [],
                'slug' => 'name',
            ],
            [
                'name' => 'Status',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'status',
            ],
            [
                'name' => 'Enabled',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'enabled',
            ],
            [
                'name' => 'Options',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'options',
            ],
        ];
    }
}

test('custom Table - Fields get saved correctly when fillable are set', function () {
    $resource = ResourceWithCustomTableModel::create([
        'name' => 'Test Post 1',
        'status' => 'publish',
        'enabled' => 1,
        'options' => [
            'option1' => 'Option 1',
            'option2' => 'Option 2',
        ],
    ]);

    expect($resource->usesCustomTable())->toBe(true);
    expect($resource->name)->toBe('Test Post 1');
    expect($resource->status)->toBe('publish');
    expect($resource->enabled)->toBe(true);
    expect($resource->options)->toBe([
        'option1' => 'Option 1',
        'option2' => 'Option 2',
    ]);

    $db = DB::table('custom_projects')->where('id', $resource->id)->first();

    expect($db->name)->toBe('Test Post 1');
    expect($db->status)->toBe('publish');
    expect($db->enabled)->toBe(1);
    expect(json_decode($db->options, true))->toBe([
        'option1' => 'Option 1',
        'option2' => 'Option 2',
    ]);
});

test('custom table resource uses correct table name', function () {
    $resource = new ResourceWithCustomTableModel;

    expect($resource->getTable())->toBe('custom_projects');
});

test('custom table resource can be updated', function () {
    $resource = ResourceWithCustomTableModel::create([
        'name' => 'Original Name',
        'status' => 'draft',
    ]);

    $resource->update([
        'name' => 'Updated Name',
        'status' => 'publish',
    ]);

    $resource->refresh();

    expect($resource->name)->toBe('Updated Name');
    expect($resource->status)->toBe('publish');

    $this->assertDatabaseHas('custom_projects', [
        'id' => $resource->id,
        'name' => 'Updated Name',
        'status' => 'publish',
    ]);
});

test('custom table resource can be deleted', function () {
    $resource = ResourceWithCustomTableModel::create([
        'name' => 'To Be Deleted',
        'status' => 'draft',
    ]);

    $resourceId = $resource->id;

    $resource->delete();

    $this->assertDatabaseMissing('custom_projects', [
        'id' => $resourceId,
    ]);
});

test('custom table resource boolean casting works correctly', function () {
    $resource = ResourceWithCustomTableModel::create([
        'name' => 'Boolean Test',
        'enabled' => true,
    ]);

    expect($resource->enabled)->toBe(true);

    $resource->update(['enabled' => false]);
    $resource->refresh();

    expect($resource->enabled)->toBe(false);
});

test('custom table resource array casting works correctly', function () {
    $resource = ResourceWithCustomTableModel::create([
        'name' => 'Array Test',
        'options' => ['key1' => 'value1', 'key2' => 'value2'],
    ]);

    expect($resource->options)->toBeArray();
    expect($resource->options['key1'])->toBe('value1');
    expect($resource->options['key2'])->toBe('value2');
});

test('custom table resource can query by attributes', function () {
    ResourceWithCustomTableModel::create([
        'name' => 'Project A',
        'status' => 'active',
    ]);

    ResourceWithCustomTableModel::create([
        'name' => 'Project B',
        'status' => 'inactive',
    ]);

    $activeProjects = ResourceWithCustomTableModel::where('status', 'active')->get();

    expect($activeProjects)->toHaveCount(1);
    expect($activeProjects->first()->name)->toBe('Project A');
});

test('custom table resource has correct type', function () {
    expect(ResourceWithCustomTableModel::getType())->toBe('Project');
});

test('custom table resource has correct slug', function () {
    expect(ResourceWithCustomTableModel::getSlug())->toBe('project');
});
