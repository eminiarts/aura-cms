<?php

namespace Aura\Base\Commands;

use Aura\Base\Schema\AtomicSchemaUpdate;
use Aura\Base\Schema\ColumnValuePreflight;
use Aura\Base\Schema\SchemaMigrationLock;
use Aura\Base\Schema\SchemaUpdatePlan;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Schema\Blueprint;
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

            SchemaMigrationLock::runForTable(
                $plan->table,
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
    protected function applyBlueprint(
        SchemaUpdatePlan $plan,
        array $newColumns,
        array $changedColumns,
        array $dropColumns,
    ): void {
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

    protected function createTableFromPlan(string $migrationFile, SchemaUpdatePlan $plan): void
    {
        $this->info("Table '{$plan->table}' does not exist. Applying the validated schema plan...");
        $plan->assertMigrationCreatesOnlyPlannedTable($migrationFile);

        $created = false;

        try {
            Schema::create($plan->table, function (Blueprint $table) use ($plan): void {
                $table->id();

                foreach ($plan->columns as $slug => $column) {
                    $column->addTo($table, $slug);
                }

                $table->foreignId('user_id');
                if (config('aura.teams')) {
                    $table->foreignId('team_id');
                }
                $table->timestamps();
                $table->softDeletes();
            });
            $created = true;

            $this->recordMigration($migrationFile);
        } catch (Throwable $exception) {
            if ($created && Schema::hasTable($plan->table)) {
                Schema::drop($plan->table);
            }

            throw $exception;
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

        DB::connection()->pretend(
            fn () => $this->applyBlueprint($plan, $newColumns, $changedColumns, $dropColumns),
        );
    }

    protected function recordMigration(string $migrationFile): void
    {
        /** @var MigrationRepositoryInterface $repository */
        $repository = app('migration.repository');

        if (! $repository->repositoryExists()) {
            $repository->createRepository();
        }

        $migration = pathinfo($migrationFile, PATHINFO_FILENAME);

        if (! in_array($migration, $repository->getRan(), true)) {
            $repository->log($migration, $repository->getNextBatchNumber());
        }
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

    protected function synchronize(string $migrationFile, SchemaUpdatePlan $plan): void
    {
        if (! Schema::hasTable($plan->table)) {
            $this->createTableFromPlan($migrationFile, $plan);

            return;
        }

        $existingColumns = Schema::getColumnListing($plan->table);
        $desiredColumns = array_keys($plan->columns);
        $newColumns = array_values(array_diff($desiredColumns, $existingColumns));
        $columnMetadata = collect(Schema::getColumns($plan->table))->keyBy('name');
        $changedColumns = array_values(array_filter(
            array_intersect($desiredColumns, $existingColumns),
            fn (string $column): bool => ! $plan->columns[$column]->matchesDatabaseColumn(
                (array) $columnMetadata->get($column, []),
                Schema::getConnection()->getDriverName(),
            ),
        ));
        $dropColumns = array_values(array_diff(
            $existingColumns,
            [...$desiredColumns, ...$plan->preservedColumns],
        ));

        $this->preflight($plan, $newColumns, $changedColumns, $dropColumns);

        if ($newColumns === [] && $changedColumns === [] && $dropColumns === []) {
            return;
        }

        AtomicSchemaUpdate::table(
            $plan->table,
            function (Blueprint $table) use ($plan, $newColumns, $changedColumns, $dropColumns): void {
                foreach ($changedColumns as $column) {
                    $plan->columns[$column]->addTo($table, $column)->change();
                }

                foreach ($newColumns as $column) {
                    $plan->columns[$column]->addTo($table, $column);
                }

                foreach ($dropColumns as $column) {
                    $table->dropColumn($column);
                }
            },
        );
    }
}
