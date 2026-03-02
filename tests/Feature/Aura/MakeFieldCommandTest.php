<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(createSuperAdmin());
    $this->fieldPath = app_path('Aura/Fields');
    $this->viewPath = resource_path('views/components/fields');
});

afterEach(function () {
    // Clean up generated field files
    $fieldFiles = ['TestField.php', 'CustomField.php', 'MyField.php'];
    foreach ($fieldFiles as $file) {
        $path = $this->fieldPath.'/'.$file;
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    // Clean up generated view files
    $viewFiles = ['testfield.blade.php', 'testfield-view.blade.php', 'customfield.blade.php', 'customfield-view.blade.php', 'myfield.blade.php', 'myfield-view.blade.php'];
    foreach ($viewFiles as $file) {
        $path = $this->viewPath.'/'.$file;
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    // Remove directories if empty
    if (File::isDirectory($this->fieldPath) && empty(File::files($this->fieldPath))) {
        File::deleteDirectory($this->fieldPath);
    }
    if (File::isDirectory($this->viewPath) && empty(File::files($this->viewPath))) {
        File::deleteDirectory($this->viewPath);
    }
});

it('generates a field file with correct path', function () {
    $this->artisan('aura:field', ['name' => 'TestField'])
        ->assertExitCode(0);

    expect(File::exists($this->fieldPath.'/TestField.php'))->toBeTrue();
});

it('generates field class with correct namespace and structure', function () {
    $this->artisan('aura:field', ['name' => 'TestField'])
        ->assertExitCode(0);

    $fieldClass = File::get($this->fieldPath.'/TestField.php');

    expect($fieldClass)
        ->toContain("namespace App\Aura\Fields;")
        ->toContain('use Aura\Base\Fields\Field;')
        ->toContain('class TestField extends Field')
        ->toContain("public \$edit = 'fields.testfield';")
        ->toContain("public \$view = 'fields.testfield-view';")
        ->toContain('public function getFields()');
});

it('generates both edit and view blade files', function () {
    $this->artisan('aura:field', ['name' => 'TestField'])
        ->assertExitCode(0);

    expect(File::exists($this->viewPath.'/testfield.blade.php'))->toBeTrue()
        ->and(File::exists($this->viewPath.'/testfield-view.blade.php'))->toBeTrue();
});

it('generates edit blade file with correct content', function () {
    $this->artisan('aura:field', ['name' => 'CustomField'])
        ->assertExitCode(0);

    $editContent = File::get($this->viewPath.'/customfield.blade.php');

    expect($editContent)
        ->toContain('x-aura::fields.wrapper')
        ->toContain('x-aura::input.text');
});

it('generates view blade file with correct content', function () {
    $this->artisan('aura:field', ['name' => 'CustomField'])
        ->assertExitCode(0);

    $viewContent = File::get($this->viewPath.'/customfield-view.blade.php');

    expect($viewContent)
        ->toContain('x-aura::fields.wrapper')
        ->toContain('$this->model->display');
});

it('displays success message after creation', function () {
    $this->artisan('aura:field', ['name' => 'MyField'])
        ->expectsOutput('Field created successfully.')
        ->assertExitCode(0);
});

it('generates correct slug from PascalCase name', function () {
    $this->artisan('aura:field', ['name' => 'CustomField'])
        ->assertExitCode(0);

    $fieldClass = File::get($this->fieldPath.'/CustomField.php');

    // Str::slug converts CustomField to customfield
    expect($fieldClass)
        ->toContain("public \$edit = 'fields.customfield';")
        ->toContain("public \$view = 'fields.customfield-view';");
});

it('creates views directory if not exists', function () {
    // Ensure directory doesn't exist before test
    if (File::isDirectory($this->viewPath)) {
        File::deleteDirectory($this->viewPath);
    }

    $this->artisan('aura:field', ['name' => 'TestField'])
        ->assertExitCode(0);

    expect(File::isDirectory($this->viewPath))->toBeTrue();
});
