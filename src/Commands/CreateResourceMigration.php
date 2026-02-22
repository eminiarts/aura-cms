<?php

namespace Aura\Base\Commands;

use Aura\Base\Resource;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Symfony\Component\Process\ExecutableFinder;

class CreateResourceMigration extends Command
{
    protected $description = 'Create a migration based on the fields of a resource';

    protected $files;

    /**
     * Relationship fields that use pivot tables instead of columns
     */
    protected $relationshipFieldTypes = [
        'Aura\\Base\\Fields\\HasMany',
        'Aura\\Base\\Fields\\HasOne',
        'Aura\\Base\\Fields\\BelongsToMany',
        'Aura\\Base\\Fields\\Tags',
    ];

    protected $signature = 'aura:create-resource-migration {resource} {--soft-deletes} {--no-timestamps}';

    /**
     * Fields that should not generate columns (structural/display only)
     */
    protected $skipFieldTypes = [
        'Aura\\Base\\Fields\\Tab',
        'Aura\\Base\\Fields\\Tabs',
        'Aura\\Base\\Fields\\Panel',
        'Aura\\Base\\Fields\\Group',
        'Aura\\Base\\Fields\\Heading',
        'Aura\\Base\\Fields\\HorizontalLine',
        'Aura\\Base\\Fields\\View',
        'Aura\\Base\\Fields\\ViewValue',
        'Aura\\Base\\Fields\\LivewireComponent',
    ];

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $resourceClass = $this->argument('resource');

        if (! class_exists($resourceClass)) {
            $this->error("Resource class '{$resourceClass}' not found.");

            return 1;
        }

        /** @var resource $resource */
        $resource = app($resourceClass);

        if (! method_exists($resource, 'getFields')) {
            $this->error("Method 'getFields' not found in the '{$resourceClass}' class.");

            return 1;
        }

        $tableName = Str::plural(Str::snake(class_basename($resourceClass)));

        $migrationName = "create_{$tableName}_table";

        // Get input fields from resource
        $fields = method_exists($resource, 'inputFields') ? $resource->inputFields() : [];

        // Filter out structural and relationship fields
        $columnFields = collect($fields)->filter(function ($field) {
            $type = $field['type'] ?? null;

            // Skip structural fields
            if (in_array($type, $this->skipFieldTypes)) {
                return false;
            }

            // Skip relationship fields that use pivot tables
            if (in_array($type, $this->relationshipFieldTypes)) {
                return false;
            }

            return true;
        });

        $schema = $this->generateSchema($columnFields, $tableName);

        if ($this->migrationExists($migrationName)) {
            $migrationFile = $this->getMigrationPath($migrationName);
            $this->warn("Migration '{$migrationName}' already exists. Updating...");
        } else {
            Artisan::call('make:migration', [
                'name' => $migrationName,
                '--create' => $tableName,
            ]);

            $migrationFile = $this->getMigrationPath($migrationName);
        }

        if ($migrationFile === null) {
            $this->error("Unable to find migration file '{$migrationName}'.");

            return 1;
        }

        $content = $this->files->get($migrationFile);

        // Up method
        $pattern = '/(public function up\(\): void[\s\S]*?Schema::create\(.*?\{)([\s\S]*?)(\}\);[\s\S]*?\})/';
        $replacement = '${1}'.$schema.'${3}';
        $replacedContent = preg_replace($pattern, $replacement, $content);

        // Down method
        $down = "Schema::dropIfExists('{$tableName}');";
        $pattern = '/(public function down\(\): void[\s\S]*?{)[\s\S]*?Schema::table\(.*?function \(Blueprint \$table\) \{[\s\S]*?\/\/[\s\S]*?\}\);[\s\S]*?\}/';
        $replacement = '${1}'.PHP_EOL.'        '.$down.PHP_EOL.'    }';
        $replacedContent2 = preg_replace($pattern, $replacement, $replacedContent);

        $this->files->put($migrationFile, $replacedContent2);

        $this->info("Migration '{$migrationName}' created successfully.");
        $this->newLine();
        $this->table(['Field', 'Column Type'], $this->getFieldSummary($columnFields));

        // Run "pint" on the migration file
        $this->runPint($migrationFile);

        return 0;
    }

    protected function generateColumn($field)
    {
        try {
            $fieldInstance = app($field['type']);
        } catch (\Exception $e) {
            $this->warn("Could not instantiate field type '{$field['type']}' for '{$field['slug']}'. Skipping.");

            return '';
        }

        $columnType = $fieldInstance->tableColumnType ?? 'string';
        $slug = $field['slug'];

        // Handle special column types
        if ($columnType === 'bigIncrements') {
            return "\$table->id();\n";
        }

        $column = "\$table->{$columnType}('{$slug}')";

        // Add nullable unless explicitly set to false
        if ($fieldInstance->tableNullable ?? true) {
            $column .= '->nullable()';
        }

        // Add index for BelongsTo (foreign key) fields
        if ($field['type'] === 'Aura\\Base\\Fields\\BelongsTo') {
            $column .= '->index()';
        }

        return $column.";\n            ";
    }

    protected function generateSchema($fields, $tableName)
    {
        $schema = "\n            ";
        $schema .= "\$table->id();\n            ";

        $indexes = [];

        foreach ($fields as $field) {
            // Skip if it's an ID field (already added)
            if (($field['slug'] ?? '') === 'id') {
                continue;
            }

            $column = $this->generateColumn($field);
            if ($column) {
                $schema .= $column;
            }
        }

        // Add user_id
        $schema .= "\$table->foreignId('user_id')->nullable()->index();\n            ";

        // Add team_id if teams are enabled
        if (config('aura.teams', true)) {
            $schema .= "\$table->foreignId('team_id')->nullable()->index();\n            ";
        }

        // Add timestamps unless disabled
        if (! $this->option('no-timestamps')) {
            $schema .= "\$table->timestamps();\n            ";
        }

        // Add soft deletes if requested
        if ($this->option('soft-deletes')) {
            $schema .= "\$table->softDeletes();\n            ";
        }

        // Add composite index for team + type queries if teams enabled
        if (config('aura.teams', true)) {
            $schema .= "\n            // Indexes for common queries\n            ";
            $schema .= "\$table->index(['team_id', 'created_at']);\n        ";
        }

        return $schema;
    }

    protected function getFieldSummary($fields)
    {
        $summary = [];

        foreach ($fields as $field) {
            if (($field['slug'] ?? '') === 'id') {
                continue;
            }

            try {
                $fieldInstance = app($field['type']);
                $columnType = $fieldInstance->tableColumnType ?? 'string';
                $summary[] = [$field['slug'], $columnType];
            } catch (\Exception $e) {
                $summary[] = [$field['slug'], 'skipped'];
            }
        }

        $summary[] = ['user_id', 'foreignId'];
        if (config('aura.teams', true)) {
            $summary[] = ['team_id', 'foreignId'];
        }
        if (! $this->option('no-timestamps')) {
            $summary[] = ['created_at', 'timestamp'];
            $summary[] = ['updated_at', 'timestamp'];
        }
        if ($this->option('soft-deletes')) {
            $summary[] = ['deleted_at', 'timestamp'];
        }

        return $summary;
    }

    protected function getMigrationPath($name)
    {
        $migrationFiles = $this->files->glob(database_path('migrations/*.php'));
        $name = Str::snake($name);

        foreach ($migrationFiles as $file) {
            if (strpos($file, $name) !== false) {
                return $file;
            }
        }

    }

    protected function migrationExists($name)
    {
        $migrationFiles = $this->files->glob(database_path('migrations/*.php'));
        $name = Str::snake($name);

        foreach ($migrationFiles as $file) {
            if (strpos($file, $name) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function runPint($migrationFile)
    {
        // Disabled for now
        return;

        $command = [
            (new ExecutableFinder)->find('php', 'php', [
                '/usr/local/bin',
                '/opt/homebrew/bin',
            ]),
            'vendor/bin/pint', $migrationFile,
        ];

        $result = Process::path(base_path())->run($command);
    }
}
