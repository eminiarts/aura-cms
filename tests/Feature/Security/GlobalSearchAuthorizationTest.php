<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\GlobalSearch;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

test('global search requires authentication on mount and every hydration', function () {
    Livewire::test(GlobalSearch::class)->assertForbidden();

    $this->actingAs(createSuperAdmin());
    $search = Livewire::test(GlobalSearch::class);
    auth()->logout();

    $search->set('search', 'anything')->assertForbidden();
});

test('global search filters each record even when viewAny is allowed', function () {
    $this->actingAs($admin = createAdmin());
    $admin->roles->first()->update([
        'permissions' => [
            'viewAny-securitysearch' => true,
            'view-securitysearch' => false,
        ],
    ]);
    Cache::flush();

    SecuritySearchModel::create(['title' => 'Record-level secret']);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Record-level secret')
        ->assertDontSee('Record-level secret');
});

test('global search reauthorizes every result on a second Livewire request', function () {
    $this->actingAs(createSuperAdmin());
    $denyRecord = false;
    Gate::before(function ($user, string $ability, array $arguments) use (&$denyRecord): ?bool {
        return $denyRecord
            && $ability === 'view'
            && ($arguments[0] ?? null) instanceof SecuritySearchModel
                ? false
                : null;
    });
    SecuritySearchModel::create(['title' => 'Fresh authorization needle']);

    $search = Livewire::test(GlobalSearch::class)
        ->set('search', 'Fresh authorization')
        ->assertSee('Fresh authorization needle');

    $denyRecord = true;

    $search->set('search', 'Fresh authorization needle')
        ->assertDontSee('Fresh authorization needle');
});

test('global search fails closed for a non-Eloquent authenticated actor', function () {
    Auth::setUser(new GenericUser([
        'id' => 1,
        'name' => 'External Actor',
        'email' => 'external-actor@example.test',
        'password' => 'password',
    ]));

    $globalSearch = new GlobalSearch;
    $globalSearch->search = 'external';

    expect(fn () => $globalSearch->getSearchResultsProperty())
        ->toThrow(HttpException::class);
});
