<?php

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

    Schema::create('custom_projects_meta', function (Blueprint $table) {
        $table->id();
        $table->foreignId('team_id')->index()->nullable();
        $table->string('key')->nullable();
        $table->longText('value')->nullable();
    });
});

// Create Resource for this test

class ResourceWithCustomTableAndCustomMetaModelMeta extends Meta
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'custom_projects_meta';
}

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

    public function meta()
    {
        return $this->hasMany(ResourceWithCustomTableAndCustomMetaModelMeta::class, 'team_id');
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

    expect($resource->usesCustomMeta())->toBe(true);

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

    $meta = DB::table('custom_projects_meta')->get();

    expect($meta->count())->toBe(2);

    $meta1 = $meta->where('key', 'meta_1')->first();
    $meta2 = $meta->where('key', 'meta_2')->first();

    expect($meta1)->not->toBeNull();
    expect($meta1->value)->toBe('first');

    expect($meta2)->not->toBeNull();
    expect($meta2->value)->toBe('second');

});
