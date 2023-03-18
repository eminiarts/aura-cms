<?php

use Eminiarts\Aura\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

use Eminiarts\Aura\Resources\Attachment;
use Eminiarts\Aura\Jobs\GenerateImageThumbnail;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    // Create User
    $this->actingAs($this->user = User::factory()->create());

    // Create Team and assign to user
    createSuperAdmin();

    // Refresh User
    $this->user = $this->user->refresh();

    // Login
    $this->actingAs($this->user);
});



it('generates image thumbnail', function () {
    Storage::fake('local');
    Queue::fake();

    $attachment = Attachment::create([
        'name' => 'Test Attachment',
        'url' => 'test-url.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 12345,
    ]);

    // Assert that the Job was dispatched
    Queue::assertPushed(GenerateImageThumbnail::class);
});
