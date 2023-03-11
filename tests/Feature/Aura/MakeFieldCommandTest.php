<?php

use Eminiarts\Aura\Resources\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('generates the correct files for a field', function () {
    Storage::fake('app');

    Artisan::call('aura:field', [
        'name' => 'TestField',
    ]);

    // assert app/Aura/Resources/TestField.php exists
    $this->assertTrue(File::exists(app_path('Aura/Resources/TestField.php')));

    // delete app/Aura/Resources/TestField.php
    File::delete(app_path('Aura/Resources/TestField.php'));
});

test('field command and contents', function () {
    $fieldName = 'field';
    
    // Run the command and provide input
    $this->artisan('aura:field ' . $fieldName)->assertExitCode(0);

    // Assert that the field class file was generated
    expect(File::exists(app_path('Aura/Resources/' . ucfirst($fieldName) . '.php')))->toBeTrue();

    // Check file contents
    $fieldClass = File::get(app_path('Aura/Resources/' . ucfirst($fieldName) . '.php'));

    expect($fieldClass)->toContain("namespace App\Aura\Resources;");
    expect($fieldClass)->toContain('public static string $type = \'Field\';');
    expect($fieldClass)->toContain('public static ?string $slug = \'field\';');
    expect($fieldClass)->toContain("public function getIcon()");
    expect($fieldClass)->toContain("public static function getFields()");

    // Delete the field class file
    if (File::exists(app_path('Aura/Resources/' . ucfirst($fieldName) . '.php'))) {
        File::delete(app_path('Aura/Resources/' . ucfirst($fieldName) . '.php'));
    }
});