<?php

namespace Aura\Base\Commands;

use Aura\Base\Facades\Aura;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use ReflectionClass;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;

class MigrateFromPostsToCustomTable extends Command
{
    protected $description = 'Switch a resource to its own table: set $customTable, generate the migration and optionally run it and copy the data';

    protected $signature = 'aura:migrate-from-posts-to-custom-table {resource? : The fully qualified resource class, e.g. "App\\Aura\\Resources\\Article"}';

    public function handle()
    {
        $resourceClass = $this->argument('resource');

        if ($resourceClass && ! class_exists($resourceClass)) {
            error("Resource class '{$resourceClass}' not found.");

            return self::FAILURE;
        }

        if (! $resourceClass) {
            $resourceOptions = [];

            foreach (Aura::getResources() as $class) {
                $resourceOptions[app($class)->name ?? class_basename($class)] = $class;
            }

            $resourceClass = $resourceOptions[select(
                'Which resource do you want to migrate?',
                array_keys($resourceOptions)
            )];
        }

        info('Generating migration for resource: '.$resourceClass);

        if ($this->generateMigration($resourceClass) === self::FAILURE) {
            return self::FAILURE;
        }

        if (confirm('Do you want to run the migration now?', true)) {
            $this->call('migrate');
        }

        if (confirm('Do you want to transfer data from posts and meta tables?', true)) {
            $this->call('aura:transfer-from-posts-to-custom-table', [
                'resource' => $resourceClass,
            ]);
        }

        info('Migration process completed.');

        return self::SUCCESS;
    }

    protected function generateMigration($resourceClass)
    {
        $reflection = new ReflectionClass($resourceClass);
        $filePath = $reflection->getFileName();

        if (! $filePath || ! file_exists($filePath)) {
            error('Resource class file not found for: '.$resourceClass);

            return self::FAILURE;
        }

        $file = file_get_contents($filePath);

        // Add or update $customTable. A column-backed resource must also stop
        // writing its fields to the meta table (see make-custom-resource.stub).
        if (! str_contains($file, 'public static $customTable')) {
            $file = preg_replace(
                '/(class\s+'.$reflection->getShortName().'\s+extends\s+\S+\s*{)/i',
                "$1\n    public static \$customTable = true;\n\n    public static bool \$usesMeta = false;",
                $file
            );
        } else {
            $file = preg_replace(
                '/public\s+static\s+\$customTable\s*=\s*(?:true|false);/i',
                'public static $customTable = true;',
                $file
            );
        }

        $tableName = Str::snake(Str::pluralStudly(class_basename($resourceClass)));

        // Add or update $table
        if (! str_contains($file, 'protected $table')) {
            $file = preg_replace(
                '/(class\s+'.$reflection->getShortName().'\s+extends\s+\S+\s*{)/i',
                "$1\n    protected \$table = '$tableName';",
                $file
            );
        } else {
            $file = preg_replace(
                '/protected\s+\$table\s*=\s*[\'"].*?[\'"]\s*;/i',
                "protected \$table = '$tableName';",
                $file
            );
        }

        file_put_contents($filePath, $file);
        info('Modified resource class file: '.$filePath);

        // The class is already loaded, so its in-memory $table is still `posts`.
        // Pass the new table name explicitly.
        $this->call('aura:create-resource-migration', [
            'resource' => $resourceClass,
            '--table' => $tableName,
        ]);

        info('Migration generated for resource: '.$resourceClass);

        return self::SUCCESS;
    }
}
