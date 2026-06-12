<?php

use Aura\Base\Livewire\MediaUploader;
use Aura\Base\Resources\Attachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
    Storage::fake('public');
});

// PHP File Rejection Tests
test('rejects PHP file uploads', function () {
    $phpFile = UploadedFile::fake()->create('malware.php', 100, 'application/x-php');

    Livewire::test(MediaUploader::class)
        ->set('media', [$phpFile])
        ->assertHasErrors(['media.*']);

    expect(Attachment::count())->toBe(0);
    Storage::disk('public')->assertMissing('media/malware.php');
});

test('rejects PHP files with alternate extensions', function () {
    $phpExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'phar'];

    foreach ($phpExtensions as $ext) {
        Storage::fake('public');

        $file = UploadedFile::fake()->create("backdoor.{$ext}", 100);

        Livewire::test(MediaUploader::class)
            ->set('media', [$file])
            ->assertHasErrors(['media.*']);

        expect(Attachment::count())->toBe(0);
        Storage::disk('public')->assertMissing("media/backdoor.{$ext}");
    }
});

// Executable File Rejection Tests
test('rejects shell script uploads', function () {
    $shFile = UploadedFile::fake()->create('script.sh', 100, 'application/x-sh');

    Livewire::test(MediaUploader::class)
        ->set('media', [$shFile])
        ->assertHasErrors(['media.*']);

    expect(Attachment::count())->toBe(0);
});

test('rejects Windows executable uploads', function () {
    $exeFile = UploadedFile::fake()->create('program.exe', 100, 'application/x-executable');

    Livewire::test(MediaUploader::class)
        ->set('media', [$exeFile])
        ->assertHasErrors(['media.*']);

    expect(Attachment::count())->toBe(0);
});

test('rejects batch file uploads', function () {
    $batFile = UploadedFile::fake()->create('script.bat', 100, 'application/x-msdos-program');

    Livewire::test(MediaUploader::class)
        ->set('media', [$batFile])
        ->assertHasErrors(['media.*']);

    expect(Attachment::count())->toBe(0);
});

test('rejects all blocked extensions', function () {
    // These extensions are explicitly blocked in MediaUploader
    $blockedExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'sh', 'exe', 'bat'];

    foreach ($blockedExtensions as $ext) {
        Storage::fake('public');

        $file = UploadedFile::fake()->create("malicious.{$ext}", 100);

        Livewire::test(MediaUploader::class)
            ->set('media', [$file])
            ->assertHasErrors(['media.*']);

        expect(Attachment::count())->toBe(0);
    }
});

// Safe Image File Tests
test('accepts jpeg image uploads', function () {
    $imageFile = UploadedFile::fake()->image('photo.jpg');

    Livewire::test(MediaUploader::class)
        ->set('media', [$imageFile])
        ->assertHasNoErrors();

    expect(Attachment::count())->toBe(1);

    $attachment = Attachment::first();
    expect($attachment->title)->toBe('photo.jpg');
    expect($attachment->mime_type)->toBe('image/jpeg');
});

test('accepts png image uploads', function () {
    $imageFile = UploadedFile::fake()->image('image.png');

    Livewire::test(MediaUploader::class)
        ->set('media', [$imageFile])
        ->assertHasNoErrors();

    expect(Attachment::count())->toBe(1);
    expect(Attachment::first()->mime_type)->toBe('image/png');
});

test('accepts gif image uploads', function () {
    $imageFile = UploadedFile::fake()->image('animation.gif');

    Livewire::test(MediaUploader::class)
        ->set('media', [$imageFile])
        ->assertHasNoErrors();

    expect(Attachment::count())->toBe(1);
    expect(Attachment::first()->mime_type)->toBe('image/gif');
});

test('accepts webp image uploads', function () {
    $imageFile = UploadedFile::fake()->create('image.webp', 100, 'image/webp');

    Livewire::test(MediaUploader::class)
        ->set('media', [$imageFile])
        ->assertHasNoErrors();

    expect(Attachment::count())->toBe(1);
    expect(Attachment::first()->mime_type)->toBe('image/webp');
});

test('accepts svg image uploads', function () {
    $svgFile = UploadedFile::fake()->create('icon.svg', 100, 'image/svg+xml');

    Livewire::test(MediaUploader::class)
        ->set('media', [$svgFile])
        ->assertHasNoErrors();

    expect(Attachment::count())->toBe(1);
});

// Safe Document File Tests
test('accepts PDF document uploads', function () {
    $pdfFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    Livewire::test(MediaUploader::class)
        ->set('media', [$pdfFile])
        ->assertHasNoErrors();

    expect(Attachment::count())->toBe(1);
    expect(Attachment::first()->mime_type)->toBe('application/pdf');
});

test('accepts Word document uploads', function () {
    $docFile = UploadedFile::fake()->create('document.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    Livewire::test(MediaUploader::class)
        ->set('media', [$docFile])
        ->assertHasNoErrors();

    expect(Attachment::count())->toBe(1);
});

test('accepts Excel spreadsheet uploads', function () {
    $xlsxFile = UploadedFile::fake()->create('spreadsheet.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    Livewire::test(MediaUploader::class)
        ->set('media', [$xlsxFile])
        ->assertHasNoErrors();

    expect(Attachment::count())->toBe(1);
});

test('accepts PowerPoint presentation uploads', function () {
    $pptxFile = UploadedFile::fake()->create('presentation.pptx', 100, 'application/vnd.openxmlformats-officedocument.presentationml.presentation');

    Livewire::test(MediaUploader::class)
        ->set('media', [$pptxFile])
        ->assertHasNoErrors();

    expect(Attachment::count())->toBe(1);
});

test('accepts plain text file uploads', function () {
    $txtFile = UploadedFile::fake()->create('notes.txt', 100, 'text/plain');

    Livewire::test(MediaUploader::class)
        ->set('media', [$txtFile])
        ->assertHasNoErrors();

    expect(Attachment::count())->toBe(1);
});

test('accepts CSV file uploads', function () {
    $csvFile = UploadedFile::fake()->create('data.csv', 100, 'text/csv');

    Livewire::test(MediaUploader::class)
        ->set('media', [$csvFile])
        ->assertHasNoErrors();

    expect(Attachment::count())->toBe(1);
});

// Media File Tests
test('accepts MP4 video uploads', function () {
    $videoFile = UploadedFile::fake()->create('video.mp4', 100, 'video/mp4');

    Livewire::test(MediaUploader::class)
        ->set('media', [$videoFile])
        ->assertHasNoErrors();

    expect(Attachment::count())->toBe(1);
});

test('accepts MOV video uploads', function () {
    $movFile = UploadedFile::fake()->create('video.mov', 100, 'video/quicktime');

    Livewire::test(MediaUploader::class)
        ->set('media', [$movFile])
        ->assertHasNoErrors();

    expect(Attachment::count())->toBe(1);
});

test('accepts MP3 audio uploads', function () {
    $mp3File = UploadedFile::fake()->create('audio.mp3', 100, 'audio/mpeg');

    Livewire::test(MediaUploader::class)
        ->set('media', [$mp3File])
        ->assertHasNoErrors();

    expect(Attachment::count())->toBe(1);
});

test('accepts WAV audio uploads', function () {
    $wavFile = UploadedFile::fake()->create('audio.wav', 100, 'audio/wav');

    Livewire::test(MediaUploader::class)
        ->set('media', [$wavFile])
        ->assertHasNoErrors();

    expect(Attachment::count())->toBe(1);
});

// Archive File Tests
test('accepts ZIP archive uploads', function () {
    $zipFile = UploadedFile::fake()->create('archive.zip', 100, 'application/zip');

    Livewire::test(MediaUploader::class)
        ->set('media', [$zipFile])
        ->assertHasNoErrors();

    expect(Attachment::count())->toBe(1);
});

// File Size Tests
test('rejects files exceeding size limit', function () {
    // 100MB limit is set in MediaUploader
    $largeFile = UploadedFile::fake()->create('huge.pdf', 102401); // Just over 100MB in KB

    Livewire::test(MediaUploader::class)
        ->set('media', [$largeFile])
        ->assertHasErrors(['media.*']);

    expect(Attachment::count())->toBe(0);
});

test('accepts files within size limit', function () {
    // 100MB limit is set in MediaUploader
    $normalFile = UploadedFile::fake()->create('normal.pdf', 10000, 'application/pdf'); // ~10MB

    Livewire::test(MediaUploader::class)
        ->set('media', [$normalFile])
        ->assertHasNoErrors();

    expect(Attachment::count())->toBe(1);
});

// Multiple Files Tests
test('rejects upload when one file in batch is malicious', function () {
    $safeFile = UploadedFile::fake()->image('safe.jpg');
    $maliciousFile = UploadedFile::fake()->create('evil.php', 100, 'application/x-php');

    Livewire::test(MediaUploader::class)
        ->set('media', [$safeFile, $maliciousFile])
        ->assertHasErrors(['media.*']);
});

test('accepts multiple safe files', function () {
    $files = [
        UploadedFile::fake()->image('photo1.jpg'),
        UploadedFile::fake()->image('photo2.png'),
        UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
    ];

    Livewire::test(MediaUploader::class)
        ->set('media', $files)
        ->assertHasNoErrors();

    expect(Attachment::count())->toBe(3);
});

// All Allowed Extensions Test
test('accepts all allowed MIME types from validation', function () {
    // Based on MediaUploader validation: mimes:jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,mp4,mov,avi,mp3,wav
    $allowedTypes = [
        'jpg' => 'image/jpeg',
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
    ];

    foreach ($allowedTypes as $ext => $mimeType) {
        Storage::fake('public');

        $file = UploadedFile::fake()->create("file.{$ext}", 100, $mimeType);

        Livewire::test(MediaUploader::class)
            ->set('media', [$file])
            ->assertHasNoErrors();

        expect(Attachment::count())->toBeGreaterThan(0);
    }
});

// File Storage Location Tests
test('stores uploaded files in correct directory', function () {
    $imageFile = UploadedFile::fake()->image('photo.jpg');

    Livewire::test(MediaUploader::class)
        ->set('media', [$imageFile])
        ->assertHasNoErrors();

    $attachment = Attachment::first();
    expect($attachment->url)->toStartWith('media/');
});

test('stores file with correct name pattern', function () {
    $imageFile = UploadedFile::fake()->image('my_photo.jpg');

    Livewire::test(MediaUploader::class)
        ->set('media', [$imageFile])
        ->assertHasNoErrors();

    $attachment = Attachment::first();
    expect($attachment->title)->toBe('my_photo.jpg');
});
