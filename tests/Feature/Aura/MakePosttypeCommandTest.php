<?php

// uses()->group('current');

use Eminiarts\Aura\Resources\User;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);
// uses(FilesystemServiceProvider::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('generates the correct files for a posttype', function () {
    Storage::fake('app');

    Artisan::call('aura:posttype', [
        'name' => 'TestPosttype',
    ]);

    // assert app/Aura/Resources/TestPosttype.php exists
    $this->assertTrue(File::exists(app_path('Aura/Resources/TestPosttype.php')));

    // delete app/Aura/Resources/TestPosttype.php
    File::delete(app_path('Aura/Resources/TestPosttype.php'));
});
