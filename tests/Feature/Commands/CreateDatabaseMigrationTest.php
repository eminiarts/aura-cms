<?php

use Aura\Base\Events\SaveFields;
use Aura\Base\Listeners\CreateDatabaseMigration;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->filesystemMock = Mockery::mock(Filesystem::class);
    $this->listener = new CreateDatabaseMigration($this->filesystemMock);
    $this->modelMock = Mockery::mock('Model');
    $this->modelMock->shouldReceive('getTable')->andReturn('test_table');
    $this->modelMock::$customTable = true;

    // Mock Artisan call
    Artisan::shouldReceive('call')->andReturn(true);

    // Mock Schema
    Schema::shouldReceive('table')->andReturn(true);

    // Mock Log
    Log::shouldReceive('error')->andReturn(true);
});

test('it detects fields to add', function () {
    $newFields = [
        ['slug' => 'field1', 'type' => 'Aura\Base\Fields\Text'],
        ['slug' => 'field2', 'type' => 'Aura\Base\Fields\Text'],
    ];

    $existingFields = [
        ['_id' => 1, 'slug' => 'field1', 'type' => 'Aura\Base\Fields\Text'],
    ];

    $event = new SaveFields($newFields, $existingFields, $this->modelMock);

    $this->filesystemMock->shouldReceive('glob')->andReturn(['migration.php']);
    $this->filesystemMock->shouldReceive('get')->andReturn('public function up(): void { Schema::table("test_table", function (Blueprint $table) { }); }');
    $this->filesystemMock->shouldReceive('put')->andReturn(true);

    $this->listener->handle($event);

    // Add assertions to verify correct schema generation for additions
});

test('it detects fields to update', function () {
    $newFields = [
        ['_id' => 1, 'slug' => 'field1', 'type' => 'Aura\Base\Fields\Textarea'],
        ['_id' => 2, 'slug' => 'field2', 'type' => 'Aura\Base\Fields\Text', 'name' => 'Field Two'],
    ];

    $existingFields = [
        ['_id' => 1, 'slug' => 'field1', 'type' => 'Aura\Base\Fields\Text'],
        ['_id' => 2, 'slug' => 'field2', 'type' => 'Aura\Base\Fields\Text'],
    ];

    $event = new SaveFields($newFields, $existingFields, $this->modelMock);

    $this->filesystemMock->shouldReceive('glob')->andReturn(['migration.php']);
    $this->filesystemMock->shouldReceive('get')->andReturn('public function up(): void { Schema::table("test_table", function (Blueprint $table) { }); }');
    $this->filesystemMock->shouldReceive('put')->andReturn(true);

    $this->listener->handle($event);

    // Add assertions to verify correct schema generation for updates (type changes and slug changes)
});

test('it detects fields to delete', function () {
    $newFields = [
        ['_id' => 1, 'slug' => 'field1', 'type' => 'Aura\Base\Fields\Text'],
    ];

    $existingFields = [
        ['_id' => 1, 'slug' => 'field1', 'type' => 'Aura\Base\Fields\Text'],
        ['_id' => 2, 'slug' => 'field2', 'type' => 'Aura\Base\Fields\Text'],
    ];

    $event = new SaveFields($newFields, $existingFields, $this->modelMock);

    $this->filesystemMock->shouldReceive('glob')->andReturn(['migration.php']);
    $this->filesystemMock->shouldReceive('get')->andReturn('public function up(): void { Schema::table("test_table", function (Blueprint $table) { }); }');
    $this->filesystemMock->shouldReceive('put')->andReturn(true);

    $this->listener->handle($event);

    // Add assertions to verify correct schema generation for deletions
});
