<?php

namespace Aura\Base\Listeners;

use Aura\Base\Events\SaveFields;
use Aura\Base\Schema\FieldColumn;
use Aura\Base\Schema\SchemaMigrationLock;
use Aura\Base\Support\PackageTool;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Throwable;

class CreateDatabaseMigration
{
    protected $files;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    public function handle(SaveFields $event)
    {
        $model = $event->model;
        $tableName = $model->getTable();

        if (! $model::$customTable) {
            return;
        }

        SchemaMigrationLock::runForTable(
            $tableName,
            fn () => $this->createAndRunMigration($event, $tableName),
        );
    }

    protected function createAndRunMigration(SaveFields $event, string $tableName): void
    {
        $newFields = collect($event->fields);
        $existingFields = collect($event->oldFields);

        // Detect fields to add
        $fieldsToAdd = $newFields->filter(function ($field) {
            return ! isset($field['_id']);
        });

        // Detect fields to update
        $fieldsToUpdate = $newFields->filter(function ($field) use ($existingFields) {
            if (! isset($field['_id'])) {
                return false;
            }
            $existingField = $existingFields->firstWhere('_id', $field['_id']);

            return $existingField && $existingField != $field;
        })->map(function ($field) use ($existingFields) {
            $oldField = $existingFields->firstWhere('_id', $field['_id']);

            return ['old' => $oldField, 'new' => $field];
        })->values();

        // Detect fields to delete
        $fieldsToDelete = $existingFields->filter(function ($field) use ($newFields) {
            return ! $newFields->contains('_id', $field['_id']);
        });

        if ($fieldsToAdd->isEmpty() && $fieldsToUpdate->isEmpty() && $fieldsToDelete->isEmpty()) {
            return;
        }

        // Generate migration name
        $migrationName = 'update_'.$tableName.'_table_'.now()->format('Y_m_d_His_u').'_'.Str::lower(Str::random(8));

        $migrationFile = null;

        try {
            $migrationFile = $this->createMigration($migrationName, $tableName);

            if ($migrationFile === null) {
                throw new RuntimeException("Unable to find migration file '{$migrationName}'.");
            }

            // Generate schema for additions, updates, and deletions
            $schemaAdditions = $this->generateSchema($fieldsToAdd, 'add');
            $schemaUpdates = $this->generateSchema($fieldsToUpdate, 'update');
            $schemaDeletions = $this->generateSchema($fieldsToDelete, 'delete');

            // Generate down schema for additions, updates, and deletions
            $schemaAdditionsDown = $this->generateDownSchema($fieldsToAdd, 'add');
            $schemaUpdatesDown = $this->generateDownSchema($fieldsToUpdate, 'update');
            $schemaDeletionsDown = $this->generateDownSchema($fieldsToDelete, 'delete');
            $upPreflight = $this->generateUpPreflight($fieldsToUpdate, 'update', $tableName);
            $downPreflight = $this->generateDownPreflight($fieldsToUpdate, 'update', $tableName);

            $content = $this->files->get($migrationFile);
            $updatedContent = $this->updateMigrationContent(
                $content,
                $schemaAdditions,
                $schemaUpdates,
                $schemaDeletions,
                $schemaAdditionsDown,
                $schemaUpdatesDown,
                $schemaDeletionsDown,
                $downPreflight,
                $upPreflight,
            );

            if ($this->files->put($migrationFile, $updatedContent) === false) {
                throw new RuntimeException("Unable to update generated migration [{$migrationFile}].");
            }

            // Run Pint to format the migration file
            $this->runPint($migrationFile);

            // Run the migration
            $this->runMigration($migrationFile);
        } catch (Throwable $exception) {
            if ($migrationFile !== null && $this->files->exists($migrationFile) && ! $this->files->delete($migrationFile)) {
                throw new RuntimeException("Unable to remove failed migration [{$migrationFile}].", previous: $exception);
            }

            throw $exception;
        }

    }

    protected function createMigration(string $migrationName, string $tableName): string
    {
        /** @var MigrationCreator $creator */
        $creator = app('migration.creator');

        return $creator->create(
            Str::snake($migrationName),
            database_path('migrations'),
            $tableName,
            false,
        );
    }

    protected function generateColumn($field)
    {
        $definition = $this->getColumnDefinition($field);

        return $definition->toMigration($field['slug']).";\n";
    }

    protected function generateConversionPreflight($fields, string $action, string $tableName, bool $rollingBack): string
    {
        if ($action !== 'update') {
            return '';
        }

        $preflight = '';

        foreach ($fields as $field) {
            $source = $rollingBack ? $field['new'] : $field['old'];
            $target = $rollingBack ? $field['old'] : $field['new'];
            $sourceDefinition = $this->getColumnDefinition($source);
            $targetDefinition = $this->getColumnDefinition($target);

            if ($sourceDefinition == $targetDefinition) {
                continue;
            }

            $column = (string) $source['slug'];
            $tableLiteral = var_export($tableName, true);
            $columnLiteral = var_export($column, true);
            $definitionLiteral = var_export($targetDefinition->toArray(), true);

            $preflight .= "\\Aura\\Base\\Schema\\ColumnValuePreflight::assertTableColumnCanConvert(\n";
            $preflight .= "    {$tableLiteral},\n";
            $preflight .= "    {$columnLiteral},\n";
            $preflight .= "    \\Aura\\Base\\Schema\\FieldColumn::fromArray({$definitionLiteral}),\n";
            $preflight .= ");\n";
        }

        return $preflight;
    }

    protected function generateDownPreflight($fields, $action, string $tableName): string
    {
        return $this->generateConversionPreflight($fields, $action, $tableName, rollingBack: true);
    }

    protected function generateDownSchema($fields, $action)
    {
        $downSchema = '';

        foreach ($fields as $field) {

            switch ($action) {
                case 'add':
                    // For additions in the up method, we need to drop the columns in the down method
                    $downSchema .= "\$table->dropColumn('{$field['slug']}');\n";
                    break;
                case 'update':
                    $oldSlug = $field['old']['slug'];
                    $newSlug = $field['new']['slug'];
                    $oldDefinition = $this->getColumnDefinition($field['old']);
                    $newDefinition = $this->getColumnDefinition($field['new']);

                    if ($oldDefinition != $newDefinition) {
                        $downSchema .= $oldDefinition->toMigration($newSlug, change: true).";\n";
                    }

                    if ($oldSlug !== $newSlug) {
                        $downSchema .= "\$table->renameColumn('{$newSlug}', '{$oldSlug}');\n";
                    }
                    break;
                case 'delete':
                    // For deletions in the up method, we need to re-add the columns in the down method
                    $downSchema .= $this->generateColumn($field);
                    break;
            }
        }

        return $downSchema;
    }

    protected function generateSchema($fields, $action)
    {
        $schema = '';

        foreach ($fields as $field) {

            switch ($action) {
                case 'add':
                    $schema .= $this->generateColumn($field);
                    break;
                case 'update':
                    $oldSlug = $field['old']['slug'];
                    $newSlug = $field['new']['slug'];
                    $oldDefinition = $this->getColumnDefinition($field['old']);
                    $newDefinition = $this->getColumnDefinition($field['new']);

                    if ($oldSlug !== $newSlug) {
                        $schema .= "\$table->renameColumn('{$oldSlug}', '{$newSlug}');\n";
                    }

                    if ($oldDefinition != $newDefinition) {
                        $schema .= $newDefinition->toMigration($newSlug, change: true).";\n";
                    }
                    break;
                case 'delete':
                    // Dont Drop ID, Created At, Updated At
                    if (in_array($field['slug'], ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                        break;
                    }

                    $schema .= "\$table->dropColumn('{$field['slug']}');\n";
                    break;
            }
        }

        return $schema;
    }

    protected function generateUpPreflight($fields, $action, string $tableName): string
    {
        return $this->generateConversionPreflight($fields, $action, $tableName, rollingBack: false);
    }

    protected function getColumnDefinition(array $field): FieldColumn
    {
        $fieldInstance = app($field['type']);

        if (method_exists($fieldInstance, 'columnDefinition')) {
            return $fieldInstance->columnDefinition($field);
        }

        return new FieldColumn(
            type: $fieldInstance->tableColumnType,
            nullable: $fieldInstance->tableNullable ?? true,
        );
    }

    protected function runMigration(string $migrationFile): void
    {
        $exitCode = Artisan::call('migrate', [
            '--path' => [$migrationFile],
            '--realpath' => true,
            '--no-interaction' => true,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException(trim(Artisan::output()) ?: 'Generated migration failed.');
        }
    }

    protected function runPint($migrationFile): void
    {
        $pint = PackageTool::binary('pint');

        if ($pint === null) {
            return;
        }

        $command = [
            (new ExecutableFinder)->find('php', 'php', [
                '/usr/local/bin',
                '/opt/homebrew/bin',
            ]),

            $pint, $migrationFile,
        ];

        Process::path(dirname($migrationFile))->run($command)->throw();
    }

    protected function updateMigrationContent(
        $content,
        $additions,
        $updates,
        $deletions,
        $additionsDown,
        $updatesDown,
        $deletionsDown,
        string $downPreflight = '',
        string $upPreflight = '',
    ) {
        // Up method
        $pattern = '/(public function up\(\): void[\s\S]*?Schema::table\(.*?\{)([\s\S]*?)(\}\);[\s\S]*?\})/';
        $replacement = '${1}'.PHP_EOL.$additions.PHP_EOL.$updates.PHP_EOL.$deletions.PHP_EOL.'${3}';
        $updatedContent = preg_replace($pattern, $replacement, $content, -1, $upReplacementCount);

        if ($updatedContent === null || $upReplacementCount !== 1) {
            throw new RuntimeException('Unable to update the generated migration up method.');
        }

        if ($upPreflight !== '') {
            $updatedContent = preg_replace(
                '/(public function up\(\): void\s*\{)/',
                '${1}'.PHP_EOL.$upPreflight,
                $updatedContent,
                1,
                $preflightReplacementCount,
            );

            if ($updatedContent === null || $preflightReplacementCount !== 1) {
                throw new RuntimeException('Unable to add the generated migration preflight.');
            }
        }

        // Down method
        $downPattern = '/(public function down\(\): void[\s\S]*?Schema::table\(.*?\{)([\s\S]*?)(\}\);[\s\S]*?\})/';
        $downReplacement = '${1}'.PHP_EOL.$additionsDown.PHP_EOL.$updatesDown.PHP_EOL.$deletionsDown.PHP_EOL.'${3}';
        $updatedContent = preg_replace($downPattern, $downReplacement, $updatedContent, -1, $downReplacementCount);

        if ($updatedContent === null || $downReplacementCount !== 1) {
            throw new RuntimeException('Unable to update the generated migration down method.');
        }

        if ($downPreflight !== '') {
            $updatedContent = preg_replace(
                '/(public function down\(\): void\s*\{)/',
                '${1}'.PHP_EOL.$downPreflight,
                $updatedContent,
                1,
                $preflightReplacementCount,
            );

            if ($updatedContent === null || $preflightReplacementCount !== 1) {
                throw new RuntimeException('Unable to add the generated migration rollback preflight.');
            }
        }

        $updatedContent = str_replace(
            'Schema::table(',
            '\\Aura\\Base\\Schema\\AtomicSchemaUpdate::table(',
            $updatedContent,
        );

        return $updatedContent;
    }
}
