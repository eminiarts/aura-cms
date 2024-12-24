<?php

use Aura\Base\Resources\User;
use Illuminate\Support\Facades\File;

it('can create a factory for a resource', function () {
    $factoryPath = database_path('factories/UserFactory.php');

    // Delete the factory file if it exists (cleanup)
    if (File::exists($factoryPath)) {
        File::delete($factoryPath);
    }

    $this->artisan('aura:create-resource-factory', [
        'resource' => User::class,
    ])
        ->assertExitCode(0);

    // Assert the factory file was created
    expect(File::exists($factoryPath))->toBeTrue();

    // Assert the factory file contains the expected content
    $factoryContent = File::get($factoryPath);
    expect($factoryContent)->toContain('namespace Database\Factories');
    expect($factoryContent)->toContain('class UserFactory');

    // Cleanup
    File::delete($factoryPath);
});

it('shows error for non-existent resource', function () {
    $this->artisan('aura:create-resource-factory', [
        'resource' => 'NonExistentResource',
    ])
        ->expectsOutput("Resource class 'NonExistentResource' not found.")
        ->assertExitCode(1);
});
