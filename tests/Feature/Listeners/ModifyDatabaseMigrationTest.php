<?php

use Aura\Base\Events\SaveFields;
use Aura\Base\Fields\Text;
use Aura\Base\Listeners\ModifyDatabaseMigration;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

function generatedModifyDatabaseMigrationSchema(): string
{
    $listener = app(ModifyDatabaseMigration::class);
    $method = (new ReflectionClass($listener))->getMethod('generateSchema');
    $method->setAccessible(true);

    return $method->invoke($listener, collect([
        ['slug' => 'title', 'type' => Text::class],
    ]));
}

it('does not generate a team column when teams are disabled', function () {
    config(['aura.teams' => false]);

    expect(generatedModifyDatabaseMigrationSchema())
        ->toContain('$table->foreignId("user_id");')
        ->not->toContain('team_id');
});

it('reports a failed schema sync and restores the migration files', function (bool $migrationExisted) {
    $originalDatabasePath = app()->databasePath();
    $temporaryPath = sys_get_temp_dir().'/aura-listener-'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists($temporaryPath.'/migrations');
    $migrationFile = $temporaryPath.'/migrations/2026_01_01_000000_create_schema_failure_table.php';
    $original = <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('schema_failure', function (Blueprint $table) {
            $table->string('old_field');
        });
    }
};
PHP;
    if ($migrationExisted) {
        File::put($migrationFile, $original);
    }
    app()->useDatabasePath($temporaryPath);
    Process::fake();
    app(Kernel::class)->registerCommand(new class extends Command
    {
        protected $signature = 'aura:schema-update {migration?} {--drop} {--force}';

        public function handle(): int
        {
            $this->error('The migration could not be parsed.');

            return self::FAILURE;
        }
    });

    $model = new class
    {
        public static bool $customTable = true;

        public function getTable(): string
        {
            return 'schema_failure';
        }
    };

    try {
        $event = new SaveFields([['slug' => 'new_field', 'type' => Text::class]], [], $model);

        expect(fn () => app(ModifyDatabaseMigration::class)->handle($event))
            ->toThrow(RuntimeException::class, 'Schema update failed');
        if ($migrationExisted) {
            expect(File::get($migrationFile))->toBe($original);
        } else {
            expect(File::files($temporaryPath.'/migrations'))->toBeEmpty();
        }
    } finally {
        app()->useDatabasePath($originalDatabasePath);
        File::deleteDirectory($temporaryPath);
    }
})->with([true, false]);
