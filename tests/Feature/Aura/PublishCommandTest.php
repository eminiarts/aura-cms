<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->assetPath = public_path('vendor/aura/assets');

    // Clean up before test
    if (File::exists($this->assetPath)) {
        File::deleteDirectory($this->assetPath);
    }
});

afterEach(function () {
    // Clean up after test
    if (File::exists($this->assetPath)) {
        File::deleteDirectory($this->assetPath);
    }
});

it('can publish aura assets', function () {
    $this->artisan('aura:publish')
        ->assertExitCode(0);

    expect(File::exists($this->assetPath))->toBeTrue();
});

it('removes existing assets before publishing', function () {
    // Create a dummy file in assets directory
    File::makeDirectory($this->assetPath, 0755, true);
    File::put($this->assetPath.'/dummy.txt', 'test');

    expect(File::exists($this->assetPath.'/dummy.txt'))->toBeTrue();

    $this->artisan('aura:publish')
        ->assertExitCode(0);

    expect(File::exists($this->assetPath.'/dummy.txt'))->toBeFalse();
});
