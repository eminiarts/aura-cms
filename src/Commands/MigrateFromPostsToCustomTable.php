<?php

namespace Aura\Base\Commands;

use Aura\Base\Facades\Aura;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use ReflectionClass;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;

class MigrateFromPostsToCustomTable extends Command
{
    protected $description = 'Migrate resources from posts and meta tables to custom tables';

    protected $signature = 'aura:migrate-from-posts-to-custom-table {resource?}';

    public function handle()
    {
        // Step 1: Ask which resource to use
        $resources = Aura::getResources();
        $resourceOptions = [];

        foreach ($resources as $resourceClass) {
            $resourceInstance = new $resourceClass;
            $resourceName = $resourceInstance->name ?? class_basename($resourceClass);
            $resourceOptions[$resourceName] = $resourceClass;
        }

        $resourceName = select(
            'Which resource do you want to migrate?',
            array_keys($resourceOptions)
        );
        $resourceClass = $resourceOptions[$resourceName];

        // Step 2: Generate migration and modify resource
        info('Generating migration for resource: '.$resourceName);
        $this->generateMigration($resourceClass);

        // Step 3: Ask if should run migration
        if (confirm('Do you want to run the migration now?', true)) {
            $this->call('migrate');
        }

        // Step 4: Ask if should transfer data
        if (confirm('Do you want to transfer data from posts and meta tables?', true)) {
            $this->call('aura:transfer-from-posts-to-custom-table', [
                'resource' => $resourceClass,
            ]);
        }

        info('Migration process completed.');
    }

    protected function generateMigration($resourceClass)
    {
        // Reflect on the resource class
        $reflection = new ReflectionClass($resourceClass);
        $filePath = $reflection->getFileName();

        if (! file_exists($filePath)) {
            error('Resource class file not found: '.$filePath);

            return;
        }

        $file = file_get_contents($filePath);

        // Add or update $customTable
        if (strpos($file, 'public static $customTable') === false) {
            $file = preg_replace(
                '/(class\s+'.$reflection->getShortName().'\s+extends\s+\S+\s*{)/i',
                "$1\n    public static \$customTable = true;",
                $file
            );
        } else {
            $file = preg_replace(
                '/public\s+static\s+\$customTable\s*=\s*(?:true|false);/i',
                'public static $customTable = true;',
                $file
            );
        }

        // Add or update $table
        $resourceInstance = new $resourceClass;
        $modelClass = $resourceInstance->model ?? $resourceInstance->getModel();
        $tableName = Str::snake(Str::pluralStudly(class_basename($modelClass)));

        if (strpos($file, 'protected $table') === false) {
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

        // dd($resourceClass); // double backslashes to $resourceClass

        // $resourceClass = str_replace('\\', '\\\\', $resourceClass);

        // Call the artisan command to create the migration
        $this->call('aura:create-resource-migration', [
            'resource' => $resourceClass,
        ]);

        info('Migration generated for resource: '.$resourceClass);
    }
}
