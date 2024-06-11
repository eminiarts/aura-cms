<?php

use Aura\Base\Livewire\Table\Table;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resource;
use Aura\Base\Resources\Post;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(function () {
    Schema::dropIfExists('custom_projects');
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

    $meta = DB::table('post_meta')->where('post_id', $resource->id)->get();

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

    $meta = DB::table('post_meta')->insert([
        'post_id' => $resource->id,
        'key' => 'name',
        'value' => 'Test Post 1',
    ]);

    $resource = ResourceWithCustomTableWithoutFillableModel::find($resource->id);

    expect($resource->name)->not->toBe('Test Post 1');
    expect($resource->name)->toBeNull();

    expect($resource->display('name'))->toBeNull();

});
