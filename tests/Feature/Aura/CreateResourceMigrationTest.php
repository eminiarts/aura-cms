<?php

// use Aura\Base\Resources\User;
// use Illuminate\Support\Facades\File;
// use Illuminate\Support\Facades\Artisan;

// beforeEach(function () {
//     // Clean up any existing migration files
//     // collect(File::glob(database_path('migrations/*_create_users_table.php')))
//     //     ->each(fn ($file) => File::delete($file));
// });

// afterEach(function () {
//     // Clean up any created migration files
//     collect(File::glob(database_path('migrations/*_create_users_table.php')))
//         ->each(fn ($file) => File::delete($file));
// });

// it('can create a migration file for a resource', function () {
//     $exitCode = Artisan::call('aura:create-resource-migration', [
//         'resource' => User::class,
//     ]);

//     expect($exitCode)->toBe(0);

//     // Check if migration file was created
//     $migrationFile = collect(File::glob(database_path('migrations/*_create_users_table.php')))
//         ->first();

//     expect($migrationFile)->not->toBeNull();
//     expect(File::exists($migrationFile))->toBeTrue();

//     // Check migration file content
//     $content = File::get($migrationFile);
//     expect($content)->toContain('users');
//     expect($content)->toContain('Schema::create');
//     expect($content)->toContain('Migration');
// });

// it('fails when resource class does not exist', function () {
//     $exitCode = Artisan::call('aura:create-resource-migration', [
//         'resource' => 'NonExistentResource',
//     ]);

//     expect($exitCode)->toBe(1);
// });
