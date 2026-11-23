<?php

use Aura\Base\Resources\Option;
use Aura\Base\Resources\Team;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    config(['aura.auth.2fa' => false]);

    $this->admin = createSuperAdmin();

    Option::withoutGlobalScopes()->create([
        'name' => config('aura.teams') ? 'team.'.$this->admin->current_team_id.'.settings' : 'settings',
        'value' => ['color-palette' => 'orange', 'interface-density' => 'compact', 'corner-radius' => 'square'],
        ...config('aura.teams') ? ['team_id' => $this->admin->current_team_id] : [],
    ]);

    Cache::flush();
    auth()->logout();
});

test('guest pages use the saved appearance of a single-team install', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('--primary-500: 234 88 12;', false)
        ->assertSee('--aura-panel-padding: 1rem;', false)
        ->assertSee('--aura-control-radius: 0rem;', false);
});

test('guest pages do not show any team appearance when several teams exist', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Only applies with teams.');
    }

    Team::withoutGlobalScopes()->insert(['name' => 'Other team', 'user_id' => $this->admin->id, 'created_at' => now(), 'updated_at' => now()]);

    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('--primary-500: 234 88 12;', false)
        ->assertSee('--aura-panel-padding: 1.25rem;', false)
        ->assertSee('--aura-control-radius: 0.5rem;', false);
});
