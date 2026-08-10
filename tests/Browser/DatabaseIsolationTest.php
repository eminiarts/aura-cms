<?php

use Illuminate\Support\Facades\DB;

test('browser database setup migrates and persists inside the current test transaction', function () {
    $actor = createSuperAdmin();
    DB::table('users')->where('id', $actor->id)->update([
        'email' => 'browser-database-isolation@example.test',
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'browser-database-isolation@example.test',
    ]);
});

test('browser database setup rolls the prior test transaction back', function () {
    $this->assertDatabaseMissing('users', [
        'email' => 'browser-database-isolation@example.test',
    ]);

    $actor = createSuperAdmin();

    expect($actor->exists)->toBeTrue();

    if (config('aura.teams')) {
        expect($actor->currentTeam)->not->toBeNull();
    } else {
        expect($actor->currentTeam)->toBeNull();
    }
});
