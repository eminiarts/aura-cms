<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Illuminate\Support\Facades\Cache;

use function Pest\Livewire\livewire;

/**
 * Test resource that declares a single legitimate bulk action.
 */
class SecurityBulkModel extends Resource
{
    public array $bulkActions = [
        'deleteSelected' => 'Delete',
    ];

    public static $singularName = 'SecurityBulk';

    public static ?string $slug = 'securitybulk';

    public static string $type = 'SecurityBulk';

    public function deleteSelected($ids = null)
    {
        // Per-item destructive action invoked by bulkAction().
        $this->delete();
    }

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'searchable' => true,
                'slug' => 'title',
            ],
        ];
    }
}

class SecurityBulkDateModel extends SecurityBulkModel
{
    public static ?string $slug = 'bulkdate';

    public static string $type = 'BulkDate';

    public static function getFields(): array
    {
        return [...parent::getFields(), [
            'name' => 'Published at',
            'slug' => 'published_at',
            'type' => 'Aura\\Base\\Fields\\Datetime',
            'on_index' => false,
        ]];
    }
}

beforeEach(function () {
    Aura::fake();
    Aura::registerResources([SecurityBulkModel::class]);
    Aura::setModel(new SecurityBulkModel);
    Cache::clear();
});

test('select-all bulk deletion respects valueless date filters', function (string $operator, bool $deleteDated) {
    $this->actingAs(createSuperAdmin());
    Aura::registerResources([SecurityBulkDateModel::class]);
    Aura::setModel(new SecurityBulkDateModel);

    $undated = SecurityBulkDateModel::create(['title' => 'Undated']);
    $dated = SecurityBulkDateModel::create([
        'title' => 'Dated',
        'published_at' => '2026-09-10 09:00:00',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $undated])
        ->call('addFilterGroup')
        ->set('filters.custom.0.filters.0.name', 'published_at')
        ->set('filters.custom.0.filters.0.operator', $operator)
        ->set('filters.custom.0.filters.0.value', '')
        ->call('selectAll')
        ->call('bulkAction', 'deleteSelected')
        ->assertHasNoErrors();

    expect(SecurityBulkDateModel::find($deleteDated ? $undated->id : $dated->id))->not->toBeNull()
        ->and(SecurityBulkDateModel::find($deleteDated ? $dated->id : $undated->id))->toBeNull();
})->with([
    ['date_is_empty', false],
    ['date_is_not_empty', true],
]);

test('bulkAction rejects an action that is not in the declared allowlist', function () {
    // 'delete' is a real method on the model but is NOT a declared bulk action.
    $this->actingAs(createSuperAdmin());

    SecurityBulkModel::create(['title' => 'Keep me 1']);
    SecurityBulkModel::create(['title' => 'Keep me 2']);

    expect(SecurityBulkModel::count())->toBe(2);

    $model = SecurityBulkModel::first();
    $ids = SecurityBulkModel::pluck('id')->toArray();

    livewire(Table::class, ['query' => null, 'model' => $model])
        ->set('selected', $ids)
        ->call('bulkAction', 'delete')
        ->assertStatus(403);

    // Arbitrary method invocation blocked: records untouched.
    expect(SecurityBulkModel::count())->toBe(2);
});

test('bulkAction blocks a declared action the user is not authorized for', function () {
    // Limited admin (Editor role) has no delete permission for this resource.
    $this->actingAs(createAdmin());

    SecurityBulkModel::create(['title' => 'Protected 1']);
    SecurityBulkModel::create(['title' => 'Protected 2']);

    expect(SecurityBulkModel::count())->toBe(2);

    $model = SecurityBulkModel::first();
    $ids = SecurityBulkModel::pluck('id')->toArray();

    livewire(Table::class, ['query' => null, 'model' => $model])
        ->set('selected', $ids)
        ->call('bulkAction', 'deleteSelected')
        ->assertStatus(403);

    // Authorization failed: nothing deleted.
    expect(SecurityBulkModel::count())->toBe(2);
});

test('bulkAction runs a declared action for an authorized user', function () {
    // Control: super admin passes both the allowlist and the policy check.
    $this->actingAs(createSuperAdmin());

    SecurityBulkModel::create(['title' => 'Delete me 1']);
    SecurityBulkModel::create(['title' => 'Delete me 2']);

    expect(SecurityBulkModel::count())->toBe(2);

    $model = SecurityBulkModel::first();
    $ids = SecurityBulkModel::pluck('id')->toArray();

    livewire(Table::class, ['query' => null, 'model' => $model])
        ->set('selected', $ids)
        ->call('bulkAction', 'deleteSelected')
        ->assertHasNoErrors();

    expect(SecurityBulkModel::count())->toBe(0);
});

test('bulkAction fails closed when a forged id is mixed into an otherwise valid selection', function () {
    $this->actingAs(createSuperAdmin());

    $keep = SecurityBulkModel::create(['title' => 'Keep me']);
    $delete = SecurityBulkModel::create(['title' => 'Delete me']);

    $model = SecurityBulkModel::first();

    livewire(Table::class, ['query' => null, 'model' => $model])
        ->set('selected', [(string) $delete->id, '999999999'])
        ->call('bulkAction', 'deleteSelected')
        ->assertHasErrors(['selected']);

    expect(SecurityBulkModel::find($keep->id))->not->toBeNull()
        ->and(SecurityBulkModel::find($delete->id))->not->toBeNull();
});

test('select-all ID endpoints respect the active table filters', function () {
    $this->actingAs(createSuperAdmin());

    $visible = SecurityBulkModel::create(['title' => 'Visible row']);
    $hidden = SecurityBulkModel::create(['title' => 'Hidden row']);

    $component = livewire(Table::class, ['query' => null, 'model' => $visible])
        ->call('addFilterGroup')
        ->set('filters.custom.0.filters.0.name', 'title')
        ->set('filters.custom.0.filters.0.operator', 'is')
        ->set('filters.custom.0.filters.0.value', 'Visible row')
        ->assertViewHas('rows', fn ($rows) => $rows->total() === 1 && $rows->items()[0]->is($visible));

    expect($component->instance()->allTableRows())
        ->toEqualCanonicalizing([$visible->id]);

    expect($component->instance()->getAllTableRows())
        ->toEqualCanonicalizing([$visible->id]);

    $component->call('selectAll');

    expect($component->instance()->getSelectedRowsQueryProperty()->pluck('id')->all())
        ->toEqualCanonicalizing([$visible->id])
        ->not->toContain($hidden->id);
});

test('select-all bulk action only mutates rows in the active table filters', function () {
    $this->actingAs(createSuperAdmin());

    $visible = SecurityBulkModel::create(['title' => 'Visible row']);
    $hidden = SecurityBulkModel::create(['title' => 'Hidden row']);

    livewire(Table::class, ['query' => null, 'model' => $visible])
        ->call('addFilterGroup')
        ->set('filters.custom.0.filters.0.name', 'title')
        ->set('filters.custom.0.filters.0.operator', 'is')
        ->set('filters.custom.0.filters.0.value', 'Visible row')
        ->assertViewHas('rows', fn ($rows) => $rows->total() === 1 && $rows->items()[0]->is($visible))
        ->call('selectAll')
        ->call('bulkAction', 'deleteSelected')
        ->assertHasNoErrors();

    expect(SecurityBulkModel::find($visible->id))->toBeNull()
        ->and(SecurityBulkModel::find($hidden->id))->not->toBeNull();
});
