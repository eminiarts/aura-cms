<?php

namespace Aura\Base\Commands;

use Aura\Base\Resource;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Process\ExecutableFinder;

class CreateResourceMigration extends Command
{
    protected $description = 'Create a migration based on the fields of a resource';

    protected $files;

    protected $signature = 'aura:create-resource-migration {resource}';

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

        $tableName = Str::plural(Str::lower(class_basename($resourceClass)));

        $migrationName = "create_{$tableName}_table";

        $baseFields = collect([
            [
                'name' => 'ID',
                'type' => 'Aura\\Base\\Fields\\ID',
                'slug' => 'id',
            ],
        ]);

        $fields = method_exists($resource, 'inputFields') ? $resource->inputFields() : [];

        $combined = $baseFields->merge($fields)->merge(collect([
            [
                'name' => 'User Id',
                'type' => 'Aura\\Base\\Fields\\BelongsTo',
                'slug' => 'user_id',
            ],
            [
                'name' => 'Team Id',
                'type' => 'Aura\\Base\\Fields\\BelongsTo',
                'slug' => 'team_id',
            ],
            [
                'name' => 'created_at',
                'type' => 'Aura\\Base\\Fields\\DateTime',
                'slug' => 'created_at',
            ],
            [
                'name' => 'updated_at',
                'type' => 'Aura\\Base\\Fields\\DateTime',
                'slug' => 'updated_at',
            ],
        ]));

        $combined = $combined->unique('slug');

        $schema = $this->generateSchema($combined);

        if ($this->migrationExists($migrationName)) {
            $migrationFile = $this->getMigrationPath($migrationName);
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
        $replacement = '${1}'.PHP_EOL.'    '.$down.PHP_EOL.'}';
        $replacedContent2 = preg_replace($pattern, $replacement, $replacedContent);

        $this->files->put($migrationFile, $replacedContent2);

        $this->info("Migration '{$migrationName}' created successfully.");

        // Run "pint" on the migration file
        $this->runPint($migrationFile);
    }

    protected function generateColumn($field)
    {
        $fieldInstance = app($field['type']);
        $columnType = $fieldInstance->tableColumnType;

        $column = "\$table->{$columnType}('{$field['slug']}')";

        if ($fieldInstance->tableNullable) {
            $column .= '->nullable()';
        }

        return $column.";\n";
    }

    protected function generateSchema($fields)
    {
        $schema = '';

        // Maybe custom Schema instead of Fields?
        // $schema .= "$table->id();\n";

        foreach ($fields as $field) {
            $schema .= $this->generateColumn($field);
        }

        return $schema;
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
