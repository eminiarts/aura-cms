<?php

use Aura\Base\Livewire\MediaUploader;
use Aura\Base\Resources\Attachment;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Fixtures\Media\Core20FailingAttachment;
use Aura\Base\Tests\Fixtures\Media\Core20PostInsertFailingAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs($this->actor = createSuperAdmin());
});

test('uploader requires authentication and attachment listing access on mount and hydration', function () {
    auth()->logout();
    Livewire::test(MediaUploader::class)->assertForbidden();

    $denied = User::factory()->create(config('aura.teams') ? ['current_team_id' => $this->actor->current_team_id] : []);
    $this->actingAs($denied);
    Livewire::test(MediaUploader::class)->assertForbidden();

    $this->actingAs($this->actor);
    $uploader = Livewire::test(MediaUploader::class);
    auth()->logout();
    $uploader->call('uploadPolicy')->assertForbidden();
});

test('uploader authorizes attachment creation before storing any bytes', function () {
    Gate::before(fn ($user, string $ability): ?bool => $ability === 'create' ? false : null);
    $file = UploadedFile::fake()->image('denied.jpg');

    Livewire::test(MediaUploader::class)
        ->set('media', [$file])
        ->assertForbidden();

    expect(Attachment::count())->toBe(0);
    Storage::disk('public')->assertMissing('media/denied.jpg');
    expect(Storage::disk('public')->allFiles('media'))->toBe([]);
});

test('uploader removes stored bytes when attachment persistence fails', function () {
    config()->set('aura.resources.attachment', Core20FailingAttachment::class);
    app('aura')::registerResources([Core20FailingAttachment::class]);

    Livewire::test(MediaUploader::class)
        ->set('media', [UploadedFile::fake()->image('orphan.jpg')])
        ->assertHasErrors('media.0');

    expect(Attachment::count())->toBe(0)
        ->and(Storage::disk('public')->allFiles('media'))->toBe([]);
});

test('uploader rejects foreign preselected attachments', function () {
    $foreignTeam = foreignTeam();
    $foreignAttributes = [
        'type' => Attachment::$type,
        'team_id' => $foreignTeam->getKey(),
        'user_id' => $this->actor->getKey(),
    ];

    expect(fn () => Attachment::withoutGlobalScopes()->create($foreignAttributes))
        ->toThrow(LogicException::class, 'Use createForTeamForSystem()');

    $foreign = Attachment::createForTeamForSystem($foreignTeam->getKey(), $foreignAttributes);

    expect(fn () => Livewire::test(MediaUploader::class, ['selected' => [(string) $foreign->getKey()]]))
        ->toThrow(Exception::class);
})->skip(fn () => ! config('aura.teams'), 'Cross-team authorization requires teams enabled.');

test('uploader locks its owner token', function () {
    $uploader = Livewire::test(MediaUploader::class);

    expect(fn () => $uploader->set('ownerToken', 'forged'))
        ->toThrow(Exception::class)
        ->and(fn () => $uploader->set('table', false))
        ->toThrow(Exception::class)
        ->and(fn () => $uploader->set('model', new Attachment))
        ->toThrow(Exception::class);
});

test('uploader rolls back a row inserted before a persistence listener fails', function () {
    config()->set('aura.resources.attachment', Core20PostInsertFailingAttachment::class);
    app('aura')::registerResources([Core20PostInsertFailingAttachment::class]);

    Livewire::test(MediaUploader::class)
        ->set('media', [UploadedFile::fake()->image('post-insert-orphan.jpg')])
        ->assertHasErrors('media.0');

    expect(Attachment::count())->toBe(0)
        ->and(Storage::disk('public')->allFiles('media'))->toBe([]);
});
