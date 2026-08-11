<?php

use Aura\Base\Contracts\FieldValueContext;
use Aura\Base\Events\ResourceUpdated;
use Aura\Base\Fields\Text;
use Aura\Base\Resource;
use Aura\Base\ResourcePersistence\ResourceWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

class Core17WriterDocument extends Resource
{
    public static string $type = 'Core17WriterDocument';

    public static function getFields(): array
    {
        return [
            ['name' => 'Title', 'slug' => 'title', 'type' => Text::class, 'validation' => 'required|string|max:255'],
            ['name' => 'Notes', 'slug' => 'notes', 'type' => Text::class, 'validation' => 'nullable|string'],
            ['name' => 'Protected note', 'slug' => 'protected_note', 'type' => Text::class, 'disabled' => true],
        ];
    }
}

beforeEach(function (): void {
    $this->actingAs($this->actor = createSuperAdmin());
    $this->writer = app(ResourceWriter::class);
});

test('the explicit writer creates and updates physical and meta fields', function (): void {
    $resource = $this->writer->create(new Core17WriterDocument, [
        'title' => 'Created',
        'notes' => 'Initial meta',
    ]);

    expect($resource->title)->toBe('Created')
        ->and($resource->getMeta('notes'))->toBe('Initial meta');

    $resource = $this->writer->update($resource, [
        'title' => 'Updated',
        'notes' => 'Updated meta',
    ]);

    expect($resource->title)->toBe('Updated')
        ->and($resource->getMeta('notes'))->toBe('Updated meta')
        ->and(DB::table('meta')
            ->where('metable_type', Core17WriterDocument::class)
            ->where('metable_id', $resource->getKey())
            ->where('key', 'notes')
            ->count())->toBe(1);
});

test('the writer rejects unknown and read only input', function (array $input, string $key): void {
    expect(fn () => $this->writer->create(new Core17WriterDocument, $input))
        ->toThrow(ValidationException::class, "The [{$key}] field is not writable.");
})->with([
    'unknown field' => [['title' => 'Valid', 'forged' => 'value'], 'forged'],
    'disabled field' => [['title' => 'Valid', 'protected_note' => 'value'], 'protected_note'],
]);

test('validation happens before any physical or meta persistence', function (): void {
    expect(fn () => $this->writer->create(new Core17WriterDocument, ['notes' => 'No title']))
        ->toThrow(ValidationException::class);

    expect(Core17WriterDocument::withoutGlobalScopes()->count())->toBe(0)
        ->and(DB::table('meta')->where('metable_type', Core17WriterDocument::class)->count())->toBe(0);
});

test('save with fields persists normalization and meta while model events are suppressed', function (): void {
    $resource = $this->writer->create(new Core17WriterDocument, [
        'title' => 'Before quiet write',
        'notes' => 'Before quiet meta',
    ]);
    Event::fake([ResourceUpdated::class]);

    Core17WriterDocument::withoutEvents(function () use (&$resource): void {
        $resource = $this->writer->saveWithFields(
            $resource,
            ['title' => 'Quiet write', 'notes' => 'Quiet meta'],
            FieldValueContext::Edit,
        );
    });

    expect($resource->title)->toBe('Quiet write')
        ->and($resource->getMeta('notes'))->toBe('Quiet meta');
    Event::assertNotDispatched(ResourceUpdated::class);
});

test('ordinary meta values are persisted with one batch upsert', function (): void {
    $queries = [];
    DB::listen(function ($query) use (&$queries): void {
        if (str_contains(strtolower($query->sql), 'meta')) {
            $queries[] = strtolower($query->sql);
        }
    });

    $this->writer->create(new Core17WriterDocument, [
        'title' => 'Batched',
        'notes' => 'One meta value',
    ]);

    expect(collect($queries)->filter(fn (string $sql): bool => str_contains($sql, 'insert into "meta"')
        || str_contains($sql, 'insert into `meta`'))->count())->toBe(1);
});
