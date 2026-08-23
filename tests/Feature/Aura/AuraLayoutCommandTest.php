<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->sourcePath = dirname(__DIR__, 3).'/resources/views/components/layout/app.blade.php';
    $this->destinationPath = resource_path('views/vendor/aura/components/layout/app.blade.php');

    if (File::exists($this->destinationPath)) {
        File::delete($this->destinationPath);
    }
});

afterEach(function () {
    if (File::exists($this->destinationPath)) {
        File::delete($this->destinationPath);
    }
});

it('copies the packaged layout into the application view overrides', function () {
    $this->artisan('aura:layout')
        ->expectsOutput('Aura layout file copied successfully.')
        ->assertExitCode(0);

    expect(File::exists($this->destinationPath))->toBeTrue();
    expect(File::get($this->destinationPath))->toBe(File::get($this->sourcePath));
});
