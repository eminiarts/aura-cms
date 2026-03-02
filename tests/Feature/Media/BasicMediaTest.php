<?php

use Aura\Base\Livewire\Attachment\Index as AttachmentIndex;
use Aura\Base\Livewire\MediaUploader;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resources\Attachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

// Media Index Page Tests
test('media page can be rendered with all required components', function () {
    $attachment = Attachment::create([
        'title' => 'Test Attachment',
        'status' => 'publish',
    ]);

    $this->assertDatabaseHas('posts', ['type' => 'Attachment']);

    $response = $this->withoutExceptionHandling()
        ->get(route('aura.attachment.index'));

    $response
        ->assertOk()
        ->assertSeeLivewire(AttachmentIndex::class)
        ->assertSeeLivewire(MediaUploader::class)
        ->assertSeeLivewire(Table::class);
});

test('media index page is accessible without attachments', function () {
    $response = $this->get(route('aura.attachment.index'));

    $response->assertOk();
});

// Media Uploader Tests
test('media uploader uploads and stores image file', function () {
    Storage::fake('public');
    Storage::fake('tmp-for-tests');

    $file = UploadedFile::fake()->image('avatar.png');

    Livewire::test(MediaUploader::class)
        ->set('media', [$file])
        ->assertHasNoErrors();

    $this->assertDatabaseHas('posts', ['type' => 'Attachment']);

    $attachment = Attachment::first();

    expect($attachment)->not->toBeNull();
    expect($attachment->title)->toBe('avatar.png');
    expect($attachment->mime_type)->toBe('image/png');
    expect($attachment->url)->not->toBeEmpty();

    Storage::disk('public')->assertExists($attachment->url);
});

test('media uploader uploads multiple files simultaneously', function () {
    Storage::fake('public');
    Storage::fake('tmp-for-tests');

    $files = [
        UploadedFile::fake()->image('photo1.jpg'),
        UploadedFile::fake()->image('photo2.png'),
    ];

    Livewire::test(MediaUploader::class)
        ->set('media', $files)
        ->assertHasNoErrors();

    expect(Attachment::count())->toBe(2);

    $attachments = Attachment::all();
    expect($attachments->pluck('title')->toArray())
        ->toContain('photo1.jpg')
        ->toContain('photo2.png');
});

test('media uploader stores file size correctly', function () {
    Storage::fake('public');
    Storage::fake('tmp-for-tests');

    $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

    Livewire::test(MediaUploader::class)
        ->set('media', [$file])
        ->assertHasNoErrors();

    $attachment = Attachment::first();
    expect($attachment)->not->toBeNull();
    expect($attachment->size)->toBeGreaterThan(0);
});

test('media uploader dispatches refreshTable event after upload', function () {
    Storage::fake('public');
    Storage::fake('tmp-for-tests');

    $file = UploadedFile::fake()->image('test.jpg');

    Livewire::test(MediaUploader::class)
        ->set('media', [$file])
        ->assertDispatched('refreshTable');
});

test('media uploader stores correct mime type for jpg', function () {
    Storage::fake('public');
    Storage::fake('tmp-for-tests');

    $file = UploadedFile::fake()->image('test.jpg');

    Livewire::test(MediaUploader::class)
        ->set('media', [$file])
        ->assertHasNoErrors();

    $attachment = Attachment::first();
    expect($attachment->mime_type)->toBe('image/jpeg');
});

test('media uploader stores correct mime type for png', function () {
    Storage::fake('public');
    Storage::fake('tmp-for-tests');

    $file = UploadedFile::fake()->image('test.png');

    Livewire::test(MediaUploader::class)
        ->set('media', [$file])
        ->assertHasNoErrors();

    $attachment = Attachment::first();
    expect($attachment->mime_type)->toBe('image/png');
});

test('media uploader stores correct mime type for gif', function () {
    Storage::fake('public');
    Storage::fake('tmp-for-tests');

    $file = UploadedFile::fake()->image('test.gif');

    Livewire::test(MediaUploader::class)
        ->set('media', [$file])
        ->assertHasNoErrors();

    $attachment = Attachment::first();
    expect($attachment->mime_type)->toBe('image/gif');
});

// Media Grid View Tests
test('media grid view uses grid as default view for attachments', function () {
    $attachment = Attachment::create([
        'title' => 'Test Attachment',
        'status' => 'publish',
    ]);

    Livewire::test(Table::class, ['query' => null, 'model' => $attachment])
        ->assertSet('settings.default_view', $attachment->defaultTableView())
        ->assertSet('perPage', $attachment->defaultPerPage())
        ->assertSet('columns', $attachment->getDefaultColumns());

    expect($attachment->defaultTableView())->toBe('grid');
    expect($attachment->defaultPerPage())->toBe(25);
});

// Media Selection Tests
test('media can be selected', function () {
    $attachment = Attachment::factory()->create();

    Livewire::test(Table::class, ['model' => new Attachment])
        ->set('selected', [$attachment->id])
        ->assertSet('selected', [$attachment->id])
        ->assertSet('selectAll', false)
        ->assertSet('selectPage', false);
});

test('media can select multiple items', function () {
    $attachments = Attachment::factory()->count(3)->create();
    $ids = $attachments->pluck('id')->toArray();

    Livewire::test(Table::class, ['model' => new Attachment])
        ->set('selected', $ids)
        ->assertSet('selected', $ids);
});

test('media selection can be cleared', function () {
    $attachment = Attachment::factory()->create();

    Livewire::test(Table::class, ['model' => new Attachment])
        ->set('selected', [$attachment->id])
        ->set('selected', [])
        ->assertSet('selected', []);
});
