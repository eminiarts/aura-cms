<?php

use Aura\Base\Fields\Text;
use Aura\Base\Listeners\ModifyDatabaseMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

uses(RefreshDatabase::class);

/**
 * Writes a throwaway `create schema_update_targets` migration outside
 * database/migrations, so it cannot collide with the migration files other
 * command tests wipe.
 */
function writeSchemaUpdateMigration(string $path, array $columnLines): void
{
    $columns = implode("\n", array_map(fn ($line) => '            '.$line, $columnLines));

    File::put($path, implode("\n", [
        '<?php',
        '',
        'use Illuminate\Database\Migrations\Migration;',
        'use Illuminate\Database\Schema\Blueprint;',
        'use Illuminate\Support\Facades\Schema;',
        '',
        'return new class extends Migration',
        '{',
        '    public function up(): void',
        '    {',
        "        Schema::create('schema_update_targets', function (Blueprint \$table) {",
        $columns,
        '        });',
        '    }',
        '};',
        '',
    ]));
}

function generatedSchemaForSchemaUpdate(array $fields): string
{
    $listener = app(ModifyDatabaseMigration::class);
    $method = (new ReflectionClass($listener))->getMethod('generateSchema');
    $method->setAccessible(true);

    return $method->invoke($listener, collect($fields));
}

function runPintForSchemaUpdateMigration(string $migrationFile): void
{
    $packagePath = dirname(__DIR__, 3);
    (new Process([PHP_BINARY, $packagePath.'/vendor/bin/pint', '--config='.$packagePath.'/pint.json', $migrationFile], $packagePath))
        ->mustRun();
}

beforeEach(function () {
    Schema::create('schema_update_targets', function (Blueprint $table) {
        $table->id();
        $table->string('title')->nullable();
        $table->string('legacy')->nullable();
        $table->foreignId('user_id')->nullable();
        $table->foreignId('team_id')->nullable();
        $table->timestamps();
    });

    $this->migrationFile = sys_get_temp_dir().'/aura_schema_update_'.uniqid().'.php';
});

afterEach(function () {
    Schema::dropIfExists('schema_update_targets');

    if (File::exists($this->migrationFile)) {
        File::delete($this->migrationFile);
    }
});

it('aborts without touching the table when no columns can be parsed', function () {
    writeSchemaUpdateMigration($this->migrationFile, []);

    $this->artisan('aura:schema-update', ['migration' => $this->migrationFile])
        ->assertExitCode(1);

    expect(Schema::hasColumn('schema_update_targets', 'title'))->toBeTrue();
    expect(Schema::hasColumn('schema_update_targets', 'legacy'))->toBeTrue();
});

it('adds missing columns but keeps unlisted ones without --drop', function () {
    writeSchemaUpdateMigration($this->migrationFile, [
        "\$table->string('title')->nullable();",
        "\$table->string('subtitle')->nullable();",
    ]);

    $this->artisan('aura:schema-update', ['migration' => $this->migrationFile])
        ->assertExitCode(0);

    expect(Schema::hasColumn('schema_update_targets', 'subtitle'))->toBeTrue();
    expect(Schema::hasColumn('schema_update_targets', 'legacy'))->toBeTrue();
});

it('syncs hyphenated field slugs emitted by the resource editor', function () {
    DB::table('schema_update_targets')->insert(['title' => 'Keep this record', 'user_id' => 42, 'team_id' => 24]);
    $schema = generatedSchemaForSchemaUpdate([
        ['slug' => 'title', 'type' => Text::class],
        ['slug' => 'release-date', 'type' => Text::class],
    ]);
    writeSchemaUpdateMigration($this->migrationFile, array_filter(explode(PHP_EOL, trim($schema))));
    runPintForSchemaUpdateMigration($this->migrationFile);

    $this->artisan('aura:schema-update', [
        'migration' => $this->migrationFile,
        '--drop' => true,
        '--force' => true,
    ])->assertSuccessful();

    expect(Schema::hasColumn('schema_update_targets', 'release-date'))->toBeTrue()
        ->and(DB::table('schema_update_targets')->value('title'))->toBe('Keep this record')
        ->and(DB::table('schema_update_targets')->value('user_id'))->toBe(42);
});

it('drops unlisted columns with --drop --force', function () {
    writeSchemaUpdateMigration($this->migrationFile, [
        "\$table->string('title')->nullable();",
    ]);

    $this->artisan('aura:schema-update', [
        'migration' => $this->migrationFile,
        '--drop' => true,
        '--force' => true,
    ])->assertExitCode(0);

    expect(Schema::hasColumn('schema_update_targets', 'title'))->toBeTrue();
    expect(Schema::hasColumn('schema_update_targets', 'legacy'))->toBeFalse();
});

it('keeps relation columns and persisted data from a formatted listener schema during a forced sync', function () {
    config(['aura.teams' => true]);

    DB::table('schema_update_targets')->insert([
        'title' => 'Persisted title',
        'legacy' => 'Remove me',
        'user_id' => 42,
        'team_id' => 24,
    ]);

    $schema = generatedSchemaForSchemaUpdate([
        ['slug' => 'title', 'type' => Text::class],
    ]);

    writeSchemaUpdateMigration($this->migrationFile, array_filter(explode(PHP_EOL, trim($schema))));
    runPintForSchemaUpdateMigration($this->migrationFile);

    expect(File::get($this->migrationFile))
        ->toContain("\$table->foreignId('user_id');")
        ->toContain("\$table->foreignId('team_id');");

    $this->artisan('aura:schema-update', [
        'migration' => $this->migrationFile,
        '--drop' => true,
        '--force' => true,
    ])->assertExitCode(0);

    $record = DB::table('schema_update_targets')->first();

    expect(Schema::hasColumn('schema_update_targets', 'title'))->toBeTrue()
        ->and(Schema::hasColumn('schema_update_targets', 'user_id'))->toBeTrue()
        ->and(Schema::hasColumn('schema_update_targets', 'team_id'))->toBeTrue()
        ->and(Schema::hasColumn('schema_update_targets', 'legacy'))->toBeFalse()
        ->and($record->title)->toBe('Persisted title')
        ->and($record->user_id)->toBe(42)
        ->and($record->team_id)->toBe(24);
});

it('keeps double-quoted relation columns and their persisted data during a forced standalone sync', function () {
    DB::table('schema_update_targets')->insert([
        'title' => 'Persisted title',
        'legacy' => 'Remove me',
        'user_id' => 42,
        'team_id' => 24,
    ]);

    writeSchemaUpdateMigration($this->migrationFile, [
        "\$table->string('title')->nullable();",
        '$table->foreignId("user_id")->nullable();',
        '$table->foreignId("team_id")->nullable();',
    ]);

    $this->artisan('aura:schema-update', [
        'migration' => $this->migrationFile,
        '--drop' => true,
        '--force' => true,
    ])->assertExitCode(0);

    $record = DB::table('schema_update_targets')->first();

    expect(Schema::hasColumn('schema_update_targets', 'title'))->toBeTrue()
        ->and(Schema::hasColumn('schema_update_targets', 'user_id'))->toBeTrue()
        ->and(Schema::hasColumn('schema_update_targets', 'team_id'))->toBeTrue()
        ->and(Schema::hasColumn('schema_update_targets', 'legacy'))->toBeFalse()
        ->and($record->title)->toBe('Persisted title')
        ->and($record->user_id)->toBe(42)
        ->and($record->team_id)->toBe(24);
});

it('aborts a forced sync when a column declaration has extra arguments', function () {
    DB::table('schema_update_targets')->insert([
        'title' => 'Persisted title',
        'legacy' => 'Keep me',
        'user_id' => 42,
        'team_id' => 24,
    ]);

    writeSchemaUpdateMigration($this->migrationFile, [
        "\$table->string('title', 255)->nullable();",
        "\$table->foreignId('user_id')->nullable();",
        "\$table->foreignId('team_id')->nullable();",
    ]);

    $this->artisan('aura:schema-update', [
        'migration' => $this->migrationFile,
        '--drop' => true,
        '--force' => true,
    ])->assertExitCode(1);

    $record = DB::table('schema_update_targets')->first();

    expect(Schema::hasColumn('schema_update_targets', 'title'))->toBeTrue()
        ->and(Schema::hasColumn('schema_update_targets', 'legacy'))->toBeTrue()
        ->and(Schema::hasColumn('schema_update_targets', 'user_id'))->toBeTrue()
        ->and(Schema::hasColumn('schema_update_targets', 'team_id'))->toBeTrue()
        ->and($record->title)->toBe('Persisted title')
        ->and($record->legacy)->toBe('Keep me')
        ->and($record->user_id)->toBe(42)
        ->and($record->team_id)->toBe(24);
});

it('fails when the migration file does not exist', function () {
    $this->artisan('aura:schema-update', ['migration' => sys_get_temp_dir().'/aura-missing-migration.php'])
        ->assertExitCode(1);
});
