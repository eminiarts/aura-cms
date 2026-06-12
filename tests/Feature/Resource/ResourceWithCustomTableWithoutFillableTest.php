<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
class ResourceWithCustomTableWithoutFillableModel extends Resource
{
    public static $customTable = true;

    public static $singularName = 'Project';

    public static ?string $slug = 'project';

    public static string $type = 'Project';

    public static bool $usesMeta = false;

    // cast options to array
    protected $casts = [
        'options' => 'array',
        'enabled' => 'boolean',
    ];

    // set fillable fields
    protected $guarded = [];

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
    $resource = ResourceWithCustomTableWithoutFillableModel::create([
        'name' => 'Test Post 1',
        'status' => 'publish',
        'enabled' => 1,
        'options' => [
            'option1' => 'Option 1',
            'option2' => 'Option 2',
        ],
    ]);

    expect($resource->usesCustomTable())->toBe(true);

    $db = DB::table('custom_projects')->where('id', $resource->id)->first();

    expect($db->name)->toBe('Test Post 1');
    expect($db->status)->toBe('publish');
    expect($db->enabled)->toBe(1);
    expect(json_decode($db->options, true))->toBe([
        'option1' => 'Option 1',
        'option2' => 'Option 2',
    ]);

    $meta = DB::table('meta')->where('metable_id', $resource->id)->where('metable_type', ResourceWithCustomTableWithoutFillableModel::class)->get();

    expect($meta->where('key', 'name')->first())->toBeNull();
    expect($meta->where('key', 'options')->first())->toBeNull();
    expect($meta->where('key', 'enabled')->first())->toBeNull();
});

test('custom Table - Meta is not used for fields', function () {
    $resource = ResourceWithCustomTableWithoutFillableModel::create([
        'status' => 'publish',
    ]);

    expect($resource->usesCustomTable())->toBe(true);
    expect($resource->name)->toBeNull();

    DB::table('meta')->insert([
        'metable_id' => $resource->id,
        'metable_type' => ResourceWithCustomTableWithoutFillableModel::class,
        'key' => 'name',
        'value' => 'Test Post 1',
    ]);

    $resource = ResourceWithCustomTableWithoutFillableModel::find($resource->id);

    expect($resource->name)->not->toBe('Test Post 1');
    expect($resource->name)->toBeNull();
    expect($resource->display('name'))->toBeNull();
});

test('custom table without fillable - uses guarded instead', function () {
    $resource = new ResourceWithCustomTableWithoutFillableModel;

    expect($resource->getGuarded())->toBe([]);
});

test('custom table without fillable - does not use meta', function () {
    $resource = new ResourceWithCustomTableWithoutFillableModel;

    expect(ResourceWithCustomTableWithoutFillableModel::$usesMeta)->toBeFalse();
});

test('custom table without fillable - can update fields directly', function () {
    $resource = ResourceWithCustomTableWithoutFillableModel::create([
        'name' => 'Original',
        'status' => 'draft',
    ]);

    $resource->update([
        'name' => 'Updated',
        'status' => 'published',
    ]);

    $resource->refresh();

    expect($resource->name)->toBe('Updated');
    expect($resource->status)->toBe('published');

    $this->assertDatabaseHas('custom_projects', [
        'id' => $resource->id,
        'name' => 'Updated',
        'status' => 'published',
    ]);
});

test('custom table without fillable - boolean and array casting work', function () {
    $resource = ResourceWithCustomTableWithoutFillableModel::create([
        'name' => 'Cast Test',
        'enabled' => true,
        'options' => ['a' => 1, 'b' => 2],
    ]);

    expect($resource->enabled)->toBe(true);
    expect($resource->options)->toBeArray();
    expect($resource->options['a'])->toBe(1);
});
