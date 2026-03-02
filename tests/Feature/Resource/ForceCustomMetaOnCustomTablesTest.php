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

class ForceCustomMetaOnCustomTablesModel extends Resource
{
    public static $customTable = true;

    public static $singularName = 'Project';

    public static ?string $slug = 'project';

    public static string $type = 'Project';

    protected $casts = [
        'options' => 'array',
        'enabled' => 'boolean',
    ];

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

test('custom Table is using custom meta', function () {
    $this->withoutExceptionHandling();

    $resource = ForceCustomMetaOnCustomTablesModel::create([
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

    $meta = DB::table('meta')->where('metable_id', $resource->id)->where('metable_type', ForceCustomMetaOnCustomTablesModel::class)->get();

    $meta1 = $meta->where('key', 'meta_1')->first();
    $meta2 = $meta->where('key', 'meta_2')->first();

    expect($meta1->value)->toBe('first');
    expect($meta2->value)->toBe('second');
});

test('custom table meta fields are not in the main table', function () {
    $resource = ForceCustomMetaOnCustomTablesModel::create([
        'name' => 'Test',
        'meta_1' => 'meta value',
    ]);

    $db = DB::table('custom_projects')->where('id', $resource->id)->first();

    // meta_1 should not be a column in the custom_projects table
    expect(property_exists($db, 'meta_1'))->toBeFalse();
});

test('custom table stores fillable fields in table', function () {
    $resource = new ForceCustomMetaOnCustomTablesModel;
    $fillable = $resource->getFillable();

    // Fillable should include table columns
    expect($fillable)->toContain('name');
    expect($fillable)->toContain('status');
    expect($fillable)->toContain('enabled');
    expect($fillable)->toContain('options');
});

test('custom table meta fields can be accessed via attribute', function () {
    $resource = ForceCustomMetaOnCustomTablesModel::create([
        'name' => 'Attribute Test',
        'meta_1' => 'accessible meta',
    ]);

    // Meta fields should be accessible as attributes
    expect($resource->meta_1)->toBe('accessible meta');
});

test('custom table with meta fields can be queried', function () {
    ForceCustomMetaOnCustomTablesModel::create([
        'name' => 'Project A',
        'status' => 'active',
        'meta_1' => 'value1',
    ]);

    ForceCustomMetaOnCustomTablesModel::create([
        'name' => 'Project B',
        'status' => 'inactive',
        'meta_1' => 'value2',
    ]);

    // Query by table column should work
    $active = ForceCustomMetaOnCustomTablesModel::where('status', 'active')->get();
    expect($active)->toHaveCount(1);
    expect($active->first()->name)->toBe('Project A');
});
