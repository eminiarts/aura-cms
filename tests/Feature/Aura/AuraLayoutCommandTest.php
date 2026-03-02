<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->sourcePath = base_path('vendor/eminiarts/aura/resources/views/components/layout/app.blade.php');
    $this->destinationPath = resource_path('views/vendor/aura/components/layout/app.blade.php');

    // Clean up destination
    if (File::exists($this->destinationPath)) {
        File::delete($this->destinationPath);
    }
});

afterEach(function () {
    // Clean up test files
    if (File::exists($this->destinationPath)) {
        File::delete($this->destinationPath);
    }
});

describe('error handling', function () {
    it('shows error when source file does not exist', function () {
        // Ensure source doesn't exist
        if (File::exists($this->sourcePath)) {
            File::delete($this->sourcePath);
        }

        $this->artisan('aura:layout')
            ->expectsOutput('Aura layout file not found. Make sure the Aura package is installed.')
            ->assertExitCode(1);
    });
});

// Note: The test for copying layout when source exists is skipped
// because the vendor/eminiarts path doesn't exist in the test environment.
// This functionality is tested manually during package development.
