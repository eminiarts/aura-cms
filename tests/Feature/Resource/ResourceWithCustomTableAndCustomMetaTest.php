<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Models\Meta;
use Aura\Base\Resource;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

afterEach(function () {
    Schema::dropIfExists('custom_projects');
    Schema::dropIfExists('custom_projects_meta');

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

class ResourceWithCustomTableAndCustomMetaModel extends Resource
{
    public static $customMeta = true;

    public static $customTable = true;

    public static $singularName = 'Project';

    public static ?string $slug = 'project';

    public static string $type = 'Project';

    public static bool $usesMeta = true;

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
            [
                'name' => 'Meta 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'meta_1',
            ],
            [
                'name' => 'Meta 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'meta_2',
            ],
        ];
    }
}

test('custom Table - Fields get saved correctly when fillable are set and meta are used', function () {
    $resource = ResourceWithCustomTableAndCustomMetaModel::create([
        'name' => 'Test Post 1',
        'status' => 'publish',
        'enabled' => 1,
        'meta_1' => 'first',
        'meta_2' => 'second',
        'options' => [
            'option1' => 'Option 1',
            'option2' => 'Option 2',
        ],
    ]);

    expect($resource->usesCustomTable())->toBe(true);
    expect($resource->name)->toBe('Test Post 1');
    expect($resource->status)->toBe('publish');
    expect($resource->enabled)->toBe(true);
    expect($resource->meta_1)->toBe('first');
    expect($resource->meta_2)->toBe('second');
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

    $meta = Meta::where('metable_id', $resource->id)
        ->where('metable_type', ResourceWithCustomTableAndCustomMetaModel::class)
        ->get();

    expect($meta->count())->toBe(2);

    $meta1 = $meta->where('key', 'meta_1')->first();
    $meta2 = $meta->where('key', 'meta_2')->first();

    expect($meta1)->not->toBeNull();
    expect($meta1->value)->toBe('first');

    expect($meta2)->not->toBeNull();
    expect($meta2->value)->toBe('second');
});

test('custom table with meta - meta values can be updated', function () {
    $resource = ResourceWithCustomTableAndCustomMetaModel::create([
        'name' => 'Test Post',
        'meta_1' => 'original',
        'meta_2' => 'original2',
    ]);

    $resource->update([
        'meta_1' => 'updated',
        'meta_2' => 'updated2',
    ]);

    $resource->refresh();

    expect($resource->meta_1)->toBe('updated');
    expect($resource->meta_2)->toBe('updated2');
});

test('custom table with meta - correctly identifies custom meta usage', function () {
    $resource = new ResourceWithCustomTableAndCustomMetaModel;

    expect($resource::$customMeta)->toBeTrue();
    expect($resource::$usesMeta)->toBeTrue();
});

test('custom table with meta - table column fields stored in table', function () {
    $resource = ResourceWithCustomTableAndCustomMetaModel::create([
        'name' => 'Column Test',
        'status' => 'active',
    ]);

    // Table columns should be in the custom_projects table
    $this->assertDatabaseHas('custom_projects', [
        'id' => $resource->id,
        'name' => 'Column Test',
        'status' => 'active',
    ]);
});

test('custom table with meta - meta stored in meta table', function () {
    $resource = ResourceWithCustomTableAndCustomMetaModel::create([
        'name' => 'Meta Test',
        'meta_1' => 'meta value',
    ]);

    // Meta should be in the meta table
    $this->assertDatabaseHas('meta', [
        'metable_id' => $resource->id,
        'metable_type' => ResourceWithCustomTableAndCustomMetaModel::class,
        'key' => 'meta_1',
        'value' => 'meta value',
    ]);
});
