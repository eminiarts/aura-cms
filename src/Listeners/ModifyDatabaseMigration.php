<?php

namespace Aura\Base\Listeners;

use Aura\Base\Events\SaveFields;
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
        $existingFields = collect($event->oldFields);

        if (! $model::$customTable) {
            return;
        }

        $tableName = $model->getTable();

        $migrationName = "create_{$tableName}_table";

        $schema = $this->generateSchema($newFields);

        $migrationExisted = $this->migrationExists($migrationName);

        if ($migrationExisted) {
            // $this->error("Migration '{$migrationName}' already exists.");
            // return 1;
            $migrationFile = $this->getMigrationPath($migrationName);
        } else {
            Artisan::call('make:migration', [
                'name' => $migrationName,
                '--create' => $tableName,
            ]);

            $migrationFile = $this->getMigrationPath($migrationName);
        }

        if ($migrationFile === null) {
            throw new \Exception("Unable to find migration file '{$migrationName}'.");
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

        try {
            $this->files->put($migrationFile, $replacedContent2);
            $this->runPint($migrationFile);

            // The regenerated migration describes the desired schema, including
            // removed fields. A rejected sync must not look like a successful save.
            $exitCode = Artisan::call('aura:schema-update', [
                'migration' => $migrationFile,
                '--drop' => true,
                '--force' => true,
            ]);

            if ($exitCode !== 0) {
                throw new RuntimeException('Schema update failed for ['.$tableName.']. '.trim(Artisan::output()));
            }
        } catch (Throwable $exception) {
            if ($migrationExisted) {
                $this->files->put($migrationFile, $content);
            } else {
                $this->files->delete($migrationFile);
            }

            throw $exception;
        }
    }

    protected function generateColumn($field)
    {
        $fieldInstance = app($field['type']);
        $columnType = $fieldInstance->tableColumnType;

        return "\$table->{$columnType}('{$field['slug']}')->nullable();\n";
    }

    protected function generateSchema($fields)
    {
        $schema = '';

        $schema .= '$table->id();'."\n";

        foreach ($fields as $field) {
            $schema .= $this->generateColumn($field);
        }

        $schema .= '$table->foreignId("user_id");'."\n";

        if (config('aura.teams')) {
            $schema .= '$table->foreignId("team_id");'."\n";
        }

        $schema .= '$table->timestamps();'."\n";
        $schema .= '$table->softDeletes();'."\n";

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
