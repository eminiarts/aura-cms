<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class UpdateSchemaFromMigration extends Command
{
    protected const STANDARD_TABLE_METHODS = [
        'id',
        'softDeletes',
        'timestamps',
    ];

    protected $description = 'Update the database schema based on the provided migration file';

    protected $signature = 'aura:schema-update
        {migration? : Path to the migration file to sync the table with}
        {--drop : Drop columns that are missing from the migration file}
        {--force : Do not ask for confirmation before dropping columns}';

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

            return self::FAILURE;
        }

        $table = $this->getTableNameFromMigration($migrationFile);

        if (! $table) {
            $this->error('Unable to determine table name from the migration.');

            return self::FAILURE;
        }

        $desiredColumns = $this->getDesiredColumnsFromMigration($migrationFile);

        if ($desiredColumns === null) {
            $this->error("Unable to safely parse columns from '{$migrationFile}'. Aborting without touching '{$table}'.");

            return self::FAILURE;
        }

        // A failed or partial parse would look like "the table has no columns",
        // which previously dropped every existing column. Abort instead.
        if ($desiredColumns === []) {
            $this->error("No columns could be parsed from '{$migrationFile}'. Aborting without touching '{$table}'.");

            return self::FAILURE;
        }

        if (! Schema::hasTable($table)) {
            $this->info("Table '{$table}' does not exist. Running the migration...");

            Artisan::call('migrate');

            $this->info("Migration completed. Table '{$table}' has been created.");

            return self::SUCCESS;
        }

        $existingColumns = Schema::getColumnListing($table);

        $addColumns = array_values(array_diff(array_keys($desiredColumns), $existingColumns));
        $dropColumns = array_values(array_diff(
            $existingColumns,
            array_keys($desiredColumns),
            ['id', 'created_at', 'updated_at', 'deleted_at']
        ));

        $this->line("Table '{$table}':");
        $this->line('  add:  '.($addColumns === [] ? '-' : implode(', ', $addColumns)));
        $this->line('  drop: '.($dropColumns === [] ? '-' : implode(', ', $dropColumns)));

        if ($dropColumns !== [] && ! $this->option('drop')) {
            $this->warn('Keeping '.implode(', ', $dropColumns).'. Pass --drop to remove them.');
            $dropColumns = [];
        }

        if ($dropColumns !== [] && ! $this->option('force') && ! confirm(
            label: 'Drop '.implode(', ', $dropColumns).'? The data in those columns is lost.',
            default: false
        )) {
            $dropColumns = [];
        }

        if ($addColumns === [] && $dropColumns === []) {
            $this->info('Nothing to do, the schema already matches the migration file.');

            return self::SUCCESS;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($addColumns, $desiredColumns, $dropColumns) {
            foreach ($addColumns as $column) {
                $blueprint->{$desiredColumns[$column]['type']}($column)->nullable();
            }

            if ($dropColumns !== []) {
                $blueprint->dropColumn($dropColumns);
            }
        });

        $this->info('Schema updated successfully based on the migration file.');

        return self::SUCCESS;
    }

    protected function getDesiredColumnsFromMigration(string $migrationFile): ?array
    {
        $migration = file_get_contents($migrationFile);

        if ($migration === false || ! preg_match(
            '/Schema::create\(\s*([\'\"])[a-zA-Z0-9_-]+\1\s*,\s*function\s*\(\s*Blueprint\s+\$table\s*\)\s*\{(?<schema>[\s\S]*?)^\s*\}\);/m',
            $migration,
            $matches
        )) {
            return null;
        }

        $columns = [];

        foreach (preg_split('/;/', $matches['schema']) as $statement) {
            if (! str_contains($statement, '$table->')) {
                continue;
            }

            if (! preg_match('/^\s*\$table->([a-zA-Z][a-zA-Z0-9_]*)\((.*?)\)(?:->.*)?\s*$/s', trim($statement), $matches)) {
                return null;
            }

            $method = $matches[1];
            $arguments = $matches[2];

            if (in_array($method, self::STANDARD_TABLE_METHODS, true)) {
                if (trim($arguments) !== '') {
                    return null;
                }

                continue;
            }

            if (! preg_match('/^\s*([\'\"])([a-zA-Z0-9_-]+)\1\s*$/', $arguments, $matches)) {
                return null;
            }

            $columns[$matches[2]] = ['type' => $method];
        }

        return $columns;
    }

    protected function getTableNameFromMigration(string $migration): ?string
    {
        $body = file_get_contents($migration);

        if ($body !== false && preg_match('/Schema::create\(\s*([\'\"])([a-zA-Z0-9_-]+)\1/', $body, $matches)) {
            return $matches[2];
        }

        return null;
    }
}
