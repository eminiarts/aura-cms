<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\select;

class UpdateSchemaFromMigration extends Command
{
    protected $description = 'Update the database schema based on the provided migration file';

    protected $signature = 'aura:schema-update {migration?}';

    public function handle()
    {
        $migrationFile = $this->argument('migration');

        if (! $migrationFile) {
            $migrationFiles = glob(database_path('migrations/*.php'));
            $migrationFile = select(
                label: 'Which migration file would you like to use?',
                options: array_combine($migrationFiles, array_map('basename', $migrationFiles))
            );
        }

        if (! file_exists($migrationFile)) {
            $this->error('Migration file does not exist.');

            return;
        }

        $table = $this->getTableNameFromMigration($migrationFile);

        if (! $table) {
            $this->error('Unable to determine table name from the migration.');

            return;
        }

        $existingColumns = DB::getSchemaBuilder()->getColumnListing($table);
        $desiredColumns = $this->getDesiredColumnsFromMigration($migrationFile);

        $newColumns = array_diff(array_keys($desiredColumns), $existingColumns);

        $dropColumns = array_diff($existingColumns, array_keys($desiredColumns));
        $dropColumns = array_diff($dropColumns, ['id', 'created_at', 'updated_at', 'deleted_at']);


        if (! Schema::hasTable($table)) {
            $this->info("Table '{$table}' does not exist. Running the migration...");

            // Run the migration
            Artisan::call('migrate');

            $this->info("Migration completed. Table '{$table}' has been created.");

            return;
        }

        // Add new columns
        Schema::table($table, function (Blueprint $table) use ($existingColumns, $desiredColumns) {
            $newColumns = array_diff(array_keys($desiredColumns), $existingColumns);

            ray($newColumns, $existingColumns, $desiredColumns)->red();

            foreach ($newColumns as $column) {

                $table->{$desiredColumns[$column]['type']}($column)->nullable();
            }

            // Drop outdated columns
            $dropColumns = array_diff($existingColumns, array_keys($desiredColumns));
            $dropColumns = array_diff($dropColumns, ['id', 'created_at', 'updated_at', 'deleted_at']);

            foreach ($dropColumns as $column) {
                $table->dropColumn($column);
            }
        });

        // Modify existing columns if needed
        Schema::table($table, function (Blueprint $table) use ($desiredColumns) {
            foreach ($desiredColumns as $column => $definition) {
                $table->{$definition['type']}($column)->nullable()->change();
            }
        });

        $this->info('Schema updated successfully based on the migration file.');
    }

    protected function getDesiredColumnsFromMigration($migrationFile)
    {
        $body = file($migrationFile);
        $upMethodStarted = false;
        $columns = [];

        foreach ($body as $line) {
            if (preg_match('/public function up\(\)/', $line)) {
                $upMethodStarted = true;

                continue;
            }

            if ($upMethodStarted) {
                if (preg_match('/\}/', $line)) {
                    break;
                }

                if (preg_match('/\$table->([a-zA-Z]+)\(\'([a-zA-Z0-9_]+)\'\)/', $line, $matches)) {
                    $columns[$matches[2]] = ['type' => $matches[1]];
                }
            }
        }

        return $columns;
    }

    protected function getTableNameFromMigration($migration)
    {
        $body = file($migration);

        foreach ($body as $line) {
            if (preg_match('/Schema::create\(\'([a-zA-Z0-9_]+)\'/', $line, $matches)) {
                return $matches[1];
            }
        }
    }
}
