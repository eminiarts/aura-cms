<?php

use Illuminate\Support\Facades\Schema;
use Aura\Base\Commands\DatabaseToResources;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    // Set up default column mocks that all tests will need
    Schema::shouldReceive('getColumnListing')
        ->andReturn(['id', 'name', 'email', 'created_at', 'updated_at']);

    Schema::shouldReceive('getColumnType')
        ->with(Mockery::any(), 'id')
        ->andReturn('integer');
    Schema::shouldReceive('getColumnType')
        ->with(Mockery::any(), 'name')
        ->andReturn('string');
    Schema::shouldReceive('getColumnType')
        ->with(Mockery::any(), 'email')
        ->andReturn('string');
    Schema::shouldReceive('getColumnType')
        ->with(Mockery::any(), 'created_at')
        ->andReturn('datetime');
    Schema::shouldReceive('getColumnType')
        ->with(Mockery::any(), 'updated_at')
        ->andReturn('datetime');
});

test('it executes database to resources command successfully', function () {
    Schema::shouldReceive('getConnection->getDoctrineSchemaManager->listTableNames')
        ->andReturn(['users', 'posts', 'comments', 'migrations', 'failed_jobs', 'password_resets', 'settions']);

    $commandLog = [];

    Artisan::command('aura:transform-table-to-resource {table}', function ($table) use (&$commandLog) {
        $commandLog[] = $table;
        return 0;
    });

    $this->artisan('aura:database-to-resources')
        ->assertSuccessful()
        ->assertExitCode(0);

    expect($commandLog)->toHaveCount(3)
        ->toContain('users', 'posts', 'comments')
        ->not->toContain('migrations', 'failed_jobs', 'password_resets', 'settions');
});

test('it skips system tables', function () {
    Schema::shouldReceive('getConnection->getDoctrineSchemaManager->listTableNames')
        ->andReturn(['users', 'posts', 'comments', 'migrations', 'failed_jobs', 'password_resets', 'settions']);

    $commandLog = [];

    Artisan::command('aura:transform-table-to-resource {table}', function ($table) use (&$commandLog) {
        $commandLog[] = $table;
        return 0;
    });

    $this->artisan('aura:database-to-resources')
        ->assertSuccessful();

    $systemTables = ['migrations', 'failed_jobs', 'password_resets', 'settions'];
    foreach ($systemTables as $table) {
        expect($commandLog)->not->toContain($table);
    }
});

test('it shows success message after completion', function () {
    Schema::shouldReceive('getConnection->getDoctrineSchemaManager->listTableNames')
        ->andReturn(['users', 'posts', 'comments']);

    Artisan::command('aura:transform-table-to-resource {table}', function () {
        return 0;
    });

    $this->artisan('aura:database-to-resources')
        ->expectsOutput('Resources generated successfully')
        ->assertSuccessful();
});

test('it handles empty database gracefully', function () {
    Schema::shouldReceive('getConnection->getDoctrineSchemaManager->listTableNames')
        ->andReturn([]);

    $commandLog = [];

    Artisan::command('aura:transform-table-to-resource {table}', function ($table) use (&$commandLog) {
        $commandLog[] = $table;
        return 0;
    });

    $this->artisan('aura:database-to-resources')
        ->assertSuccessful()
        ->expectsOutput('Resources generated successfully');

    expect($commandLog)->toBeEmpty();
});

test('it processes all non-system tables', function () {
    Schema::shouldReceive('getConnection->getDoctrineSchemaManager->listTableNames')
        ->andReturn(['users', 'posts', 'comments', 'migrations', 'failed_jobs', 'password_resets', 'settions']);

    $expectedTables = ['users', 'posts', 'comments'];
    $processedTables = [];

    Artisan::command('aura:transform-table-to-resource {table}', function ($table) use (&$processedTables) {
        $processedTables[] = $table;
        return 0;
    });

    $this->artisan('aura:database-to-resources')
        ->assertSuccessful();

    expect($processedTables)
        ->toHaveCount(3)
        ->toEqual($expectedTables);
});
