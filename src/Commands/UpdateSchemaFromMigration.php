<?php

namespace Aura\Base\Commands;

use Aura\Base\Schema\ColumnValuePreflight;
use Aura\Base\Schema\SchemaMigrationLock;
use Aura\Base\Schema\SchemaUpdatePlan;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

use function Laravel\Prompts\select;

class UpdateSchemaFromMigration extends Command
{
    protected $description = 'Update the database schema from the structured plan in a migration file';

    protected $signature = 'aura:schema-update {migration?}';

    public function handle(): int
    {
        try {
            $migrationFile = $this->resolveMigrationFile();
            $plan = SchemaUpdatePlan::fromMigrationFile($migrationFile);

            SchemaMigrationLock::run(
                'schema-update:'.Schema::getConnection()->getName().':'.$plan->table,
                fn () => $this->synchronize($migrationFile, $plan),
            );

            $this->info('Schema updated successfully based on the migration plan.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @param  array<int, string>  $newColumns
     * @param  array<int, string>  $changedColumns
     * @param  array<int, string>  $dropColumns
     */
    protected function preflight(
        SchemaUpdatePlan $plan,
        array $newColumns,
        array $changedColumns,
        array $dropColumns,
    ): void {
        foreach ($changedColumns as $column) {
            ColumnValuePreflight::assertTableColumnCanConvert($plan->table, $column, $plan->columns[$column]);
        }

        foreach ($newColumns as $column) {
            if (Schema::hasColumn($plan->table, $column)) {
                throw new RuntimeException("Cannot add existing column {$plan->table}.{$column}.");
            }
        }

        foreach ($dropColumns as $column) {
            if (! Schema::hasColumn($plan->table, $column)) {
                throw new RuntimeException("Cannot drop missing column {$plan->table}.{$column}.");
            }
        }

        DB::connection()->pretend(function () use ($plan, $newColumns, $changedColumns, $dropColumns): void {
            Schema::table($plan->table, function (Blueprint $table) use ($plan, $newColumns, $changedColumns, $dropColumns): void {
                foreach ($changedColumns as $column) {
                    $plan->columns[$column]->addTo($table, $column)->change();
                }

                foreach ($newColumns as $column) {
                    $plan->columns[$column]->addTo($table, $column);
                }

                foreach ($dropColumns as $column) {
                    $table->dropColumn($column);
                }
            });
        });
    }

    protected function resolveMigrationFile(): string
    {
        $migrationFile = $this->argument('migration');

        if (! is_string($migrationFile) || $migrationFile === '') {
            $migrationFiles = glob(database_path('migrations/*.php')) ?: [];

            if ($migrationFiles === []) {
                throw new RuntimeException('No migration files are available.');
            }

            $migrationFile = select(
                label: 'Which migration file would you like to use?',
                options: array_combine($migrationFiles, array_map('basename', $migrationFiles)),
            );
        }

        $resolved = realpath($migrationFile);

        if ($resolved === false || ! is_file($resolved)) {
            throw new RuntimeException("Migration file [{$migrationFile}] does not exist.");
        }

        return $resolved;
    }

    protected function runExactMigration(string $migrationFile, string $table): void
    {
        $this->info("Table '{$table}' does not exist. Running the selected migration...");

        $exitCode = Artisan::call('migrate', [
            '--path' => [$migrationFile],
            '--realpath' => true,
            '--no-interaction' => true,
        ]);

        if ($exitCode !== self::SUCCESS || ! Schema::hasTable($table)) {
            $output = trim(Artisan::output());

            throw new RuntimeException($output !== '' ? $output : "Migration did not create expected table [{$table}].");
        }
    }

    protected function synchronize(string $migrationFile, SchemaUpdatePlan $plan): void
    {
        if (! Schema::hasTable($plan->table)) {
            $this->runExactMigration($migrationFile, $plan->table);

            return;
        }

        $existingColumns = Schema::getColumnListing($plan->table);
        $desiredColumns = array_keys($plan->columns);
        $newColumns = array_values(array_diff($desiredColumns, $existingColumns));
        $changedColumns = array_values(array_intersect($desiredColumns, $existingColumns));
        $dropColumns = array_values(array_diff(
            $existingColumns,
            [...$desiredColumns, ...$plan->preservedColumns],
        ));

        $this->preflight($plan, $newColumns, $changedColumns, $dropColumns);

        if ($newColumns === [] && $changedColumns === [] && $dropColumns === []) {
            return;
        }

        // MySQL DDL is not transactionally reversible. All conversions and the
        // complete Blueprint compile before this first statement. Changes run
        // before additions and destructive drops, so a conversion failure can
        // never leave an unrelated add/drop behind.
        Schema::table($plan->table, function (Blueprint $table) use ($plan, $newColumns, $changedColumns, $dropColumns): void {
            foreach ($changedColumns as $column) {
                $plan->columns[$column]->addTo($table, $column)->change();
            }

            foreach ($newColumns as $column) {
                $plan->columns[$column]->addTo($table, $column);
            }

            foreach ($dropColumns as $column) {
                $table->dropColumn($column);
            }
        });
    }
}
