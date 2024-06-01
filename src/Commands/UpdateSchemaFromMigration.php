<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use function Laravel\Prompts\select;

class UpdateSchemaFromMigration extends Command
{
    protected $signature = 'aura:schema-update {migration?}';
    protected $description = 'Update the database schema based on the provided migration file';

    public function handle()
    {
        $migrationFile = $this->argument('migration');
        
        if (!$migrationFile) {
            $migrationFiles = glob(database_path('migrations/*.php'));
            $migrationFile = select(
                label: 'Which migration file would you like to use?',
                options: array_combine($migrationFiles, array_map('basename', $migrationFiles))
            );
        }


        if (!file_exists($migrationFile)) {
            $this->error("Migration file does not exist.");
            return;
        }

        $migrationClass = $this->getMigrationClass($migrationFile);


        if (!$migrationClass) {
            $this->error("Unable to load migration class.");
            return;
        }

        $migration = new $migrationClass;

        if (!method_exists($migration, 'up')) {
            $this->error("The migration class does not have an up method.");
            return;
        }

        // Get the table name from the migration (assuming only one table is created)
        $schemaBuilder = Schema::getFacadeRoot();

        $table = $this->getTableNameFromMigration($migration);

        if (!$table) {
            $this->error("Unable to determine table name from the migration.");
            return;
        }


        $existingColumns = DB::getSchemaBuilder()->getColumnListing($table);
        $desiredColumns = $this->getDesiredColumnsFromMigration($migration);

        $newColumns = array_diff(array_keys($desiredColumns), $existingColumns);

        $dropColumns = array_diff($existingColumns, array_keys($desiredColumns));
        $dropColumns = array_diff($dropColumns, ['id', 'created_at', 'updated_at', 'deleted_at']);


        // dd($table, $migration, $migrationClass, $existingColumns, $desiredColumns, $newColumns, $dropColumns);


        // Add new columns
        Schema::table($table, function (Blueprint $table) use ($existingColumns, $desiredColumns) {
            $newColumns = array_diff(array_keys($desiredColumns), $existingColumns);

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

        $this->info("Schema updated successfully based on the migration file.");
    }

   protected function getMigrationClass($file)
    {
        require_once $file;

        $classes = get_declared_classes();
        $migrationClass = null;

        foreach ($classes as $class) {
            $reflectionClass = new \ReflectionClass($class);
            if ($reflectionClass->isSubclassOf('Illuminate\Database\Migrations\Migration') && !$reflectionClass->isAbstract()) {
                $migrationClass = $class;
                break;
            }
        }

        return $migrationClass;
    }

   protected function getTableNameFromMigration($migration)
    {
        $reflection = new \ReflectionClass($migration);
        $method = $reflection->getMethod('up');
        $body = file($method->getFileName());
        $lines = array_slice($body, $method->getStartLine(), $method->getEndLine() - $method->getStartLine());

        foreach ($lines as $line) {
            if (preg_match('/Schema::create\(\'([a-zA-Z0-9_]+)\'/', $line, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    protected function getDesiredColumnsFromMigration($migration)
    {
        $reflection = new \ReflectionClass($migration);
        $method = $reflection->getMethod('up');
        $body = file($method->getFileName());
        $lines = array_slice($body, $method->getStartLine(), $method->getEndLine() - $method->getStartLine());

        $columns = [];
        foreach ($lines as $line) {
            if (preg_match('/\$table->([a-zA-Z]+)\(\'([a-zA-Z0-9_]+)\'\)/', $line, $matches)) {
                $columns[$matches[2]] = ['type' => $matches[1]];
            }
        }

        return $columns;
    }
}
