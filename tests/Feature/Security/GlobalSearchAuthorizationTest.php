<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\GlobalSearch;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

/**
 * Searchable test resource used to verify per-resource viewAny gating.
 */
class SecuritySearchModel extends Resource
{
    public static $singularName = 'SecuritySearch';

    public static ?string $slug = 'securitysearch';

    public static string $type = 'SecuritySearch';

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

    public function title()
    {
        return $this->title;
    }
}

beforeEach(function () {
    config(['aura.features.global_search' => true]);

    Aura::fake();
    Aura::registerResources([SecuritySearchModel::class]);
    Aura::setModel(new SecuritySearchModel);
});

test('global search returns a resource the user is allowed to view', function () {
    // Control: super admin can view any resource.
    $this->actingAs(createSuperAdmin());

    SecuritySearchModel::create(['title' => 'Secret Needle Alpha']);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Secret Needle Alpha')
        ->assertSee('Secret Needle Alpha');
});

test('global search hides a resource the user cannot viewAny', function () {
    // Limited admin (Editor role) has no viewAny permission for this resource.
    $this->actingAs(createAdmin());

    SecuritySearchModel::create(['title' => 'Secret Needle Beta']);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Secret Needle Beta')
        ->assertDontSee('Secret Needle Beta');
});

test('global search hides users when the current user cannot view users', function () {
    // Build a role with viewAny-user explicitly denied, then search by email.
    $this->actingAs($admin = createAdmin());

    $admin->roles->first()->update([
        'permissions' => ['view-user' => false, 'viewAny-user' => false],
    ]);

    // Refresh cached roles/permissions.
    Cache::flush();

    $needle = 'hidden-user-'.uniqid().'@example.com';

    User::factory()->create([
        'name' => 'Hidden Person',
        'email' => $needle,
    ]);

    Livewire::test(GlobalSearch::class)
        ->set('search', $needle)
        ->assertDontSee($needle);
});
