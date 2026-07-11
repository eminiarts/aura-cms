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

    public static function getFields()
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

beforeEach(function () {
    Aura::fake();
    Aura::registerResources([SecurityBulkModel::class]);
    Aura::setModel(new SecurityBulkModel);
    Cache::clear();
});

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
