<?php

use Aura\Base\Livewire\MediaUploader;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resources\Attachment;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Livewire\Features\SupportTesting\Testable;

use function Pest\Livewire\livewire;

beforeEach(fn () => $this->actingAs($this->user = createSuperAdmin()));

function createAttachmentWithMime(string $name, string $mime, ?Carbon $createdAt = null): Attachment
{
    $attachment = Attachment::create([
        'url' => 'media/'.$name,
        'name' => $name,
        'title' => $name,
        'size' => 100,
        'mime_type' => $mime,
    ]);

    if ($createdAt) {
        $attachment->timestamps = false;
        $attachment->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
        $attachment->timestamps = true;
    }

    return $attachment;
}

function mediaTable(?Attachment $attachment = null): Testable
{
    return livewire(Table::class, [
        'query' => null,
        'model' => $attachment ?? new Attachment,
        'settings' => [
            'default_view' => 'list',
            'actions' => false,
            'create' => false,
            'views' => [
                'list' => 'aura::components.table.list-view',
                'row' => 'aura::components.table.row',
                'table' => 'aura::components.table.index',
            ],
        ],
    ]);
}

test('type quick filter narrows to images', function () {
    createAttachmentWithMime('photo.jpg', 'image/jpeg');
    createAttachmentWithMime('clip.mp4', 'video/mp4');
    createAttachmentWithMime('doc.pdf', 'application/pdf');

    $component = mediaTable()->call('setQuickFilter', 'type', 'image');

    $rows = $component->instance()->rows;

    expect($rows->getCollection()->map(fn ($r) => $r->name)->all())->toBe(['photo.jpg']);
});

test('document quick filter means not image video or audio', function () {
    createAttachmentWithMime('photo.jpg', 'image/jpeg');
    createAttachmentWithMime('clip.mp4', 'video/mp4');
    createAttachmentWithMime('song.mp3', 'audio/mpeg');
    createAttachmentWithMime('doc.pdf', 'application/pdf');
    createAttachmentWithMime('data.csv', 'text/csv');
    createAttachmentWithMime('archive.zip', 'application/zip');

    $component = mediaTable()->call('setQuickFilter', 'type', 'document');

    $rows = $component->instance()->rows;

    expect($rows->getCollection()->map(fn ($r) => $r->name)->sort()->values()->all())
        ->toBe(['archive.zip', 'data.csv', 'doc.pdf']);
});

test('type quick filters support custom-table attachments without meta', function () {
    Schema::create('custom_table_attachments', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('url');
        $table->string('title');
        $table->unsignedBigInteger('size');
        $table->string('mime_type');
        $table->unsignedInteger('width')->nullable();
        $table->unsignedInteger('height')->nullable();
        $table->foreignId('user_id')->nullable();
        $table->foreignId('team_id')->nullable();
        $table->timestamps();
    });

    try {
        CustomTableAttachment::withoutEvents(function (): void {
            CustomTableAttachment::create([
                'name' => 'photo.jpg',
                'url' => 'media/photo.jpg',
                'title' => 'photo.jpg',
                'size' => 100,
                'mime_type' => 'image/jpeg',
                'user_id' => auth()->id(),
                'team_id' => auth()->user()->current_team_id,
            ]);
            CustomTableAttachment::create([
                'name' => 'document.pdf',
                'url' => 'media/document.pdf',
                'title' => 'document.pdf',
                'size' => 100,
                'mime_type' => 'application/pdf',
                'user_id' => auth()->id(),
                'team_id' => auth()->user()->current_team_id,
            ]);
        });

        $component = mediaTable(new CustomTableAttachment)
            ->call('setQuickFilter', 'type', 'image');

        expect($component->instance()->rows->getCollection()->pluck('name')->all())
            ->toBe(['photo.jpg']);
    } finally {
        Schema::dropIfExists('custom_table_attachments');
    }
});

test('month quick filter narrows by upload month', function () {
    createAttachmentWithMime('old.jpg', 'image/jpeg', Carbon::parse('2026-05-15 10:00:00'));
    createAttachmentWithMime('new.jpg', 'image/jpeg', Carbon::parse('2026-07-01 09:00:00'));

    $component = mediaTable()->call('setQuickFilter', 'month', '2026-05');

    expect($component->instance()->rows->getCollection()->map(fn ($r) => $r->name)->all())->toBe(['old.jpg']);
});

test('quick filters can be cleared and combined', function () {
    createAttachmentWithMime('may-photo.jpg', 'image/jpeg', Carbon::parse('2026-05-15 10:00:00'));
    createAttachmentWithMime('may-doc.pdf', 'application/pdf', Carbon::parse('2026-05-20 10:00:00'));
    createAttachmentWithMime('july-photo.jpg', 'image/jpeg', Carbon::parse('2026-07-01 09:00:00'));

    $component = mediaTable()
        ->call('setQuickFilter', 'type', 'image')
        ->call('setQuickFilter', 'month', '2026-05');

    expect($component->instance()->rows->getCollection()->map(fn ($r) => $r->name)->all())->toBe(['may-photo.jpg']);

    $component->call('setQuickFilter', 'type', null);

    expect($component->instance()->rows->getCollection()->map(fn ($r) => $r->name)->sort()->values()->all())
        ->toBe(['may-doc.pdf', 'may-photo.jpg']);
});

test('an invalid month value is ignored', function () {
    createAttachmentWithMime('photo.jpg', 'image/jpeg');

    $component = mediaTable()->call('setQuickFilter', 'month', "2026-07' OR 1=1");

    expect($component->instance()->rows->getCollection()->map(fn ($r) => $r->name)->all())->toBe(['photo.jpg']);
});

test('upload months lists distinct months newest first', function () {
    createAttachmentWithMime('a.jpg', 'image/jpeg', Carbon::parse('2026-05-15 10:00:00'));
    createAttachmentWithMime('b.jpg', 'image/jpeg', Carbon::parse('2026-05-20 10:00:00'));
    createAttachmentWithMime('c.jpg', 'image/jpeg', Carbon::parse('2026-07-01 09:00:00'));

    expect(Attachment::uploadMonths())->toBe(['2026-07', '2026-05']);
});

test('uploading an image captures its dimensions', function () {
    livewire(MediaUploader::class)
        ->set('media', [UploadedFile::fake()->image('photo.jpg', 640, 480)])
        ->assertHasNoErrors();

    $attachment = Attachment::first();

    expect((int) $attachment->width)->toBe(640)
        ->and((int) $attachment->height)->toBe(480);
});

test('uploading a document does not store dimensions', function () {
    livewire(MediaUploader::class)
        ->set('media', [UploadedFile::fake()->create('document.pdf', 500, 'application/pdf')])
        ->assertHasNoErrors();

    $attachment = Attachment::first();

    expect($attachment->width)->toBeNull()
        ->and($attachment->height)->toBeNull();
});

test('alt text persists on an attachment', function () {
    $attachment = createAttachmentWithMime('photo.jpg', 'image/jpeg');

    $attachment->update(['alt_text' => 'A blue square with white text']);

    expect(Attachment::find($attachment->id)->alt_text)->toBe('A blue square with white text');
});

class CustomTableAttachment extends Attachment
{
    public static $customTable = true;

    public static bool $usesMeta = false;

    protected $guarded = [];

    protected $table = 'custom_table_attachments';
}
