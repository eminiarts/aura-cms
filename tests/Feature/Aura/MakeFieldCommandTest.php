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

    // assert app/Aura/Fields/TestField.php exists
    $this->assertTrue(File::exists(app_path('Aura/Fields/TestField.php')));

    // delete app/Aura/Fields/TestField.php
    File::delete(app_path('Aura/Fields/TestField.php'));
});

test('field command and contents', function () {
    $fieldName = 'TestField';

    // Run the command and provide input
    $this->artisan('aura:field '.$fieldName)->assertExitCode(0);

    // Assert that the field class file was generated
    expect(File::exists(app_path('Aura/Fields/'.ucfirst($fieldName).'.php')))->toBeTrue();

    // Check file contents
    $fieldClass = File::get(app_path('Aura/Fields/'.ucfirst($fieldName).'.php'));

    expect($fieldClass)->toContain("namespace App\Aura\Fields;");
    expect($fieldClass)->toContain('use Eminiarts\Aura\Fields\Field;');
    expect($fieldClass)->toContain('class TestField extends Field');
    expect($fieldClass)->toContain('public $component = \'fields.testfield\';');
    expect($fieldClass)->toContain('public $view = \'fields.testfield-view\';');
    expect($fieldClass)->toContain('public function getFields()');

    // Delete the field class file
    if (File::exists(app_path('Aura/Fields/'.ucfirst($fieldName).'.php'))) {
        File::delete(app_path('Aura/Fields/'.ucfirst($fieldName).'.php'));
    }
});
