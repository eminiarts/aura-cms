<?php

use Eminiarts\Aura\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

uses()->group('current');

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

it('generates the correct files for a posttype', function () {
    Storage::fake('app', [
                'driver' => 'local',
                'root' => app_path(),
            ]);


    Artisan::call('aura:posttype', [
        'name' => 'TestPosttype',
    ]);

    dd(Storage::disk('app')->allFiles());


    // assert app/Aura/Resources/TestPosttype.php exists
    $this->assertTrue(Storage::disk('app')->exists('Aura/Resources/TestPosttype.php'));
});
