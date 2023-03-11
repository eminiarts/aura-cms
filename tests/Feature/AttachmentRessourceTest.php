<?php

use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resources\Attachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(fn () => User::factory()->create());


test('save Attachment Model when defined fields', function () {
    $this->actingAs($user = User::first());

    Storage::fake('avatars');
    Storage::fake('tmp-for-tests');

    $file = UploadedFile::fake()->image('avatar.png');

    $this->assertDatabaseMissing('posts', [
        'type' => 'Attachment',
    ]);

    $attachment = Attachment::create([
        'name' => $file->getClientOriginalName(),
        'title' => $file->getClientOriginalName(),

        'fields' => [
            'url' => 'avatar.png',
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'bajram' => 'bajram',
        ],
    ]);

    $this->assertDatabaseHas('posts', [
        'type' => 'Attachment',
    ]);
});
