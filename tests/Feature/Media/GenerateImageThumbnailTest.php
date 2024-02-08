<?php

use Aura\Base\Jobs\GenerateImageThumbnail;
use Aura\Base\Models\User;
use Aura\Base\Resources\Attachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
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
