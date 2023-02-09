<?php

use Eminiarts\Aura\Http\Livewire\MediaUploader;
use Eminiarts\Aura\Http\Livewire\Table\Table;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resources\Attachment;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

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

test('media page can be rendered', function () {
    // Create a Post
    $attachment = Attachment::create([
        'title' => 'Test Attachment',
        'status' => 'publish',
    ]);

    // Assert Post is in DB
    $this->assertDatabaseHas('posts', ['Type' => 'Attachment']);

    // With exception handling
    $this->withoutExceptionHandling();

    // Visit the Attachment Index Page
    $this->get(route('aura.post.index', $attachment->type))
    // Custom Index Page
    ->assertSeeLivewire('attachment.index')
    // Media Uploader
    ->assertSeeLivewire('media-uploader')
    // Media Grid View
    ->assertSeeLivewire('table.table');
});

test('media uploader', function () {
    Storage::fake('avatars');

    $file = UploadedFile::fake()->image('avatar.png');

    Livewire::test(MediaUploader::class)->set('media', [$file]);

    // Assert Attachment is in DB
    $this->assertDatabaseHas('posts', ['Type' => 'Attachment']);

    $attachment = Attachment::first();

    // Title avatar.png
    expect($attachment->title)->toBe('avatar.png');

    // mime_type image/png
    expect($attachment->mime_type)->toBe('image/png');

    // assert url not to be empty
    expect($attachment->url)->not->toBeEmpty();

    Storage::disk('public')->assertExists($attachment->url);
});

test('media grid view', function () {
    $attachment = Attachment::create([
        'title' => 'Test Attachment',
        'status' => 'publish',
    ]);

    $component = Livewire::test(Table::class, ['query' => null, 'model' => $attachment])
            ->assertSet('tableView', $attachment->defaultTableView())
            ->assertSet('perPage', $attachment->defaultPerPage())
            ->assertSet('columns', $attachment->getDefaultColumns());

    expect($attachment->defaultTableView())->toBe('grid');
});

test('media can be selected', function () {
});
