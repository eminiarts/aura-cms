<?php

use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Models\Post;
use Eminiarts\Aura\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

uses()->group('current');

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));


it('can create a user with all required fields', function () {
    $this->artisan('aura:user')
         ->expectsQuestion('What is your name?', 'John Doe')
         ->expectsQuestion('What is your email?', 'johndoe@example.com')
         ->expectsQuestion('What is your password?', 'password')
         ->assertExitCode(0);

    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'johndoe@example.com',
    ]);

    $this->assertDatabaseHas('teams', [
        'name' => 'John Doe',
        'personal_team' => 1,
    ]);

    $user = User::where('email', 'johndoe@example.com')->first();
    $team = DB::table('teams')->where('user_id', $user->id)->first();

    expect($user->current_team_id)->toEqual($team->id);

    expect($user->fields['roles'])->toContain(1);
});
