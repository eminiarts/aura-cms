<?php

use Illuminate\Support\Facades\File;
use function Pest\Laravel\artisan;

uses()->group('aura', 'command');

uses()->beforeEach(function () {
    // Register service providers
    $this->app->register(\Aura\Base\AuraServiceProvider::class);
    $this->app->register(\Lab404\Impersonate\ImpersonateServiceProvider::class);
    $this->app->register(\Livewire\LivewireServiceProvider::class);

    // Add impersonate middleware
    $this->app->make(\Illuminate\Contracts\Http\Kernel::class)
        ->pushMiddleware(\Lab404\Impersonate\Middleware\ImpersonateMiddleware::class);

    // Configure package
    config(['aura' => [
        'name' => 'aura',
        'routes' => false,
    ]]);

    // Clean up test files
    if (File::exists('resources/views/vendor/aura/components/layout/app.blade.php')) {
        File::delete('resources/views/vendor/aura/components/layout/app.blade.php');
    }
});

uses()->afterEach(function () {
    // Clean up test files
    collect([
        'vendor/eminiarts/aura/resources/views/components/layout/app.blade.php',
        'resources/views/vendor/aura/components/layout/app.blade.php',
    ])->each(fn ($path) => File::exists($path) && File::delete($path));
});

test('it can copy the aura layout file', function () {
    // Create test source file
    File::ensureDirectoryExists('vendor/eminiarts/aura/resources/views/components/layout');
    File::put(
        'vendor/eminiarts/aura/resources/views/components/layout/app.blade.php',
        'test content'
    );

    artisan('aura:layout')->assertSuccessful();

    expect(File::exists('resources/views/vendor/aura/components/layout/app.blade.php'))->toBeTrue()
        ->and(File::get('resources/views/vendor/aura/components/layout/app.blade.php'))->toBe('test content');
});

test('it fails when source file does not exist', function () {
    if (File::exists('vendor/eminiarts/aura/resources/views/components/layout/app.blade.php')) {
        File::delete('vendor/eminiarts/aura/resources/views/components/layout/app.blade.php');
    }

    artisan('aura:layout')
        ->expectsOutput('Aura layout file not found. Make sure the Aura package is installed.')
        ->assertFailed();
});
