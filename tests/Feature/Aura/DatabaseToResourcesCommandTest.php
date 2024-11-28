<?php

use function Pest\Laravel\artisan;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    // Mock the Schema facade
    Schema::shouldReceive('getConnection->getDoctrineSchemaManager->listTableNames')
        ->andReturn(['test_table', 'another_table', 'migrations']);
    
    // Mock getColumnListing for any table
    Schema::shouldReceive('getColumnListing')
        ->andReturn(['id', 'name', 'created_at', 'updated_at']);
    
    // Mock getColumnType for any column
    Schema::shouldReceive('getColumnType')
        ->andReturn('string');
});

it('executes database to resources command successfully', function () {
    artisan('aura:database-to-resources')
        ->assertExitCode(0)
        ->assertSuccessful();
});

it('shows success message after completion', function () {
    artisan('aura:database-to-resources')
        ->expectsOutput('Resources generated successfully')
        ->assertExitCode(0);
});
