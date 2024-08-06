<?php

use Aura\Base\Livewire\MediaUploader;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resources\Attachment;
use Aura\Base\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// // current
// uses()->group('current');

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
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
    $response = $this->get(route('aura.resource.index', Str::slug($attachment->type)));

    // dd($attachment->type);
    // Visit the Attachment Index Page
    $response
        ->assertOk()
    // Custom Index Page
        ->assertSeeLivewire('aura::attachment-index')
    // Media Uploader
        ->assertSeeLivewire('aura::media-uploader')
    // Media Grid View
        ->assertSeeLivewire('aura::table');
});

test('media uploader', function () {
    Storage::fake('avatars');
    Storage::fake('tmp-for-tests');

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
        ->assertSet('settings.default_view', $attachment->defaultTableView())
        ->assertSet('perPage', $attachment->defaultPerPage())
        ->assertSet('columns', $attachment->getDefaultColumns());

    expect($attachment->defaultTableView())->toBe('grid');
});

test('media can be selected', function () {
    $attachment = Attachment::factory()->create();
    
    Livewire::test(MediaUploader::class)
        ->call('selectMedia', $attachment->id)
        ->assertSet('selectedMedia', [$attachment->id]);
});