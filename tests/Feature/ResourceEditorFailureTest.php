<?php

use App\Aura\Resources\Core10ComputedFieldsResource;
use App\Aura\Resources\Core10RollbackResource;
use Aura\Base\Events\SaveFields as SaveFieldsEvent;
use Aura\Base\Fields\Text;
use Aura\Base\Listeners\CreateDatabaseMigration;
use Aura\Base\Listeners\ModifyDatabaseMigration;
use Aura\Base\Resource;
use Aura\Base\Traits\SaveFields;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;

class Core10SaveFieldsHarness
{
    use SaveFields;

    public array $mappedFields = [];

    public $model;

    public array $notifications = [];

    public function notify(string $message): void
    {
        $this->notifications[] = $message;
    }
}

class Core10MigrationFailureResource extends Resource
{
    public static $customTable = true;

    protected $table = 'core_10_migration_failure_values';

    public static function getFields(): array
    {
        return [];
    }
}

test('resource field configuration is restored when a migration listener fails', function () {
    $resourceDirectory = app_path('Aura/Resources');
    $resourcePath = $resourceDirectory.'/Core10RollbackResource.php';
    File::ensureDirectoryExists($resourceDirectory);
    File::put($resourcePath, <<<'PHP'
<?php

namespace App\Aura\Resources;

use Aura\Base\Fields\Text;
use Aura\Base\Resource;

class Core10RollbackResource extends Resource
{
    public static $customTable = true;

    protected $table = 'core_10_rollback_values';

    public static function getFields(): array
    {
        return [
            ['name' => 'Name', 'slug' => 'name', 'type' => Text::class],
        ];
    }
}
PHP);
    require_once $resourcePath;

    $original = File::get($resourcePath);
    $harness = new Core10SaveFieldsHarness;
    $harness->model = new Core10RollbackResource;
    $harness->mappedFields = $harness->model->getFields();
    Event::forget(SaveFieldsEvent::class);
    Event::listen(SaveFieldsEvent::class, function () use ($original, $resourcePath): never {
        expect(File::get($resourcePath))->not->toBe($original);

        throw new RuntimeException('migration failed');
    });

    try {
        expect(fn () => $harness->saveFields([
            ['name' => 'Description', 'slug' => 'description', 'type' => Text::class],
        ]))->toThrow(RuntimeException::class, 'migration failed')
            ->and(File::get($resourcePath))->toBe($original)
            ->and($harness->notifications)->not->toContain('Saved successfully.');
    } finally {
        Event::forget(SaveFieldsEvent::class);
        File::delete($resourcePath);
    }
});

test('resource editing stops before schema changes when getFields cannot be rewritten', function () {
    $resourceDirectory = app_path('Aura/Resources');
    $resourcePath = $resourceDirectory.'/Core10ComputedFieldsResource.php';
    File::ensureDirectoryExists($resourceDirectory);
    File::put($resourcePath, <<<'PHP'
<?php

namespace App\Aura\Resources;

use Aura\Base\Fields\Text;
use Aura\Base\Resource;

class Core10ComputedFieldsResource extends Resource
{
    public static $customTable = true;

    protected $table = 'core_10_computed_field_values';

    public static function getFields(): array
    {
        $fields = [
            ['name' => 'Name', 'slug' => 'name', 'type' => Text::class],
        ];

        return $fields;
    }
}
PHP);
    require_once $resourcePath;

    $harness = new Core10SaveFieldsHarness;
    $harness->model = new Core10ComputedFieldsResource;
    $harness->mappedFields = $harness->model->getFields();
    Event::fake([SaveFieldsEvent::class]);

    try {
        expect(fn () => $harness->saveFields([
            ['name' => 'Description', 'slug' => 'description', 'type' => Text::class],
        ]))->toThrow(RuntimeException::class, 'Return statement not found')
            ->and(File::get($resourcePath))->toContain('return $fields;');

        Event::assertNotDispatched(SaveFieldsEvent::class);
    } finally {
        Event::forget(SaveFieldsEvent::class);
        File::delete($resourcePath);
    }
});

test('a generated migration is deleted and the failure is surfaced when formatting fails', function () {
    $before = collect(File::files(database_path('migrations')))->map->getPathname()->all();
    $listener = new class(app(Filesystem::class)) extends CreateDatabaseMigration
    {
        protected function runPint($migrationFile): void
        {
            throw new RuntimeException('formatter failed');
        }
    };
    $event = new SaveFieldsEvent([
        ['name' => 'Description', 'slug' => 'description', 'type' => Text::class],
    ], [], new Core10MigrationFailureResource);

    expect(fn () => $listener->handle($event))
        ->toThrow(RuntimeException::class, 'formatter failed');

    $after = collect(File::files(database_path('migrations')))->map->getPathname()->all();

    expect($after)->toBe($before);
});

test('a generated migration is deleted and the failure is surfaced when migration execution fails', function () {
    $before = collect(File::files(database_path('migrations')))->map->getPathname()->all();
    $listener = new class(app(Filesystem::class)) extends CreateDatabaseMigration
    {
        protected function runMigration(string $migrationFile): void
        {
            throw new RuntimeException('migration execution failed');
        }

        protected function runPint($migrationFile): void {}
    };
    $event = new SaveFieldsEvent([
        ['name' => 'Description', 'slug' => 'description', 'type' => Text::class],
    ], [], new Core10MigrationFailureResource);

    expect(fn () => $listener->handle($event))
        ->toThrow(RuntimeException::class, 'migration execution failed');

    $after = collect(File::files(database_path('migrations')))->map->getPathname()->all();

    expect($after)->toBe($before);
});

test('the single migration file is restored when schema synchronization fails', function () {
    $migrationPath = database_path('migrations/2099_01_01_000000_create_core_10_migration_failure_values_table.php');
    $original = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_10_migration_failure_values', function (Blueprint $table) {
            $table->id();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_10_migration_failure_values');
    }
};
PHP;
    File::put($migrationPath, $original);
    $listener = new class(app(Filesystem::class)) extends ModifyDatabaseMigration
    {
        protected function runPint($migrationFile): void {}

        protected function runSchemaUpdate(string $migrationFile): void
        {
            throw new RuntimeException('schema synchronization failed');
        }
    };
    $event = new SaveFieldsEvent([
        ['name' => 'Description', 'slug' => 'description', 'type' => Text::class],
    ], [], new Core10MigrationFailureResource);

    try {
        expect(fn () => $listener->handle($event))
            ->toThrow(RuntimeException::class, 'schema synchronization failed')
            ->and(File::get($migrationPath))->toBe($original);
    } finally {
        File::delete($migrationPath);
    }
});
