<?php

use App\Aura\Resources\Core10ComputedFieldsResource;
use App\Aura\Resources\Core10ConcurrentResource;
use App\Aura\Resources\Core10RollbackResource;
use Aura\Base\Events\SaveFields as SaveFieldsEvent;
use Aura\Base\Fields\Text;
use Aura\Base\Listeners\CreateDatabaseMigration;
use Aura\Base\Listeners\ModifyDatabaseMigration;
use Aura\Base\Resource;
use Aura\Base\Support\PackageTool;
use Aura\Base\Traits\SaveFields;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

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

test('two resource editor processes cannot commit stale source over a newer schema', function () {
    $resourceDirectory = app_path('Aura/Resources');
    $resourcePath = $resourceDirectory.'/Core10ConcurrentResource.php';
    $raceDirectory = storage_path('framework/testing/core-10-editor-race-'.getmypid());
    $databasePath = $raceDirectory.'/database.sqlite';
    $insideListener = $raceDirectory.'/inside-listener';
    $releaseListener = $raceDirectory.'/release-listener';
    $secondStarted = $raceDirectory.'/second-started';
    $firstResult = $raceDirectory.'/first-result';
    $secondResult = $raceDirectory.'/second-result';
    $connection = 'core_10_editor_race';
    $tableName = 'core_10_concurrent_values';
    $originalDefault = config('database.default');
    $firstPid = null;
    $secondPid = null;
    $waitForFile = static function (string $path): void {
        $deadline = microtime(true) + 10;

        while (! is_file($path) && microtime(true) < $deadline) {
            usleep(10_000);
        }

        if (! is_file($path)) {
            throw new RuntimeException("Timed out waiting for concurrency signal [{$path}].");
        }
    };

    File::ensureDirectoryExists($resourceDirectory);
    File::ensureDirectoryExists($raceDirectory);
    File::put($databasePath, '');
    File::put($resourcePath, <<<'PHP'
<?php

namespace App\Aura\Resources;

use Aura\Base\Fields\Text;
use Aura\Base\Resource;

class Core10ConcurrentResource extends Resource
{
    public static $customTable = true;

    protected $table = 'core_10_concurrent_values';

    public static function getFields(): array
    {
        return [
            ['name' => 'Name', 'slug' => 'name', 'type' => Text::class],
        ];
    }
}
PHP);
    require_once $resourcePath;

    config()->set("database.connections.{$connection}", [
        'driver' => 'sqlite',
        'database' => $databasePath,
        'prefix' => '',
        'foreign_key_constraints' => true,
        'busy_timeout' => 5000,
    ]);
    config()->set('database.default', $connection);
    DB::purge($connection);
    Schema::create($tableName, function (Blueprint $table): void {
        $table->id();
        $table->string('name')->nullable();
    });
    DB::disconnect($connection);

    Event::forget(SaveFieldsEvent::class);
    Event::listen(SaveFieldsEvent::class, function (SaveFieldsEvent $event) use (
        $connection,
        $insideListener,
        $releaseListener,
        $tableName,
        $waitForFile,
    ): void {
        $desiredColumns = collect($event->fields)->pluck('slug')->filter()->values()->all();

        if (in_array('from_a', $desiredColumns, true)) {
            File::put($insideListener, 'ready');
            $waitForFile($releaseListener);
        }

        DB::purge($connection);
        $existingColumns = Schema::getColumnListing($tableName);
        $columnsToAdd = array_values(array_diff($desiredColumns, $existingColumns));
        $columnsToDrop = array_values(array_diff($existingColumns, [...$desiredColumns, 'id']));

        Schema::table($tableName, function (Blueprint $table) use ($columnsToAdd, $columnsToDrop): void {
            foreach ($columnsToAdd as $column) {
                $table->string($column)->nullable();
            }

            foreach ($columnsToDrop as $column) {
                $table->dropColumn($column);
            }
        });
    });

    $harness = new Core10SaveFieldsHarness;
    $harness->model = new Core10ConcurrentResource;
    $harness->mappedFields = $harness->model->getFields();
    $harness->initializeResourceFieldsVersion();

    try {
        $firstPid = pcntl_fork();

        if ($firstPid === -1) {
            throw new RuntimeException('Unable to fork the first resource editor process.');
        }

        if ($firstPid === 0) {
            DB::purge($connection);

            try {
                $harness->saveFields([
                    ['name' => 'From A', 'slug' => 'from_a', 'type' => Text::class],
                ]);
                File::put($firstResult, 'success');
            } catch (Throwable $exception) {
                File::put($firstResult, 'failure:'.$exception->getMessage());
            }

            DB::disconnect($connection);
            exit(0);
        }

        $waitForFile($insideListener);
        $secondPid = pcntl_fork();

        if ($secondPid === -1) {
            throw new RuntimeException('Unable to fork the second resource editor process.');
        }

        if ($secondPid === 0) {
            File::put($secondStarted, 'ready');
            DB::purge($connection);

            try {
                $harness->saveFields([
                    ['name' => 'From B', 'slug' => 'from_b', 'type' => Text::class],
                ]);
                File::put($secondResult, 'success');
            } catch (Throwable $exception) {
                File::put($secondResult, 'failure:'.$exception->getMessage());
            }

            DB::disconnect($connection);
            exit(0);
        }

        $waitForFile($secondStarted);
        usleep(100_000);
        File::put($releaseListener, 'continue');
        pcntl_waitpid($firstPid, $firstStatus);
        pcntl_waitpid($secondPid, $secondStatus);
        $firstPid = null;
        $secondPid = null;
        DB::purge($connection);

        expect(pcntl_wexitstatus($firstStatus))->toBe(0)
            ->and(pcntl_wexitstatus($secondStatus))->toBe(0)
            ->and(File::get($firstResult))->toBe('success')
            ->and(File::get($secondResult))->toContain('Resource fields changed since this editor was opened')
            ->and(File::get($resourcePath))->toContain("'slug' => 'from_a'")
            ->and(File::get($resourcePath))->not->toContain("'slug' => 'from_b'")
            ->and(Schema::hasColumn($tableName, 'from_a'))->toBeTrue()
            ->and(Schema::hasColumn($tableName, 'from_b'))->toBeFalse()
            ->and(Schema::hasColumn($tableName, 'name'))->toBeFalse();
    } finally {
        File::put($releaseListener, 'continue');

        if (is_int($firstPid) && $firstPid > 0) {
            pcntl_waitpid($firstPid, $status);
        }

        if (is_int($secondPid) && $secondPid > 0) {
            pcntl_waitpid($secondPid, $status);
        }

        Event::forget(SaveFieldsEvent::class);
        DB::disconnect($connection);
        DB::purge($connection);
        config()->set('database.default', $originalDefault);
        File::delete($resourcePath);
        File::deleteDirectory($raceDirectory);
    }
})->skip(! function_exists('pcntl_fork'), 'The pcntl extension is required for the two-process resource editor contract.');

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

test('package tools resolve outside the testbench temporary base path and may be absent safely', function () {
    $pint = PackageTool::binary('pint');

    expect($pint)->not->toBe(base_path('vendor/bin/pint'));

    if ($pint !== null) {
        expect(is_file($pint))->toBeTrue();
    }

    expect(PackageTool::binary('definitely-not-an-installed-composer-binary'))->toBeNull();
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

test('multiple migration creation returns a unique exact path in the same second', function () {
    $before = collect(File::files(database_path('migrations')))->map->getPathname()->all();
    $listener = new class(app(Filesystem::class)) extends CreateDatabaseMigration
    {
        public array $executedPaths = [];

        protected function runMigration(string $migrationFile): void
        {
            $this->executedPaths[] = $migrationFile;
        }

        protected function runPint($migrationFile): void {}
    };
    $event = new SaveFieldsEvent([
        ['name' => 'Description', 'slug' => 'description', 'type' => Text::class],
    ], [], new Core10MigrationFailureResource);
    Date::setTestNow('2026-08-09 20:00:00');

    try {
        $listener->handle($event);
        $listener->handle($event);

        expect($listener->executedPaths)->toHaveCount(2)
            ->and($listener->executedPaths[0])->not->toBe($listener->executedPaths[1])
            ->and(File::exists($listener->executedPaths[0]))->toBeTrue()
            ->and(File::exists($listener->executedPaths[1]))->toBeTrue();
    } finally {
        Date::setTestNow();

        collect(File::files(database_path('migrations')))
            ->reject(fn ($file): bool => in_array($file->getPathname(), $before, true))
            ->each(fn ($file) => File::delete($file->getPathname()));
    }
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
