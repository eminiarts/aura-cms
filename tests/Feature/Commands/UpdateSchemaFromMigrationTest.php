<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use function Pest\Laravel\artisan;

beforeEach(function () {
    // Set up the necessary environment, such as database migrations
    // and any needed configuration
    Schema::dropAllTables();
});

it('shows an error if migration file does not exist', function () {
    $this->artisan('aura:schema-update', ['migration' => 'non_existent_migration.php'])
        ->expectsOutput('Migration file does not exist.')
        ->assertExitCode(0);
});

it('shows an error if migration class cannot be loaded', function () {
    $migrationPath = database_path('migrations/2021_01_01_000000_non_existent_migration.php');
    file_put_contents($migrationPath, "<?php\n\nclass FakeMigration {}\n");
    
    $this->artisan('aura:schema-update', ['migration' => $migrationPath])
        ->expectsOutput('Unable to load migration class.')
        ->assertExitCode(0);

    unlink($migrationPath);
});

it('shows an error if migration class does not have an up method', function () {
    $migrationPath = database_path('migrations/2021_01_01_000001_invalid_migration.php');
    file_put_contents($migrationPath, "<?php\n\nuse Illuminate\Database\Migrations\Migration;\n\nclass InvalidMigration extends Migration {}\n");

    $this->artisan('aura:schema-update', ['migration' => $migrationPath])
        ->expectsOutput('The migration class does not have an up method.')
        ->assertExitCode(0);

    unlink($migrationPath);
});

it('shows an error if table name cannot be determined from migration', function () {
    $migrationPath = database_path('migrations/2021_01_01_000002_no_table_migration.php');
    file_put_contents($migrationPath, "<?php\n\nuse Illuminate\Database\Migrations\Migration;\nuse Illuminate\Database\Schema\Blueprint;\nuse Illuminate\Support\Facades\Schema;\n\nclass NoTableMigration extends Migration {\n    public function up() {\n        // No Schema::create call\n    }\n}\n");

    $this->artisan('aura:schema-update', ['migration' => $migrationPath])
        ->expectsOutput('Unable to determine table name from the migration.')
        ->assertExitCode(0);

    unlink($migrationPath);
});

it('updates the schema based on a valid migration', function () {
    $migrationPath = database_path('migrations/2021_01_01_000003_create_users_table.php');
    file_put_contents($migrationPath, "<?php\n\nuse Illuminate\Database\Migrations\Migration;\nuse Illuminate\Database\Schema\Blueprint;\nuse Illuminate\Support\Facades\Schema;\n\nclass CreateUsersTable extends Migration {\n    public function up() {\n        Schema::create('users', function (Blueprint \$table) {\n            \$table->id();\n            \$table->string('name');\n            \$table->timestamps();\n        });\n    }\n}\n");

    $this->artisan('migrate');
    
    $this->artisan('aura:schema-update', ['migration' => $migrationPath])
        ->expectsOutput('Schema updated successfully based on the migration file.')
        ->assertExitCode(0);

    expect(Schema::hasTable('users'))->toBeTrue();
    expect(Schema::hasColumn('users', 'name'))->toBeTrue();
    expect(Schema::hasColumn('users', 'id'))->toBeTrue();
    expect(Schema::hasColumn('users', 'created_at'))->toBeTrue();
    expect(Schema::hasColumn('users', 'updated_at'))->toBeTrue();

    unlink($migrationPath);
});