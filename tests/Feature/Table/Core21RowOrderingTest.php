<?php

use Aura\Base\Contracts\DeclaresTableParentScopes;
use Aura\Base\Contracts\DeclaresTableRowOrdering;
use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Aura\Base\Table\TableParentScope;
use Aura\Base\Table\TableQueryState;
use Aura\Base\Table\TableRowOrdering;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Schema::create('core21_ordered_records', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('category')->nullable();
        $table->integer('position');
        $table->unsignedBigInteger('parent_id')->nullable();
        $table->foreignId('user_id');
        $table->foreignId('team_id')->nullable();
        $table->timestamps();
    });

    Schema::create('core21_keyed_ordered_records', function (Blueprint $table): void {
        $table->string('record_key')->primary();
        $table->string('name');
        $table->integer('position');
        $table->foreignId('user_id');
        $table->foreignId('team_id')->nullable();
        $table->timestamps();
    });

    Aura::fake();
    Aura::setModel(new Core21OrderedResource);
});

afterEach(function () {
    Schema::dropIfExists('core21_ordered_records');
    Schema::dropIfExists('core21_keyed_ordered_records');
});

class Core21OrderedResource extends Resource implements DeclaresTableParentScopes, DeclaresTableRowOrdering
{
    public static $customTable = true;

    public static ?string $slug = 'core-21-ordered';

    public static string $type = 'Core21Ordered';

    public static bool $usesMeta = false;

    protected $fillable = ['name', 'category', 'position', 'parent_id', 'user_id', 'team_id'];

    protected $table = 'core21_ordered_records';

    public function defaultTableSort(): string
    {
        return 'position';
    }

    public function defaultTableSortDirection(): string
    {
        return 'asc';
    }

    public static function getFields(): array
    {
        return [
            ['name' => 'Name', 'slug' => 'name', 'type' => 'Aura\\Base\\Fields\\Text'],
            ['name' => 'Category', 'slug' => 'category', 'type' => 'Aura\\Base\\Fields\\Text'],
            ['name' => 'Position', 'slug' => 'position', 'type' => 'Aura\\Base\\Fields\\Number'],
        ];
    }

    public function tableParentScopes(): array
    {
        return [
            TableParentScope::foreignKey(
                key: 'parent',
                parentResource: self::class,
                foreignKey: 'parent_id',
            ),
        ];
    }

    public function tableRowOrdering(): TableRowOrdering
    {
        return TableRowOrdering::make(column: 'position', direction: 'asc');
    }
}

class Core21MissingOrderColumnResource extends Core21OrderedResource
{
    public static string $type = 'Core21MissingOrderColumn';

    public function tableRowOrdering(): TableRowOrdering
    {
        return TableRowOrdering::make(column: 'not_physical', direction: 'asc');
    }
}

class Core21KeyedOrderedResource extends Resource implements DeclaresTableRowOrdering
{
    public static $customTable = true;

    public $incrementing = false;

    public static ?string $slug = 'core-21-keyed-ordered';

    public static string $type = 'Core21KeyedOrdered';

    public static bool $usesMeta = false;

    protected $fillable = ['record_key', 'name', 'position', 'user_id', 'team_id'];

    protected $keyType = 'string';

    protected $primaryKey = 'record_key';

    protected $table = 'core21_keyed_ordered_records';

    public function defaultTableSort(): string
    {
        return 'position';
    }

    public function defaultTableSortDirection(): string
    {
        return 'asc';
    }

    public static function getFields(): array
    {
        return [
            ['name' => 'Name', 'slug' => 'name', 'type' => 'Aura\\Base\\Fields\\Text'],
            ['name' => 'Position', 'slug' => 'position', 'type' => 'Aura\\Base\\Fields\\Number'],
        ];
    }

    public function tableRowOrdering(): TableRowOrdering
    {
        return TableRowOrdering::make(column: 'position');
    }
}

class Core21DeniedOrderPolicy
{
    public function update(User $user, Core21OrderedResource $record): bool
    {
        return $record->name !== 'Denied';
    }
}

function core21OrderedRecord(
    string $name,
    int $position,
    string $category = 'visible',
    ?int $parentId = null,
): Core21OrderedResource {
    return Core21OrderedResource::create([
        'name' => $name,
        'category' => $category,
        'position' => $position,
        'parent_id' => $parentId,
        'user_id' => test()->user->id,
        ...config('aura.teams') ? ['team_id' => test()->user->current_team_id] : [],
    ]);
}

test('ordered list renders the drag capability only for its configured effective sort', function () {
    core21OrderedRecord('First', 10);

    livewire(Table::class, ['model' => new Core21OrderedResource])
        ->assertSeeHtml('wire:sort="moveTableRow"')
        ->call('sortBy', 'name')
        ->assertDontSeeHtml('wire:sort="moveTableRow"');

    livewire(Table::class, ['model' => new Resource])
        ->assertDontSeeHtml('wire:sort="moveTableRow"');
});

test('exact current page permutation persists order slots and preserves outside page rows', function () {
    $records = collect(range(1, 6))->map(fn (int $position): Core21OrderedResource => core21OrderedRecord(
        'Record '.$position,
        $position * 10,
    ));

    $component = livewire(Table::class, [
        'model' => new Core21OrderedResource,
        'settings' => ['per_page' => 3],
    ]);

    $component->call('reorderTableRows', [
        $records[2]->id,
        $records[0]->id,
        $records[1]->id,
    ])->assertHasNoErrors();

    expect(Core21OrderedResource::query()->orderBy('position')->pluck('id')->all())->toBe([
        $records[2]->id,
        $records[0]->id,
        $records[1]->id,
        $records[3]->id,
        $records[4]->id,
        $records[5]->id,
    ])->and($records[3]->fresh()->position)->toBe(40);

    $component->call('reorderTableRows', [
        $records[2]->id,
        $records[0]->id,
        $records[1]->id,
    ])->assertHasNoErrors();
});

test('filtered pagination reorders only the authoritative filtered page', function () {
    $first = core21OrderedRecord('First', 10, 'keep');
    $hidden = core21OrderedRecord('Hidden', 20, 'hide');
    $second = core21OrderedRecord('Second', 30, 'keep');
    $third = core21OrderedRecord('Third', 40, 'keep');

    $component = livewire(Table::class, [
        'model' => new Core21OrderedResource,
        'settings' => ['per_page' => 2],
    ])->set('filters.custom', [[
        'filters' => [[
            'name' => 'category',
            'operator' => 'is',
            'value' => 'keep',
        ]],
    ]]);

    $component->call('reorderTableRows', [$second->id, $first->id])->assertHasNoErrors();

    expect($hidden->fresh()->position)->toBe(20)
        ->and($third->fresh()->position)->toBe(40)
        ->and(Core21OrderedResource::query()->orderBy('position')->pluck('id')->all())->toBe([
            $second->id,
            $hidden->id,
            $first->id,
            $third->id,
        ]);
});

test('parent scoped ordering cannot mutate rows outside the declared parent', function () {
    $parent = core21OrderedRecord('Parent', 1);
    $otherParent = core21OrderedRecord('Other parent', 2);
    $first = core21OrderedRecord('First child', 10, parentId: $parent->id);
    $second = core21OrderedRecord('Second child', 20, parentId: $parent->id);
    $foreign = core21OrderedRecord('Other child', 15, parentId: $otherParent->id);
    $state = TableQueryState::fromArray([
        'v' => 1,
        'parent' => ['scope' => 'parent', 'id' => $parent->id],
    ]);

    livewire(Table::class, [
        'model' => new Core21OrderedResource,
        'tableState' => $state->toQueryString(),
    ])->call('reorderTableRows', [$second->id, $first->id])
        ->assertHasNoErrors();

    expect($foreign->fresh()->position)->toBe(15)
        ->and(Core21OrderedResource::query()
            ->where('parent_id', $parent->id)
            ->orderBy('position')
            ->pluck('id')
            ->all())->toBe([$second->id, $first->id]);
});

test('row ordering rejects stale duplicate forged denied and mismatched sort state', function () {
    $first = core21OrderedRecord('First', 10);
    $denied = core21OrderedRecord('Denied', 20);
    $third = core21OrderedRecord('Third', 30);

    livewire(Table::class, ['model' => new Core21OrderedResource])
        ->call('reorderTableRows', [$first->id, $first->id, $third->id])
        ->assertStatus(422);

    livewire(Table::class, ['model' => new Core21OrderedResource])
        ->call('reorderTableRows', [$first->id, $denied->id, 'forged'])
        ->assertStatus(422);

    livewire(Table::class, ['model' => new Core21OrderedResource])
        ->call('sortBy', 'name')
        ->call('reorderTableRows', [$first->id, $denied->id, $third->id])
        ->assertStatus(422);

    Gate::policy(Core21OrderedResource::class, Core21DeniedOrderPolicy::class);

    livewire(Table::class, ['model' => new Core21OrderedResource])
        ->call('reorderTableRows', [$denied->id, $first->id, $third->id])
        ->assertForbidden();

    expect(Core21OrderedResource::query()->orderBy('position')->pluck('id')->all())->toBe([
        $first->id,
        $denied->id,
        $third->id,
    ]);
});

test('row ordering rejects a concurrently changed page order snapshot', function () {
    $first = core21OrderedRecord('First', 10);
    $second = core21OrderedRecord('Second', 20);
    $component = livewire(Table::class, ['model' => new Core21OrderedResource]);

    Core21OrderedResource::query()->whereKey($first->id)->update(['position' => 20]);
    Core21OrderedResource::query()->whereKey($second->id)->update(['position' => 10]);

    $component->call('reorderTableRows', [$second->id, $first->id])
        ->assertStatus(409);
});

test('row ordering rejects duplicate slots on the page and across the scoped result', function () {
    $first = core21OrderedRecord('First', 10);
    $second = core21OrderedRecord('Second', 20);
    $outsidePage = core21OrderedRecord('Outside page', 20);

    livewire(Table::class, [
        'model' => new Core21OrderedResource,
        'settings' => ['per_page' => 2],
    ])->call('reorderTableRows', [$second->id, $first->id])
        ->assertStatus(409);

    livewire(Table::class, ['model' => new Core21OrderedResource])
        ->call('reorderTableRows', [$first->id, $second->id, $outsidePage->id])
        ->assertStatus(409);

    expect(Core21OrderedResource::query()->orderBy('id')->pluck('position')->all())
        ->toBe([10, 20, 20]);
});

test('row ordering uses the resource primary key for its locked page snapshot', function () {
    $records = collect([
        ['record_key' => 'first-key', 'name' => 'First', 'position' => 10],
        ['record_key' => 'second-key', 'name' => 'Second', 'position' => 20],
    ])->map(fn (array $attributes): Core21KeyedOrderedResource => Core21KeyedOrderedResource::create([
        ...$attributes,
        'user_id' => test()->user->id,
        ...config('aura.teams') ? ['team_id' => test()->user->current_team_id] : [],
    ]));

    livewire(Table::class, ['model' => new Core21KeyedOrderedResource])
        ->call('reorderTableRows', ['second-key', 'first-key'])
        ->assertHasNoErrors();

    expect(Core21KeyedOrderedResource::query()->orderBy('position')->pluck('record_key')->all())
        ->toBe([$records[1]->getKey(), $records[0]->getKey()]);
});

test('invalid or absent physical ordering declarations expose no mutation', function () {
    core21OrderedRecord('First', 10);

    livewire(Table::class, ['model' => new Core21MissingOrderColumnResource])
        ->assertDontSeeHtml('wire:sort="moveTableRow"')
        ->call('reorderTableRows', [1])
        ->assertStatus(422);

    livewire(Table::class, ['model' => new Resource])
        ->call('reorderTableRows', [1])
        ->assertStatus(422);
});
