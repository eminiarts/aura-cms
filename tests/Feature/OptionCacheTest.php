<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Team;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

function serializedOptionCacheRepository(): Repository
{
    return new Repository(new ArrayStore(serializesValues: true, serializableClasses: false));
}

test('user option survives a serialized cache read in a fresh application container', function () {
    $cache = serializedOptionCacheRepository();
    Cache::swap($cache);

    $user = createSuperAdmin();
    $user->updateOption('recent.records', ['contact:1']);

    expect($user->getOption('recent.records'))->toBe(['contact:1']);

    $this->refreshApplication();
    Cache::swap($cache);
    $this->actingAs($user);

    expect($user->getOption('recent.records'))->toBe(['contact:1']);
});

test('wildcard user options survive serialized cache reads as a collection', function () {
    Cache::swap(serializedOptionCacheRepository());

    $user = createSuperAdmin();
    $user->updateOption('Contact.filters.mine', ['owner' => 'me']);
    $user->updateOption('Contact.filters.open', ['status' => 'open']);

    expect($user->getOption('Contact.filters.*'))
        ->toBeInstanceOf(Collection::class)
        ->all()->toBe([
            'mine' => ['owner' => 'me'],
            'open' => ['status' => 'open'],
        ]);

    expect($user->getOption('Contact.filters.*'))
        ->toBeInstanceOf(Collection::class)
        ->all()->toBe([
            'mine' => ['owner' => 'me'],
            'open' => ['status' => 'open'],
        ]);
});

test('specialized user preference getters survive serialized cache reads', function () {
    Cache::swap(serializedOptionCacheRepository());

    $user = createSuperAdmin();
    $user->updateOption('bookmarks', [['name' => 'Contacts', 'url' => '/contacts']]);
    $user->updateOption('columns.Contact', ['name' => true, 'owner' => false]);
    $user->updateOption('sidebar', ['Resources']);
    $user->updateOption('sidebarToggled', false);

    $readPreferences = fn () => [
        'bookmarks' => $user->getOptionBookmarks(),
        'columns' => $user->getOptionColumns('Contact'),
        'sidebar' => $user->getOptionSidebar(),
        'sidebarToggled' => $user->getOptionSidebarToggled(),
    ];

    $expected = [
        'bookmarks' => [['name' => 'Contacts', 'url' => '/contacts']],
        'columns' => ['name' => true, 'owner' => false],
        'sidebar' => ['Resources'],
        'sidebarToggled' => false,
    ];

    expect($readPreferences())->toBe($expected);
    expect($readPreferences())->toBe($expected);
});

test('team option survives a serialized cache read in a fresh application container', function () {
    $cache = serializedOptionCacheRepository();
    Cache::swap($cache);

    $user = createSuperAdmin();
    $team = $user->currentTeam;
    $team->updateOption('settings', ['theme' => 'dark']);

    expect($team->getOption('settings'))->toBe(['theme' => 'dark']);

    $this->refreshApplication();
    Cache::swap($cache);
    $this->actingAs($user);

    expect($team->getOption('settings'))->toBe(['theme' => 'dark']);
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');

test('updating a team option invalidates Aura option reads', function () {
    Cache::swap(serializedOptionCacheRepository());
    createSuperAdmin();

    Aura::updateOption('settings', ['theme' => 'dark']);
    expect(Aura::getOption('settings'))->toBe(['theme' => 'dark']);

    Aura::updateOption('settings', ['theme' => 'light']);
    expect(Aura::getOption('settings'))->toBe(['theme' => 'light']);
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');

test('updating a global option invalidates Aura option reads', function () {
    Cache::swap(serializedOptionCacheRepository());

    Aura::updateOption('settings', ['theme' => 'dark']);
    expect(Aura::getOption('settings'))->toBe(['theme' => 'dark']);

    Aura::updateOption('settings', ['theme' => 'light']);
    expect(Aura::getOption('settings'))->toBe(['theme' => 'light']);
})->skip(fn () => config('aura.teams'), 'Global option context requires teams-off mode.');

test('user options do not leak between users', function () {
    Cache::swap(serializedOptionCacheRepository());

    $firstUser = createSuperAdmin();
    $firstUser->updateOption('columns.Contact', ['name']);

    $secondUser = config('aura.teams')
        ? soleMemberOf($firstUser->currentTeam)
        : createSuperAdminWithoutTeam();
    $this->actingAs($secondUser);
    $secondUser->updateOption('columns.Contact', ['email']);

    expect($secondUser->getOption('columns.Contact'))->toBe(['email']);

    $this->actingAs($firstUser);
    expect($firstUser->getOption('columns.Contact'))->toBe(['name']);
});

test('user options do not leak between teams', function () {
    Cache::swap(serializedOptionCacheRepository());

    $user = createSuperAdmin();
    $firstTeam = $user->currentTeam;
    $user->updateOption('columns.Contact', ['name']);
    expect($user->getOption('columns.Contact'))->toBe(['name']);

    $secondTeam = Team::factory()->create();
    expect($user->current_team_id)->toBe($secondTeam->id);

    $user->updateOption('columns.Contact', ['email']);
    expect($user->getOption('columns.Contact'))->toBe(['email']);

    expect($user->switchTeam($firstTeam))->toBeTrue();
    expect($user->getOption('columns.Contact'))->toBe(['name']);
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');

test('user option changes invalidate cached wildcard reads', function () {
    Cache::swap(serializedOptionCacheRepository());

    $user = createSuperAdmin();
    $user->updateOption('Contact.filters.mine', ['owner' => 'me']);
    $user->updateOption('Contact.filters.open', ['status' => 'open']);

    expect($user->getOption('Contact.filters.*')->all())->toBe([
        'mine' => ['owner' => 'me'],
        'open' => ['status' => 'open'],
    ]);

    $user->updateOption('Contact.filters.open', ['status' => 'closed']);
    expect($user->getOption('Contact.filters.*')->all())->toBe([
        'mine' => ['owner' => 'me'],
        'open' => ['status' => 'closed'],
    ]);

    $user->deleteOption('Contact.filters.mine');
    expect($user->getOption('Contact.filters.*')->all())->toBe([
        'open' => ['status' => 'closed'],
    ]);
});

test('wildcard team options survive serialized cache reads in a fresh application container', function () {
    $cache = serializedOptionCacheRepository();
    Cache::swap($cache);

    $user = createSuperAdmin();
    $team = $user->currentTeam;
    $team->updateOption('Contact.filters.mine', ['owner' => 'me']);
    $team->updateOption('Contact.filters.open', ['status' => 'open']);

    expect($team->getOption('Contact.filters.*'))
        ->toBeInstanceOf(Collection::class)
        ->all()->toBe([
            'mine' => ['owner' => 'me'],
            'open' => ['status' => 'open'],
        ]);

    $this->refreshApplication();
    Cache::swap($cache);
    $this->actingAs($user);

    expect($team->getOption('Contact.filters.*'))
        ->toBeInstanceOf(Collection::class)
        ->all()->toBe([
            'mine' => ['owner' => 'me'],
            'open' => ['status' => 'open'],
        ]);
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');

test('team option reads use the team instance context', function () {
    Cache::swap(serializedOptionCacheRepository());

    $user = createSuperAdmin();
    $firstTeam = $user->currentTeam;
    $firstTeam->updateOption('settings', ['theme' => 'red']);

    $secondTeam = Team::factory()->create();
    $secondTeam->updateOption('settings', ['theme' => 'blue']);

    $firstTeam->clearCachedOption('settings');

    expect($firstTeam->getOption('settings'))->toBe(['theme' => 'red'])
        ->and($secondTeam->getOption('settings'))->toBe(['theme' => 'blue']);
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');

test('user option reads use the user and team instance context', function () {
    Cache::swap(serializedOptionCacheRepository());

    $firstUser = createSuperAdmin();
    $firstUser->updateOption('columns.Contact', ['name']);

    $secondTeam = Team::factory()->createQuietly(['user_id' => $firstUser->id]);
    $secondUser = soleMemberOf($secondTeam);
    $this->actingAs($secondUser);

    $firstUser->clearCachedOption('columns.Contact');

    expect($firstUser->getOption('columns.Contact'))->toBe(['name']);
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');
