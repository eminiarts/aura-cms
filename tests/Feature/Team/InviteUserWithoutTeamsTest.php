<?php

use Aura\Base\Livewire\InviteUser;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->teamsEnabled = config('aura.teams');

    config(['aura.teams' => false]);

    $this->actingAs(User::factory()->create());
});

afterEach(function () {
    config(['aura.teams' => $this->teamsEnabled]);
});

it('aborts the invite user component when teams are disabled', function () {
    Livewire::test(InviteUser::class)
        ->assertStatus(404);
});

it('hides team invitations from navigation when teams are disabled', function () {
    expect(TeamInvitation::getShowInNavigation())->toBeFalse();
});
