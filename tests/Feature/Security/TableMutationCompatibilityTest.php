<?php

use Aura\Base\BaseResource;
use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

use function Pest\Livewire\livewire;

class Core05SoftDeleteResource extends Resource
{
    use SoftDeletes;

    public array $actions = [
        'delete' => [
            'label' => 'Delete',
        ],
        'restore' => [
            'label' => 'Restore',
            'trashed' => 'only',
        ],
        'forceDelete' => [
            'label' => 'Force delete',
            'trashed' => 'only',
        ],
        'force_delete' => [
            'label' => 'Force delete without ability',
            'trashed' => 'only',
        ],
        'purge' => [
            'label' => 'Purge',
            'ability' => 'forceDelete',
            'trashed' => 'only',
        ],
    ];

    public static ?string $slug = 'core05-soft-delete';

    public static string $type = 'Core05SoftDelete';

    public function force_delete(): void
    {
        $this->forceDelete();
    }

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
        ];
    }

    public function purge(): void
    {
        $this->forceDelete();
    }
}

class Core05SoftDeletePolicy
{
    public static bool $allowForceDelete = false;

    public function delete(User $user, Core05SoftDeleteResource $resource): bool
    {
        return true;
    }

    public function forceDelete(User $user, Core05SoftDeleteResource $resource): bool
    {
        return self::$allowForceDelete;
    }

    public function restore(User $user, Core05SoftDeleteResource $resource): bool
    {
        return true;
    }
}

class Core05BaseTableResource extends BaseResource
{
    public array $actions = [
        'markReviewed' => [
            'label' => 'Mark reviewed',
            'ability' => 'update',
        ],
    ];

    public static $customTable = true;

    public static ?string $slug = 'core05-base-table';

    public static string $type = 'Core05BaseTable';

    public static bool $usesMeta = false;

    protected $baseFillable = ['title', 'content', 'status'];

    protected $fillable = ['title', 'content', 'status'];

    protected $table = 'core05_base_table_resources';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
            [
                'name' => 'Status',
                'slug' => 'status',
                'type' => 'Aura\\Base\\Fields\\Status',
                'options' => [
                    [
                        'key' => 'draft',
                        'value' => 'Draft',
                        'color' => 'gray',
                    ],
                    [
                        'key' => 'reviewed',
                        'value' => 'Reviewed',
                        'color' => 'green',
                    ],
                ],
            ],
        ];
    }

    public function indexQuery(Builder $query, ?Table $table = null): Builder
    {
        return $query->where('title', '!=', 'Excluded base row');
    }

    public function markReviewed(): void
    {
        $this->content = 'reviewed-by-action';
        $this->save();
    }

    public function resolveFieldValue(string $slug, mixed $meta = null): mixed
    {
        return $this->getAttribute($slug);
    }
}

class Core05BaseTablePolicy
{
    public function update(User $user, Core05BaseTableResource $resource): bool
    {
        return true;
    }
}

beforeEach(function () {
    Core05SoftDeletePolicy::$allowForceDelete = false;
    Schema::dropIfExists('core05_base_table_resources');
    Schema::create('core05_base_table_resources', function (Blueprint $table): void {
        $table->id();
        $table->string('title');
        $table->text('content')->nullable();
        $table->string('status')->nullable();
        $table->timestamps();
    });

    Aura::fake();
    Aura::registerResources([
        Core05SoftDeleteResource::class,
        Core05BaseTableResource::class,
    ]);
    Gate::policy(Core05SoftDeleteResource::class, Core05SoftDeletePolicy::class);
    Gate::policy(Core05BaseTableResource::class, Core05BaseTablePolicy::class);
});

test('soft-deleted table rows can be restored through an explicitly trashed action descriptor', function () {
    $this->actingAs(createAdmin());

    $resource = Core05SoftDeleteResource::create(['title' => 'Restore me']);

    livewire(Table::class, ['query' => null, 'model' => new Core05SoftDeleteResource])
        ->call('action', ['action' => 'delete', 'id' => $resource->id])
        ->assertHasNoErrors();

    expect(Core05SoftDeleteResource::find($resource->id))->toBeNull()
        ->and(Core05SoftDeleteResource::withTrashed()->find($resource->id))->not->toBeNull();

    livewire(Table::class, ['query' => null, 'model' => new Core05SoftDeleteResource])
        ->call('action', ['action' => 'restore', 'id' => $resource->id])
        ->assertHasNoErrors();

    expect(Core05SoftDeleteResource::find($resource->id))->not->toBeNull();
});

test('force delete uses its exact policy ability instead of ordinary delete permission', function () {
    $this->actingAs(createAdmin());

    $resource = Core05SoftDeleteResource::create(['title' => 'Do not purge']);
    $resource->delete();

    livewire(Table::class, ['query' => null, 'model' => new Core05SoftDeleteResource])
        ->call('action', ['action' => 'forceDelete', 'id' => $resource->id])
        ->assertStatus(403);

    livewire(Table::class, ['query' => null, 'model' => new Core05SoftDeleteResource])
        ->call('action', ['action' => 'force_delete', 'id' => $resource->id])
        ->assertStatus(422);

    livewire(Table::class, ['query' => null, 'model' => new Core05SoftDeleteResource])
        ->call('action', ['action' => 'purge', 'id' => $resource->id])
        ->assertStatus(403);

    expect(Core05SoftDeleteResource::withTrashed()->whereKey($resource->id)->exists())->toBeTrue();
});

test('an authorized force delete permanently removes an explicitly trashed row', function () {
    $this->actingAs(createSuperAdmin());
    Core05SoftDeletePolicy::$allowForceDelete = true;

    $resource = Core05SoftDeleteResource::create(['title' => 'Purge me']);
    $resource->delete();

    livewire(Table::class, ['query' => null, 'model' => new Core05SoftDeleteResource])
        ->call('action', ['action' => 'forceDelete', 'id' => $resource->id])
        ->assertHasNoErrors();

    expect(Core05SoftDeleteResource::withTrashed()->whereKey($resource->id)->exists())->toBeFalse();
});

test('BaseResource custom-table actions retain table mutation support', function () {
    $this->actingAs(createAdmin());

    $resource = Core05BaseTableResource::create([
        'title' => 'Base action target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core05BaseTableResource])
        ->call('action', ['action' => 'markReviewed', 'id' => $resource->id])
        ->assertHasNoErrors();

    expect($resource->fresh()->content)->toBe('reviewed-by-action');
});

test('BaseResource custom-table Kanban updates retain scope and option validation', function () {
    $this->actingAs(createAdmin());

    $resource = Core05BaseTableResource::create([
        'title' => 'Base Kanban target',
        'status' => 'draft',
    ]);
    $excluded = Core05BaseTableResource::create([
        'title' => 'Excluded base row',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core05BaseTableResource])
        ->call('updateCardStatus', $resource->id, 'reviewed')
        ->assertHasNoErrors();

    livewire(Table::class, ['query' => null, 'model' => new Core05BaseTableResource])
        ->call('updateCardStatus', $excluded->id, 'reviewed')
        ->assertNotFound();

    expect($resource->fresh()->status)->toBe('reviewed')
        ->and($excluded->fresh()->status)->toBe('draft');
});
