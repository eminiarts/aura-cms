<?php

use Aura\Base\Events\SaveFields as SaveFieldsEvent;
use Aura\Base\Fields\Text;
use Aura\Base\Resource;
use Aura\Base\Traits\SaveFields;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;

function resourceEditorPersistenceFixture(string $returnType = ': array', bool $literal = true): array
{
    $name = 'EditorPersistence'.bin2hex(random_bytes(5));
    $path = sys_get_temp_dir().'/'.$name.'.php';
    $body = $literal ? "return [['name' => 'Title', 'slug' => 'title', 'type' => '\\Aura\\Base\\Fields\\Text']];" : 'return parent::getFields();';
    $source = "<?php\nclass {$name} extends \\Aura\\Base\\Resource {\n"
        ."    public static string \$type = 'EditorFixture';\n"
        ."    public static function getFields(){$returnType}\n    {\n        {$body}\n    }\n}\n";
    File::put($path, $source);
    require $path;

    $editor = new class(new $name)
    {
        use SaveFields;

        public array $mappedFields = [];

        public array $notifications = [];

        public function __construct(public Resource $model) {}

        public function notify(string $message): void
        {
            $this->notifications[] = $message;
        }
    };

    return [$editor, $path, $source];
}

it('persists a typed getFields method before dispatching schema changes', function () {
    [$editor, $path] = resourceEditorPersistenceFixture();
    Event::forget(SaveFieldsEvent::class);
    Event::listen(SaveFieldsEvent::class, function () use ($path) {
        expect(File::get($path))->toContain('release-date');
    });

    try {
        $editor->saveFields([['name' => 'Release date', 'slug' => 'release-date', 'type' => Text::class]]);

        expect(File::get($path))->toContain('release-date')
            ->and($editor->notifications)->toBe(['Saved successfully.']);
    } finally {
        File::delete($path);
    }
});

it('restores the resource definition when its schema update fails', function () {
    [$editor, $path, $source] = resourceEditorPersistenceFixture();
    Event::forget(SaveFieldsEvent::class);
    Event::listen(SaveFieldsEvent::class, function () use ($path) {
        expect(File::get($path))->toContain('release-date');
        throw new RuntimeException('Schema update failed.');
    });

    try {
        expect(fn () => $editor->saveFields([['name' => 'Release date', 'slug' => 'release-date', 'type' => Text::class]]))
            ->toThrow(RuntimeException::class, 'Schema update failed.');

        expect(File::get($path))->toBe($source)
            ->and($editor->notifications)->toBeEmpty();
    } finally {
        File::delete($path);
    }
});

it('does not dispatch schema changes when getFields cannot be rewritten', function () {
    [$editor, $path, $source] = resourceEditorPersistenceFixture(': array', false);
    Event::fake([SaveFieldsEvent::class]);

    try {
        expect(fn () => $editor->saveFields([['name' => 'Release date', 'slug' => 'release-date', 'type' => Text::class]]))
            ->toThrow(RuntimeException::class);

        expect(File::get($path))->toBe($source);
        Event::assertNotDispatched(SaveFieldsEvent::class);
    } finally {
        File::delete($path);
    }
});
