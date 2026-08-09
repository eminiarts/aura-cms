<?php

namespace Aura\Base\Listeners;

use Aura\Base\Events\SaveFields;
use Aura\Base\Schema\FieldColumn;
use Aura\Base\Schema\SchemaMigrationLock;
use Aura\Base\Schema\SchemaUpdatePlan;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Throwable;

class ModifyDatabaseMigration
{
    protected $files;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Handle the event.
     */
    public function handle(SaveFields $event)
    {
        $model = $event->model;
        $newFields = collect($event->fields);

        if (! $model::$customTable) {
            return;
        }

        $tableName = $model->getTable();

        SchemaMigrationLock::run(
            'migration-editor:'.$tableName,
            fn () => $this->synchronize($newFields, $tableName),
        );
    }

    protected function createMigration(string $migrationName, string $tableName): string
    {
        /** @var MigrationCreator $creator */
        $creator = app('migration.creator');

        return $creator->create(
            Str::snake($migrationName),
            database_path('migrations'),
            $tableName,
            true,
        );
    }

    protected function generateColumn($field)
    {
        $definition = $this->getColumnDefinition($field);

        return $definition->toMigration($field['slug']).";\n";
    }

    protected function generateSchema($fields)
    {
        $schema = '';

        $schema .= '$table->id();'."\n";

        foreach ($fields as $field) {
            $schema .= $this->generateColumn($field);
        }

        $schema .= '$table->foreignId("user_id");'."\n";
        $schema .= '$table->foreignId("team_id");'."\n";
        $schema .= '$table->timestamps();'."\n";
        $schema .= '$table->softDeletes();'."\n";

        return $schema;
    }

    protected function getColumnDefinition(array $field): FieldColumn
    {
        $fieldInstance = app($field['type']);

        return method_exists($fieldInstance, 'columnDefinition')
            ? $fieldInstance->columnDefinition($field)
            : new FieldColumn(
                type: $fieldInstance->tableColumnType,
                nullable: $fieldInstance->tableNullable ?? true,
            );
    }

    protected function getMigrationPath($name)
    {
        $migrationFiles = $this->matchingMigrationPaths($name);

        if (count($migrationFiles) > 1) {
            throw new RuntimeException("Multiple migration files match [{$name}]; refusing to select one implicitly.");
        }

        return $migrationFiles[0] ?? null;
    }

    /** @return array<int, string> */
    protected function matchingMigrationPaths(string $name): array
    {
        $migrationFiles = $this->files->glob(database_path('migrations/*.php'));
        $name = Str::snake($name);

        return array_values(array_filter(
            $migrationFiles,
            static fn (string $file): bool => preg_match('/_'.preg_quote($name, '/').'\.php$/', basename($file)) === 1,
        ));
    }

    protected function migrationExists($name)
    {
        return $this->matchingMigrationPaths($name) !== [];
    }

    protected function runPint($migrationFile): void
    {
        $command = [
            (new ExecutableFinder)->find('php', 'php', [
                '/usr/local/bin',
                '/opt/homebrew/bin',
            ]),

            'vendor/bin/pint', $migrationFile,
        ];

        Process::path(base_path())->run($command)->throw();
    }

    protected function runSchemaUpdate(string $migrationFile): void
    {
        $exitCode = Artisan::call('aura:schema-update', [
            'migration' => $migrationFile,
            '--no-interaction' => true,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException(trim(Artisan::output()) ?: 'Schema synchronization failed.');
        }
    }

    protected function schemaPlan($fields, string $tableName): SchemaUpdatePlan
    {
        $columns = [];

        foreach ($fields as $field) {
            if (! is_array($field) || ! is_string($field['slug'] ?? null) || $field['slug'] === '') {
                throw new RuntimeException("Unable to build schema plan for [{$tableName}]: invalid field definition.");
            }

            $columns[$field['slug']] = $this->getColumnDefinition($field);
        }

        return new SchemaUpdatePlan($tableName, $columns);
    }

    protected function synchronize($newFields, string $tableName): void
    {

        $migrationName = "create_{$tableName}_table";

        $schema = $this->generateSchema($newFields);

        $migrationFile = null;
        $migrationCreated = false;
        $originalContent = null;

        try {
            if ($this->migrationExists($migrationName)) {
                $migrationFile = $this->getMigrationPath($migrationName);
            } else {
                $migrationFile = $this->createMigration($migrationName, $tableName);
                $migrationCreated = true;
            }

            if ($migrationFile === null) {
                throw new RuntimeException("Unable to find migration file '{$migrationName}'.");
            }

            $content = $this->files->get($migrationFile);
            $originalContent = $content;

            // Up method
            $pattern = '/(public function up\(\): void[\s\S]*?Schema::create\(.*?\{)([\s\S]*?)(\}\);[\s\S]*?\})/';
            $replacement = '${1}'.$schema.'${3}';
            $replacedContent = preg_replace($pattern, $replacement, $content, -1, $upReplacementCount);

            if ($replacedContent === null || $upReplacementCount !== 1) {
                throw new RuntimeException("Unable to update the up method in migration [{$migrationFile}].");
            }

            // Down method
            $down = "Schema::dropIfExists('{$tableName}');";
            $pattern = '/(public function down\(\): void[\s\S]*?{)[\s\S]*?Schema::table\(.*?function \(Blueprint \$table\) \{[\s\S]*?\/\/[\s\S]*?\}\);[\s\S]*?\}/';
            $replacement = '${1}'.PHP_EOL.'    '.$down.PHP_EOL.'}';
            $updatedContent = preg_replace($pattern, $replacement, $replacedContent);

            if ($updatedContent === null) {
                throw new RuntimeException("Unable to update migration [{$migrationFile}].");
            }

            $updatedContent = $this->schemaPlan($newFields, $tableName)->embedIn($updatedContent);

            if ($this->files->put($migrationFile, $updatedContent) === false) {
                throw new RuntimeException("Unable to update migration [{$migrationFile}].");
            }

            $this->runPint($migrationFile);
            $this->runSchemaUpdate($migrationFile);
        } catch (Throwable $exception) {
            if ($migrationCreated && $migrationFile !== null) {
                $this->files->delete($migrationFile);
            } elseif ($migrationFile !== null && is_string($originalContent) && $this->files->put($migrationFile, $originalContent) === false) {
                throw new RuntimeException("Unable to restore migration [{$migrationFile}] after schema synchronization failed.", previous: $exception);
            }

            throw $exception;
        }
    }
}
