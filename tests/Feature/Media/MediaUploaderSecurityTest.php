<?php

use Aura\Base\Livewire\MediaUploader;
use Aura\Base\Resources\Attachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
    Storage::fake('public');
});

test('rejects PHP file uploads', function () {
    $phpFile = UploadedFile::fake()->create('malware.php', 100, 'application/x-php');

    Livewire::test(MediaUploader::class)
        ->set('media', [$phpFile])
        ->assertHasErrors(['media.*']);

    // Assert NO attachment was created
    expect(Attachment::count())->toBe(0);

    // Assert file was NOT stored
    Storage::disk('public')->assertMissing('media/malware.php');
});

test('rejects executable file uploads', function () {
    $executableExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'sh', 'exe', 'bat'];

    foreach ($executableExtensions as $ext) {
        Storage::fake('public');

        $file = UploadedFile::fake()->create("backdoor.{$ext}", 100);

        Livewire::test(MediaUploader::class)
            ->set('media', [$file])
            ->assertHasErrors(['media.*']);

        expect(Attachment::count())->toBe(0);
    }
});

test('accepts safe image file uploads', function () {
    $imageFile = UploadedFile::fake()->image('photo.jpg');

    Livewire::test(MediaUploader::class)
        ->set('media', [$imageFile])
        ->assertHasNoErrors();

    expect(Attachment::count())->toBe(1);

    $attachment = Attachment::first();
    expect($attachment->title)->toBe('photo.jpg');
    expect($attachment->mime_type)->toBe('image/jpeg');
});

test('accepts safe document file uploads', function () {
    $pdfFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    Livewire::test(MediaUploader::class)
        ->set('media', [$pdfFile])
        ->assertHasNoErrors();

    expect(Attachment::count())->toBe(1);

    $attachment = Attachment::first();
    expect($attachment->title)->toBe('document.pdf');
    expect($attachment->mime_type)->toBe('application/pdf');
});

test('accepts multiple safe file types', function () {
    $safeExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv'];

    foreach ($safeExtensions as $ext) {
        Storage::fake('public');

        $mimeType = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            default => 'application/octet-stream',
        };

        $file = UploadedFile::fake()->create("file.{$ext}", 100, $mimeType);

        Livewire::test(MediaUploader::class)
            ->set('media', [$file])
            ->assertHasNoErrors();

        expect(Attachment::count())->toBeGreaterThan(0);
    }
});
