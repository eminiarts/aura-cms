<?php

use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->factoryPath = database_path('factories');
});

afterEach(function () {
    // Clean up any created factory files
    $factoryFiles = [
        'UserFactory.php',
        'PostFactory.php',
        'TestFactory.php',
    ];

    foreach ($factoryFiles as $file) {
        $path = $this->factoryPath.'/'.$file;
        if (File::exists($path)) {
            File::delete($path);
        }
    }
});

it('creates a factory for a resource', function () {
    $factoryPath = database_path('factories/UserFactory.php');

    // Ensure factory doesn't exist
    if (File::exists($factoryPath)) {
        File::delete($factoryPath);
    }

    $this->artisan('aura:create-resource-factory', [
        'resource' => User::class,
    ])
        ->assertExitCode(0);

    expect(File::exists($factoryPath))->toBeTrue();
});

it('generates factory with correct namespace and class', function () {
    $factoryPath = database_path('factories/UserFactory.php');

    if (File::exists($factoryPath)) {
        File::delete($factoryPath);
    }

    $this->artisan('aura:create-resource-factory', [
        'resource' => User::class,
    ])
        ->assertExitCode(0);

    $factoryContent = File::get($factoryPath);

    expect($factoryContent)
        ->toContain('namespace Database\Factories')
        ->toContain('class UserFactory')
        ->toContain('protected $model = User::class');
});

it('generates factory definition method', function () {
    $factoryPath = database_path('factories/UserFactory.php');

    if (File::exists($factoryPath)) {
        File::delete($factoryPath);
    }

    $this->artisan('aura:create-resource-factory', [
        'resource' => User::class,
    ])
        ->assertExitCode(0);

    $factoryContent = File::get($factoryPath);

    expect($factoryContent)
        ->toContain('public function definition()')
        ->toContain('return [');
});

it('shows success message after creation', function () {
    $factoryPath = database_path('factories/UserFactory.php');

    if (File::exists($factoryPath)) {
        File::delete($factoryPath);
    }

    $this->artisan('aura:create-resource-factory', [
        'resource' => User::class,
    ])
        ->expectsOutputToContain("Factory 'UserFactory' created successfully.")
        ->assertExitCode(0);
});

it('shows error for non-existent resource class', function () {
    $this->artisan('aura:create-resource-factory', [
        'resource' => 'NonExistentResource',
    ])
        ->expectsOutput("Resource class 'NonExistentResource' not found.")
        ->assertExitCode(1);
});

it('shows error for resource without getFields method', function () {
    // Create a mock class that exists but has no getFields method
    eval('class InvalidResource {}');

    $this->artisan('aura:create-resource-factory', [
        'resource' => 'InvalidResource',
    ])
        ->expectsOutput("Method 'getFields' not found in the 'InvalidResource' class.")
        ->assertExitCode(1);
});

it('provides newFactory method instructions', function () {
    $factoryPath = database_path('factories/UserFactory.php');

    if (File::exists($factoryPath)) {
        File::delete($factoryPath);
    }

    $this->artisan('aura:create-resource-factory', [
        'resource' => User::class,
    ])
        ->expectsOutputToContain("Don't forget to add the following method to your User Resource:")
        ->expectsOutputToContain('protected static function newFactory()')
        ->assertExitCode(0);
});
