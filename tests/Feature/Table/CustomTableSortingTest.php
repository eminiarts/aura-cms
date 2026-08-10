<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Resources\Tag;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Pest\Livewire\livewire;

afterEach(function () {
    Schema::dropIfExists('custom_sort_projects');
    Aura::clear();
});

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Schema::create('custom_sort_projects', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->text('amount')->nullable();
        $table->foreignId('user_id');
        $table->foreignId('team_id')->nullable();
        $table->timestamps();
    });

    Aura::fake();
    Aura::setModel(new CustomTableSortingModel);
});

class CustomTableSortingModel extends Resource
{
    public static $customTable = true;

    public static $singularName = 'Sort Project';

    public static ?string $slug = 'sort-project';

    public static string $type = 'SortProject';

    protected $fillable = [
        'name',
        'amount',
        'user_id',
        'team_id',
        'created_at',
        'updated_at',
    ];

    protected $table = 'custom_sort_projects';

    public static function getFields()
    {
        return [
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'name',
            ],
            [
                'name' => 'Meta 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'meta_1',
            ],
            [
                'name' => 'Amount',
                'type' => 'Aura\\Base\\Fields\\Number',
                'slug' => 'amount',
                'number_type' => 'decimal',
                'precision' => 6,
                'scale' => 2,
            ],
            [
                'name' => 'Tags',
                'slug' => 'tags',
                'type' => 'Aura\\Base\\Fields\\Tags',
                'resource' => 'Aura\\Base\\Resources\\Tag',
                'create' => true,
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
        ];
    }
}

test('physical sqlite number sorting uses a stable primary key tie break in both directions', function () {
    $first = CustomTableSortingModel::create([
        'name' => 'First equivalent',
        'amount' => '2',
        'user_id' => $this->user->id,
        ...config('aura.teams') ? ['team_id' => $this->user->current_team_id] : [],
    ]);
    $second = CustomTableSortingModel::create([
        'name' => 'Second equivalent',
        'amount' => '2',
        'user_id' => $this->user->id,
        ...config('aura.teams') ? ['team_id' => $this->user->current_team_id] : [],
    ]);
    $firstInvalid = CustomTableSortingModel::create([
        'name' => 'First invalid',
        'amount' => '3',
        'user_id' => $this->user->id,
        ...config('aura.teams') ? ['team_id' => $this->user->current_team_id] : [],
    ]);
    $secondInvalid = CustomTableSortingModel::create([
        'name' => 'Second invalid',
        'amount' => '4',
        'user_id' => $this->user->id,
        ...config('aura.teams') ? ['team_id' => $this->user->current_team_id] : [],
    ]);
    DB::table('custom_sort_projects')->where('id', $first->id)->update(['amount' => '2']);
    DB::table('custom_sort_projects')->where('id', $second->id)->update(['amount' => '+002.0']);
    DB::table('custom_sort_projects')->where('id', $firstInvalid->id)->update(['amount' => 'invalid-a']);
    DB::table('custom_sort_projects')->where('id', $secondInvalid->id)->update(['amount' => 'invalid-b']);

    $component = livewire(Table::class, ['query' => null, 'model' => $first]);
    $expected = [$second->id, $first->id, $secondInvalid->id, $firstInvalid->id];

    $component->call('sortBy', 'amount')
        ->assertViewHas('rows', fn ($rows): bool => collect($rows->items())->pluck('id')->all() === $expected);
    $component->call('sortBy', 'amount')
        ->assertViewHas('rows', fn ($rows): bool => collect($rows->items())->pluck('id')->all() === $expected);
});

test('custom table resource can sort by a meta field', function () {
    $projectB = CustomTableSortingModel::create([
        'name' => 'Project B',
        'meta_1' => 'B',
        'user_id' => $this->user->id,
        ...config('aura.teams') ? ['team_id' => $this->user->current_team_id] : [],
    ]);

    $projectA = CustomTableSortingModel::create([
        'name' => 'Project A',
        'meta_1' => 'A',
        'user_id' => $this->user->id,
        ...config('aura.teams') ? ['team_id' => $this->user->current_team_id] : [],
    ]);

    $component = livewire(Table::class, ['query' => null, 'model' => $projectB]);

    $component->call('sortBy', 'meta_1');
    $component->assertViewHas('rows', fn ($rows) => $rows->items()[0]->id === $projectA->id && $rows->items()[1]->id === $projectB->id);

    $query = $component->instance()->rowsQuery();
    expect($query->toSql())->toContain('left join "meta" on "custom_sort_projects"."id" = "meta"."metable_id"');

    $component->call('sortBy', 'meta_1');
    $component->assertViewHas('rows', fn ($rows) => $rows->items()[0]->id === $projectB->id && $rows->items()[1]->id === $projectA->id);
});

test('custom table resource can sort by a taxonomy field', function () {
    $tag1 = Tag::create(['title' => 'Tag 1', 'slug' => 'tag-1']);
    $tag2 = Tag::create(['title' => 'Tag 2', 'slug' => 'tag-2']);
    $tag3 = Tag::create(['title' => 'Tag 3', 'slug' => 'tag-3']);

    $project1 = CustomTableSortingModel::create([
        'name' => 'Project 1',
        'tags' => [$tag1->id, $tag2->id],
        'user_id' => $this->user->id,
        ...config('aura.teams') ? ['team_id' => $this->user->current_team_id] : [],
    ]);

    $project2 = CustomTableSortingModel::create([
        'name' => 'Project 2',
        'tags' => [$tag3->id],
        'user_id' => $this->user->id,
        ...config('aura.teams') ? ['team_id' => $this->user->current_team_id] : [],
    ]);

    $component = livewire(Table::class, ['query' => null, 'model' => $project1]);

    $component->call('sortBy', 'tags');
    $component->assertViewHas('rows', fn ($rows) => $rows->items()[0]->id === $project1->id && $rows->items()[1]->id === $project2->id);

    $query = $component->instance()->rowsQuery();
    expect($query->toSql())->toContain('left join "post_relations" as "pr" on "custom_sort_projects"."id" = "pr"."related_id"');

    $component->call('sortBy', 'tags');
    $component->assertViewHas('rows', fn ($rows) => $rows->items()[0]->id === $project2->id && $rows->items()[1]->id === $project1->id);
});
