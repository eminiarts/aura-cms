<?php

use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Models\Post;
use Eminiarts\Aura\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

uses()->group('current');

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));


use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

test('it creates a new Aura Posttype', function () {
    // Arrange
    $posttypeName = 'TestPosttype';
    $namespace = 'Eminiarts\Aura\Resources';
    $filePath = app_path("{$namespace}/{$posttypeName}.php");

    // Act
    Artisan::call('aura:posttype', ['name' => $posttypeName]);

    // Assert
    expect(File::exists($filePath))->toBeTrue();
    $content = File::get($filePath);
    expect(Str::contains($content, "class {$posttypeName}"))->toBeTrue();
    expect(Str::contains($content, "protected \$type = 'Posttype'"))->toBeTrue();
    expect(Str::contains($content, "PostName"))->toBeFalse();
    expect(Str::contains($content, "PostSlug"))->toBeFalse();
});

test('it replaces placeholders in the stub file', function () {
    // Arrange
    $posttypeName = 'TestPosttype';
    $namespace = 'Eminiarts\Aura\Resources';
    $filePath = app_path("{$namespace}/{$posttypeName}.php");

    // Act
    Artisan::call('aura:posttype', ['name' => $posttypeName]);

    // Assert
    expect(File::exists($filePath))->toBeTrue();
    $content = File::get($filePath);
    expect(Str::contains($content, "class {$posttypeName}"))->toBeTrue();
    expect(Str::contains($content, "protected \$type = 'Posttype'"))->toBeTrue();
    expect(Str::contains($content, "TestPosttype"))->toBeTrue();
    expect(Str::contains($content, Str::slug('TestPosttype')))->toBeTrue();
});
