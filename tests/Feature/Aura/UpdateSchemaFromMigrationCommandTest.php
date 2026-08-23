<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

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

beforeEach(function () {
    Schema::create('schema_update_targets', function (Blueprint $table) {
        $table->id();
        $table->string('title')->nullable();
        $table->string('legacy')->nullable();
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

it('fails when the migration file does not exist', function () {
    $this->artisan('aura:schema-update', ['migration' => sys_get_temp_dir().'/aura-missing-migration.php'])
        ->assertExitCode(1);
});
