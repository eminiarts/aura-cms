## ./Stubs/make-field-view.stub
```
<x-aura::fields.wrapper :field="$field">
    {!! $this->model->display($field['slug']) !!}
</x-aura::fields.wrapper>```

## ./Stubs/make-taxonomy.stub
```
<?php

namespace App\Aura\Taxonomies;

use Aura\Base\Taxonomies\Taxonomy;

class TaxonomyName extends Taxonomy
{
    public static $hierarchical = false;

    public static string $type = 'TaxonomyName';

    public static ?string $slug = 'TaxonomySlug';

    public function getIcon()
    {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 7L11.8845 4.76892C11.5634 4.1268 11.4029 3.80573 11.1634 3.57116C10.9516 3.36373 10.6963 3.20597 10.4161 3.10931C10.0992 3 9.74021 3 9.02229 3H5.2C4.0799 3 3.51984 3 3.09202 3.21799C2.71569 3.40973 2.40973 3.71569 2.21799 4.09202C2 4.51984 2 5.0799 2 6.2V7M2 7H17.2C18.8802 7 19.7202 7 20.362 7.32698C20.9265 7.6146 21.3854 8.07354 21.673 8.63803C22 9.27976 22 10.1198 22 11.8V16.2C22 17.8802 22 18.7202 21.673 19.362C21.3854 19.9265 20.9265 20.3854 20.362 20.673C19.7202 21 18.8802 21 17.2 21H6.8C5.11984 21 4.27976 21 3.63803 20.673C3.07354 20.3854 2.6146 19.9265 2.32698 19.362C2 18.7202 2 17.8802 2 16.2V7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }
}
```

## ./Stubs/make-field-edit.stub
```
@dump(':fieldSlug')

<x-aura::fields.wrapper :field="$field">
    <x-aura::input.text :disabled="optional($field)['disabled']" wire:model="form.fields.{{ optional($field)['slug'] }}" error="form.fields.{{ optional($field)['slug'] }}" placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}" id="resource-field-{{ optional($field)['slug'] }}"></x-aura::input.text>
</x-aura::fields.wrapper>
```

## ./Stubs/livewire.custom.stub
```
<?php

namespace {{ namespace }};

use {{ baseClass }};

class {{ class }} extends {{ componentType }}
{
    // Add your custom logic here

    public function mount($id, $slug = '{{ resourceClass }}')
    {
        parent::mount($slug, $id);
    }
}```

## ./Stubs/make-custom-resource.stub
```
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class PostName extends Resource
{
    public static string $type = 'PostName';

    public static ?string $slug = 'PostSlug';

    public static $customTable = true;

    protected $table = 'post_slug';

    public static function getWidgets(): array
    {
        return [];
    }

    public function getIcon()
    {
        return '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20.5 7.27783L12 12.0001M12 12.0001L3.49997 7.27783M12 12.0001L12 21.5001M21 16.0586V7.94153C21 7.59889 21 7.42757 20.9495 7.27477C20.9049 7.13959 20.8318 7.01551 20.7354 6.91082C20.6263 6.79248 20.4766 6.70928 20.177 6.54288L12.777 2.43177C12.4934 2.27421 12.3516 2.19543 12.2015 2.16454C12.0685 2.13721 11.9315 2.13721 11.7986 2.16454C11.6484 2.19543 11.5066 2.27421 11.223 2.43177L3.82297 6.54288C3.52345 6.70928 3.37369 6.79248 3.26463 6.91082C3.16816 7.01551 3.09515 7.13959 3.05048 7.27477C3 7.42757 3 7.59889 3 7.94153V16.0586C3 16.4013 3 16.5726 3.05048 16.7254C3.09515 16.8606 3.16816 16.9847 3.26463 17.0893C3.37369 17.2077 3.52345 17.2909 3.82297 17.4573L11.223 21.5684C11.5066 21.726 11.6484 21.8047 11.7986 21.8356C11.9315 21.863 12.0685 21.863 12.2015 21.8356C12.3516 21.8047 12.4934 21.726 12.777 21.5684L20.177 17.4573C20.4766 17.2909 20.6263 17.2077 20.7354 17.0893C20.8318 16.9847 20.9049 16.8606 20.9495 16.7254C21 16.5726 21 16.4013 21 16.0586Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    public static function getFields()
    {
        return [];
    }


}
```

## ./Stubs/make-field.stub
```
<?php

namespace App\Aura\Fields;

use Aura\Base\Fields\Field;

class FieldName extends Field
{
    public $edit = 'fields.FieldSlug';

    public $view = 'fields.FieldSlug-view';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            // Custom Fields for this field
            // See Documentation for more info
        ]);
    }
}
```

## ./Stubs/make-resource.stub
```
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class PostName extends Resource
{
    public static string $type = 'PostName';

    public static ?string $slug = 'PostSlug';

    public static function getWidgets(): array
    {
        return [];
    }

    public function getIcon()
    {
        return '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20.5 7.27783L12 12.0001M12 12.0001L3.49997 7.27783M12 12.0001L12 21.5001M21 16.0586V7.94153C21 7.59889 21 7.42757 20.9495 7.27477C20.9049 7.13959 20.8318 7.01551 20.7354 6.91082C20.6263 6.79248 20.4766 6.70928 20.177 6.54288L12.777 2.43177C12.4934 2.27421 12.3516 2.19543 12.2015 2.16454C12.0685 2.13721 11.9315 2.13721 11.7986 2.16454C11.6484 2.19543 11.5066 2.27421 11.223 2.43177L3.82297 6.54288C3.52345 6.70928 3.37369 6.79248 3.26463 6.91082C3.16816 7.01551 3.09515 7.13959 3.05048 7.27477C3 7.42757 3 7.59889 3 7.94153V16.0586C3 16.4013 3 16.5726 3.05048 16.7254C3.09515 16.8606 3.16816 16.9847 3.26463 17.0893C3.37369 17.2077 3.52345 17.2909 3.82297 17.4573L11.223 21.5684C11.5066 21.726 11.6484 21.8047 11.7986 21.8356C11.9315 21.863 12.0685 21.863 12.2015 21.8356C12.3516 21.8047 12.4934 21.726 12.777 21.5684L20.177 17.4573C20.4766 17.2909 20.6263 17.2077 20.7354 17.0893C20.8318 16.9847 20.9049 16.8606 20.9495 16.7254C21 16.5726 21 16.4013 21 16.0586Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    public static function getFields()
    {
        return [];
    }
}
```

## ./DatabaseToResources.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class DatabaseToResources extends Command
{
    protected $description = 'Create resources based on existing database tables';

    protected $signature = 'aura:database-to-resources';

    public function handle()
    {
        $tables = $this->getAllTables();

        // dd($tables);

        foreach ($tables as $table) {
            if (in_array($table, ['migrations', 'failed_jobs', 'password_resets', 'settions'])) {
                continue;
            }

            $this->call('aura:transform-table-to-resource', ['table' => $table]);
        }

        $this->info('Resources generated successfully');
    }

    private function getAllTables()
    {
        return Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();
    }
}
```

## ./MigrateFromPostsToCustomTable.php
```
<?php

namespace Aura\Base\Commands;

use ReflectionClass;
use Aura\Base\Facades\Aura;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use function Laravel\Prompts\info;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\error;
use function Laravel\Prompts\select;
use function Laravel\Prompts\confirm;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class MigrateFromPostsToCustomTable extends Command
{
    protected $signature = 'aura:migrate-from-posts-to-custom-table {resource?}';
    protected $description = 'Migrate resources from posts and meta tables to custom tables';

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
        info('Generating migration for resource: ' . $resourceName);
        $this->generateMigration($resourceClass);

        // Step 3: Ask if should run migration
        if (confirm('Do you want to run the migration now?', true)) {
            $this->call('migrate');
        }

        // Step 4: Ask if should transfer data
        if (confirm('Do you want to transfer data from posts and meta tables?', true)) {
            $this->call('aura:transfer-from-posts-to-custom-table', [
                'resource' => $resourceClass
            ]);
        }

        info('Migration process completed.');
    }

    protected function generateMigration($resourceClass)
    {
        // Reflect on the resource class
        $reflection = new ReflectionClass($resourceClass);
        $filePath = $reflection->getFileName();

        if (!file_exists($filePath)) {
            error('Resource class file not found: ' . $filePath);
            return;
        }

        $file = file_get_contents($filePath);

        // Add or update $customTable
        if (strpos($file, 'public static $customTable') === false) {
            $file = preg_replace(
                '/(class\s+' . $reflection->getShortName() . '\s+extends\s+\S+\s*{)/i',
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
                '/(class\s+' . $reflection->getShortName() . '\s+extends\s+\S+\s*{)/i',
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
        info('Modified resource class file: ' . $filePath);

        // dd($resourceClass); // double backslashes to $resourceClass

        // $resourceClass = str_replace('\\', '\\\\', $resourceClass);

        // Call the artisan command to create the migration
        $this->call('aura:create-resource-migration', [
            'resource' => $resourceClass,
        ]);

        info('Migration generated for resource: ' . $resourceClass);
    }
}
```

## ./UpdateSchemaFromMigration.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\select;

class UpdateSchemaFromMigration extends Command
{
    protected $description = 'Update the database schema based on the provided migration file';

    protected $signature = 'aura:schema-update {migration?}';

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

            return;
        }

        $table = $this->getTableNameFromMigration($migrationFile);

        if (! $table) {
            $this->error('Unable to determine table name from the migration.');

            return;
        }

        $existingColumns = DB::getSchemaBuilder()->getColumnListing($table);
        $desiredColumns = $this->getDesiredColumnsFromMigration($migrationFile);

        $newColumns = array_diff(array_keys($desiredColumns), $existingColumns);

        $dropColumns = array_diff($existingColumns, array_keys($desiredColumns));
        $dropColumns = array_diff($dropColumns, ['id', 'created_at', 'updated_at', 'deleted_at']);

        if (! Schema::hasTable($table)) {
            $this->info("Table '{$table}' does not exist. Running the migration...");

            // Run the migration
            Artisan::call('migrate');

            $this->info("Migration completed. Table '{$table}' has been created.");

            return;
        }

        // Add new columns
        Schema::table($table, function (Blueprint $table) use ($existingColumns, $desiredColumns) {
            $newColumns = array_diff(array_keys($desiredColumns), $existingColumns);

            foreach ($newColumns as $column) {

                $table->{$desiredColumns[$column]['type']}($column)->nullable();
            }

            // Drop outdated columns
            $dropColumns = array_diff($existingColumns, array_keys($desiredColumns));
            $dropColumns = array_diff($dropColumns, ['id', 'created_at', 'updated_at', 'deleted_at']);

            foreach ($dropColumns as $column) {
                $table->dropColumn($column);
            }
        });

        // Modify existing columns if needed
        Schema::table($table, function (Blueprint $table) use ($desiredColumns) {
            foreach ($desiredColumns as $column => $definition) {
                $table->{$definition['type']}($column)->nullable()->change();
            }
        });

        $this->info('Schema updated successfully based on the migration file.');
    }

    protected function getDesiredColumnsFromMigration($migrationFile)
    {
        $body = file($migrationFile);
        $upMethodStarted = false;
        $columns = [];

        foreach ($body as $line) {
            if (preg_match('/public function up\(\)/', $line)) {
                $upMethodStarted = true;

                continue;
            }

            if ($upMethodStarted) {
                if (preg_match('/\}/', $line)) {
                    break;
                }

                if (preg_match('/\$table->([a-zA-Z]+)\(\'([a-zA-Z0-9_]+)\'\)/', $line, $matches)) {
                    $columns[$matches[2]] = ['type' => $matches[1]];
                }
            }
        }

        return $columns;
    }

    protected function getTableNameFromMigration($migration)
    {
        $body = file($migration);

        foreach ($body as $line) {
            if (preg_match('/Schema::create\(\'([a-zA-Z0-9_]+)\'/', $line, $matches)) {
                return $matches[1];
            }
        }
    }
}
```

## ./CreateResourceMigration.php
```
<?php

namespace Aura\Base\Commands;

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

        $resource = app($resourceClass);

        if (! method_exists($resource, 'getFields')) {
            $this->error("Method 'getFields' not found in the '{$resourceClass}' class.");

            return 1;
        }

        $tableName = Str::lower($resource->getPluralName());

        $migrationName = "create_{$tableName}_table";

        $baseFields = collect([
            [
                'name' => 'ID',
                'type' => 'Aura\\Base\\Fields\\ID',
                'slug' => 'id',
            ],
            // [
            //     'name' => 'Title',
            //     'type' => 'Aura\\Base\\Fields\\Text',
            //     'slug' => 'title',
            // ],
            // [
            //     'name' => 'Slug',
            //     'type' => 'Aura\\Base\\Fields\\Text',
            //     'slug' => 'slug',
            // ],
            // [
            //     'name' => 'Content',
            //     'type' => 'Aura\\Base\\Fields\\Textarea',
            //     'slug' => 'content',
            // ],
            // [
            //     'name' => 'Status',
            //     'type' => 'Aura\\Base\\Fields\\Text',
            //     'slug' => 'status',
            // ],
            // [
            //     'name' => 'Parent ID',
            //     'type' => 'Aura\\Base\\Fields\\ID',
            //     'slug' => 'parent_id',
            // ],
            // [
            //     'name' => 'Order',
            //     'type' => 'Aura\\Base\\Fields\\Number',
            //     'slug' => 'order',
            // ],

        ]);

        $fields = $resource->inputFields();

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

        // dd($schema);

        if ($this->migrationExists($migrationName)) {
            //$this->error("Migration '{$migrationName}' already exists.");
            //return 1;
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
```

## ./CreateResourcePermissions.php
```
<?php

namespace Aura\Base\Commands;

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Permission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class CreateResourcePermissions extends Command
{
    protected $description = 'Create permissions for all resources';

    protected $signature = 'aura:create-resource-permissions';

    public function handle()
    {
        // Permissions
        foreach (Aura::getResources() as $resource) {
            $r = app($resource);

            $this->info('Creating missing permissions for '.$r->pluralName().'...');

            // login user 1
            Auth::loginUsingId(1);

            Permission::firstOrCreate(
                ['slug' => 'view-'.$r::$slug],
                [
                    'title' => 'View '.$r->pluralName(),
                    'name' => 'View '.$r->pluralName(),
                    'slug' => 'view-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'viewAny-'.$r::$slug],
                [
                    'title' => 'View Any '.$r->pluralName(),
                    'name' => 'View Any '.$r->pluralName(),
                    'slug' => 'viewAny-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'create-'.$r::$slug],
                [
                    'title' => 'Create '.$r->pluralName(),
                    'name' => 'Create '.$r->pluralName(),
                    'slug' => 'create-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'update-'.$r::$slug],
                [
                    'title' => 'Update '.$r->pluralName(),
                    'name' => 'Update '.$r->pluralName(),
                    'slug' => 'update-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'restore-'.$r::$slug],
                [
                    'title' => 'Restore '.$r->pluralName(),
                    'name' => 'Restore '.$r->pluralName(),
                    'slug' => 'restore-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'delete-'.$r::$slug],
                [
                    'title' => 'Delete '.$r->pluralName(),
                    'name' => 'Delete '.$r->pluralName(),
                    'slug' => 'delete-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'forceDelete-'.$r::$slug],
                [
                    'title' => 'Force Delete '.$r->pluralName(),
                    'name' => 'Force Delete '.$r->pluralName(),
                    'slug' => 'forceDelete-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'scope-'.$r::$slug],
                [
                    'title' => 'Scope '.$r->pluralName(),
                    'name' => 'Scope '.$r->pluralName(),
                    'slug' => 'scope-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );
        }

        $this->info('Resource permissions created successfully');
    }
}
```

## ./PublishCommand.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish all of the Aura resources';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aura:publish';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $assetPath = public_path('vendor/aura/assets');

        if (File::exists($assetPath)) {
            File::deleteDirectory($assetPath);
        }

        $this->call('vendor:publish', [
            '--tag' => 'aura-assets',
            '--force' => true,
        ]);
    }
}
```

## ./TransferFromPostsToCustomTable.php
```
<?php

namespace Aura\Base\Commands;

use ReflectionClass;
use Aura\Base\Facades\Aura;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use function Laravel\Prompts\info;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\error;
use function Laravel\Prompts\select;
use function Laravel\Prompts\confirm;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class TransferFromPostsToCustomTable extends Command
{
    protected $signature = 'aura:transfer-from-posts-to-custom-table {resource?}';
    protected $description = 'Transfer resources from posts and meta tables to custom tables';

    public function handle()
    {
        // Get resource class from argument or prompt user to select
        $resourceClass = $this->argument('resource');

        if ($resourceClass) {
            // Validate that the provided resource class exists
            if (!class_exists($resourceClass)) {
                error("Resource class '{$resourceClass}' does not exist.");
                return Command::FAILURE;
            }
        } else {
            // Step 1: Ask which resource to use if not provided
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
        }

        $this->transferData($resourceClass);
        info('Transfer process completed.');
    }

    protected function transferData($resourceClass)
    {
        $resourceInstance = new $resourceClass;
        $type = $resourceInstance->getType();

        info('Transferring data from posts to: ' . $resourceClass);

        // Fetch posts of the specific type along with their meta data
        $posts = DB::table('posts')->where('type', $type)->get();

        // Initialize progress bar
        $this->output->progressStart($posts->count());

        foreach ($posts as $post) {

            // Get all meta for this post
            $metas = DB::table('meta')
                ->where('metable_type', get_class($resourceInstance))
                ->where('metable_id', $post->id)
                ->get();

            // Prepare record data combining post and meta fields
            $newRecord = [
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
                'user_id' => $post->user_id,
                'team_id' => $post->team_id,
            ];

            foreach ($metas as $meta) {
                $newRecord[$meta->key] = $meta->value;
            }

            // Create new record using the resource
            app($resourceClass)->create($newRecord);

            // Advance the progress bar
            $this->output->progressAdvance();
        }

        // Finish the progress bar
        $this->output->progressFinish();

        info('Data transfer completed.');
    }
}
```

## ./AuraLayoutCommand.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AuraLayoutCommand extends Command
{
    protected $description = 'Copy Aura layout file to the project for customization';

    protected $signature = 'aura:layout';

    public function handle()
    {
        $sourcePath = 'vendor/eminiarts/aura/resources/views/components/layout/app.blade.php';
        $destinationPath = 'resources/views/vendor/aura/components/layout/app.blade.php';

        if (! File::exists($sourcePath)) {
            $this->error('Aura layout file not found. Make sure the Aura package is installed.');

            return 1;
        }

        File::ensureDirectoryExists(dirname($destinationPath));

        try {
            File::copy($sourcePath, $destinationPath);
            $this->info('Aura layout file copied successfully.');
            $this->info("You can now customize the layout at: $destinationPath");
        } catch (\Exception $e) {
            $this->error('Failed to copy Aura layout file: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
```

## ./TransformTableToResource.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TransformTableToResource extends Command
{
    protected $description = 'Create a resource based on a specific database table';

    protected $signature = 'aura:transform-table-to-resource {table}';

    public function handle()
    {
        $table = $this->argument('table');
        $columns = Schema::getColumnListing($table);
        $resourceName = Str::studly(Str::singular($table));

        $fields = $this->generateFields($columns);

        $resourceContent = $this->generateResourceContent($resourceName, $fields);
        $this->saveResourceFile($resourceName, $resourceContent);

        $this->info("Resource {$resourceName} generated successfully");
    }

    private function generateFields(array $columns): array
    {
        $fields = [];

        // Add your custom column to field type mapping logic here
        foreach ($columns as $column) {
            $columnType = Schema::getColumnType($this->argument('table'), $column);
            $fieldType = $this->getFieldTypeFromColumnType($columnType);

            $fields[] = [
                'name' => ucfirst($column),
                'slug' => $column,
                'type' => $fieldType,
                'validation' => '',
            ];
        }

        return $fields;
    }

    private function generateResourceContent(string $resourceName, array $fields): string
    {
        $fieldsContent = '';

        foreach ($fields as $field) {
            $fieldsContent .= "            [\n";
            $fieldsContent .= "                'name' => '{$field['name']}',\n";
            $fieldsContent .= "                'slug' => '{$field['slug']}',\n";
            $fieldsContent .= "                'type' => '{$field['type']}',\n";
            $fieldsContent .= "                'validation' => '{$field['validation']}',\n";
            $fieldsContent .= "            ],\n";
        }

        return <<<EOT
<?php

namespace App\Aura\Resources;

use Aura\Base\Models\Post;

class {$resourceName} extends Resource
{
    public static string \$type = '{$resourceName}';

    public static ?string \$slug = '{$resourceName}';

    public static function getWidgets(): array
    {
        return [];
    }

    public function getIcon()
    {
        return '<svg class="w-5 h-5" viewBox="0 0 18 18" fill="none" stroke="currentColor" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.75 9a6.75 6.75 0 1 1-13.5 0 6.75 6.75 0 0 1 13.5 0Z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    public static function getFields()
    {
        return [
{$fieldsContent}
        ];
    }
}

EOT;
    }

    private function getFieldTypeFromColumnType(string $columnType): string
    {
        switch ($columnType) {
            case 'text':
            case 'longtext':
                return 'Aura\\Base\\Fields\\Textarea';
            case 'integer':
            case 'float':
            case 'double':
                return 'Aura\\Base\\Fields\\Number';
            case 'date':
                return 'Aura\\Base\\Fields\\Date';
                // Add more cases as needed
            default:
                return 'Aura\\Base\\Fields\\Text';
        }
    }

    private function saveResourceFile(string $resourceName, string $resourceContent)
    {
        $filesystem = new Filesystem;

        $resourcesDirectory = app_path('Aura/Resources');
        if (! $filesystem->exists($resourcesDirectory)) {
            $filesystem->makeDirectory($resourcesDirectory, 0755, true);
        }

        $resourceFile = "{$resourcesDirectory}/{$resourceName}.php";

        if ($filesystem->exists($resourceFile)) {
            $this->error("Resource file '{$resourceName}.php' already exists.");

            return;
        }

        $filesystem->put($resourceFile, $resourceContent);
    }
}
```

## ./CreateAuraPlugin.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CreateAuraPlugin extends Command
{
    protected $description = 'Create a new Aura plugin';

    protected $signature = 'aura:plugin {name?}';

    public function getStubsDirectory($path)
    {
        return __DIR__.'/../../stubs/'.$path;
    }

    public function handle()
    {

        if ($this->argument('name')) {

            $vendorAndName = $this->argument('name');

        } else {

            $vendorAndName = text(
                label: 'What is the name of your plugin?',
                placeholder: 'E.g. aura/plugin (vendor/name)',
            );

        }

        [$vendor, $name] = explode('/', $vendorAndName);

        $pluginType = select(
            label: 'What type of plugin do you want to create?',
            options: [
                'plugin' => 'Complete plugin',
                'plugin-resource' => 'Resource plugin',
                'plugin-field' => 'Field plugin',
                'plugin-widget' => 'Widget plugin',
            ],
            default: 'plugin',
        );

        $pluginDirectory = base_path("plugins/{$vendor}/{$name}");
        File::makeDirectory($pluginDirectory, 0755, true);

        $stubDirectory = $this->getStubsDirectory($pluginType);

        File::copyDirectory($stubDirectory, $pluginDirectory);

        $this->line("{$pluginType} created at {$pluginDirectory}");

        $this->line('Replacing placeholders...');
        // $this->runProcess("php {$pluginDirectory}/configure.php --vendor={$vendor} --name={$name}");

        $result = Process::path($pluginDirectory)->run("php ./configure.php --vendor={$vendor} --name={$name}");

        $this->line($result->output());

        if ($this->confirm('Do you want to append '.str($name)->title().'ServiceProvider to config/app.php?')) {
            $providerClassName = str($name)->title().'ServiceProvider';
            $configFile = base_path('config/app.php');
            $configContent = File::get($configFile);
            $newProvider = str($vendor)->title().'\\'.str($name)->title()."\\{$providerClassName}::class";
            $configContent = str_replace("App\Providers\AppServiceProvider::class,", "{$newProvider},\n\n        App\Providers\AppServiceProvider::class,", $configContent);
            File::put($configFile, $configContent);
            $this->line("{$providerClassName} added to config/app.php");
        }

        $this->line('Updating composer.json...');
        $composerJsonFile = base_path('composer.json');
        $composerJson = json_decode(File::get($composerJsonFile), true);
        $composerJson['autoload']['psr-4'][ucfirst($vendor).'\\'.ucfirst($name).'\\']
        = "plugins/{$vendor}/{$name}/src";
        File::put($composerJsonFile, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->line('composer.json updated');

        $this->line('composer dump-autoload...');

        Process::run('composer dump-autoload');

        $this->line('Plugin created successfully!');
    }
}
```

## ./CustomizeComponent.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\select;

class CustomizeComponent extends Command
{
    protected $description = 'Customize a component for a specific resource';

    protected $signature = 'aura:customize-component';

    public function handle()
    {
        $componentType = select(
            label: 'Which component would you like to customize?',
            options: ['Index', 'Create', 'Edit', 'View'],
            default: 'Edit'
        );

        $resources = collect(app('aura')::getResources())->mapWithKeys(function ($resource) {
            return [$resource => class_basename($resource)];
        });

        $resourceOptions = collect($resources)->mapWithKeys(function ($resourceName, $resourceClass) {
            return [$resourceClass => $resourceName];
        })->toArray();

        $resourceClass = select(
            label: 'For which resource?',
            options: $resourceOptions,
            scroll: 10
        );

        // dd($resourceClass, $resources);

        $resourceName = $resources[$resourceClass];

        $this->createCustomComponent($componentType, $resourceClass, $resourceName);
        $this->updateRoute($componentType, $resourceClass, $resourceName);

        $this->components->info("Custom {$componentType} component for {$resourceName} has been created and route has been updated.");
    }

    protected function createCustomComponent($componentType, $resourceClass, $resourceName)
    {
        $componentName = "{$componentType}{$resourceName}";

        $stubPath = __DIR__.'/Stubs/livewire.custom.stub';
        $componentPath = app_path("Livewire/{$componentName}.php");

        $stub = file_get_contents($stubPath);
        $stub = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ baseClass }}', '{{ componentType }}', '{{ resourceClass }}'],
            ['App\\Livewire', $componentName, "Aura\\Base\\Livewire\\Resource\\{$componentType}", $componentType, $resourceName],
            $stub
        );

        // Ensure the directory exists
        $directory = dirname($componentPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($componentPath, $stub);

        $this->components->info("Created component: {$componentName}");
    }

    protected function updateRoute($componentType, $resourceClass, $resourceName)
    {
        $routeFile = base_path('routes/web.php');
        $routeContents = file_get_contents($routeFile);

        $resourceSlug = Str::kebab($resourceName);

        $newRoute = "Route::get('admin/{$resourceSlug}/{id}/".Str::lower($componentType)."', App\\Livewire\\{$componentType}{$resourceName}::class)->name('{$resourceSlug}.".Str::lower($componentType)."');";

        // Append the new route to the end of the file
        $updatedContents = $routeContents."\n".$newRoute;

        file_put_contents($routeFile, $updatedContents);

        $this->components->info("Added new route for: {$componentType}{$resourceName}");
    }
}
```

## ./Installation.php
```
<?php

namespace Aura\Base\Commands;

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class MakeUser extends Command
{
    protected $description = 'Creates an Aura Super Admin.';

    protected $signature = 'aura:user
                            {--name= : The name of the user}
                            {--email= : A valid email address}
                            {--password= : The password for the user}';

    public function handle(): int
    {
        $name = $this->option('name') ?? text('What is your name?');
        $email = $this->option('email') ?? text('What is your email?');
        $password = $this->option('password') ?? password('What is your password?');

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'fields' => [
                'password' => $password,
            ],
        ]);

        if (config('aura.teams')) {
            DB::table('teams')->insert([
                'name' => $name,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $team = Team::first();
            $user->current_team_id = $team->id;
            $user->save();
        }

        auth()->loginUsingId($user->id);

        $roleData = [
            'name' => 'Super Admin',
            'slug' => 'super_admin',
            'description' => 'Super Admin can perform everything.',
            'super_admin' => true,
            'permissions' => [],
            'user_id' => $user->id,
            'team_id' => $team->id ?? null,
        ];

        if (config('aura.teams')) {
            $roleData['team_id'] = $team->id;
        }

        $role = Role::create($roleData);

        $user->update(['roles' => [$role->id]]);

        $this->info('User created successfully.');

        return static::SUCCESS;
    }
}
```

## ./InstallConfigCommand.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\text;
use function Laravel\Prompts\select;
use function Laravel\Prompts\confirm;
use Symfony\Component\Process\Process;

class InstallConfigCommand extends Command
{
    public $description = 'Install Aura Configuration';

    public $signature = 'aura:install-config';

    public function handle(): int
    {
        // 1. Do you want to use teams?
        $useTeams = confirm('Do you want to use teams?');

        // Get the config path
        $configPath = config_path('aura.php');

        // Include the config array
        $config = include $configPath;

        // Modify the 'teams' value
        $config['teams'] = $useTeams;

        // 2. Do you want to modify default features?
        $modifyFeatures = confirm('Do you want to modify the default features?');

        if ($modifyFeatures) {
            // For each feature, ask if they want to enable/disable it
            $features = $config['features'];

            foreach ($features as $feature => $value) {
                $features[$feature] = confirm("Enable feature '{$feature}'?", $value);
            }

            // Update the features in config
            $config['features'] = $features;
        }

        // 3. Do you want to allow registration?
        $allowRegistration = confirm('Do you want to allow registration?');

        // Update the env variable AURA_REGISTRATION
        $this->setEnvValue('AURA_REGISTRATION', $allowRegistration ? 'true' : 'false');

        // 4. Do you want to modify the default theme?
        $modifyTheme = confirm('Do you want to modify the default theme?');

        if ($modifyTheme) {
            $theme = $config['theme'];

            foreach ($theme as $option => $currentValue) {

                if (in_array($option, ['login-bg', 'login-bg-darkmode', 'app-favicon', 'app-favicon-darkmode', 'sidebar-darkmode-type'])) {
                    continue;
                }

                if ($option == 'color-palette') {
                    $choices = ['aura', 'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose', 'mountain-meadow', 'sandal', 'slate', 'gray', 'zinc', 'neutral', 'stone'];
                    $theme[$option] = select(
                        label: "Select value for '{$option}':",
                        options: $choices,
                        default: $currentValue
                    );
                } elseif ($option == 'gray-color-palette') {
                    $choices = ['slate', 'purple-slate', 'gray', 'zinc', 'neutral', 'stone', 'blue', 'smaragd', 'dark-slate', 'blackout'];
                    $theme[$option] = select(
                        label: "Select value for '{$option}':",
                        options: $choices,
                        default: $currentValue
                    );
                } elseif ($option == 'darkmode-type') {
                    $choices = ['auto', 'light', 'dark'];
                    $theme[$option] = select(
                        label: "Select value for '{$option}':",
                        options: $choices,
                        default: $currentValue
                    );
                } elseif ($option == 'sidebar-size') {
                    $choices = ['standard', 'compact'];
                    $theme[$option] = select(
                        label: "Select value for '{$option}':",
                        options: $choices,
                        default: $currentValue
                    );
                } elseif ($option == 'sidebar-type') {
                    $choices = ['primary', 'light', 'dark'];
                    $theme[$option] = select(
                        label: "Select value for '{$option}':",
                        options: $choices,
                        default: $currentValue
                    );
                } elseif (is_bool($currentValue)) {
                    // Boolean option
                    $theme[$option] = confirm("Enable '{$option}'?", $currentValue);
                } else {
                    // For other options, just ask for the value
                    $theme[$option] = text(
                        label: "Enter value for '{$option}':",
                        default: $currentValue
                    );
                }
            }

            // Update the theme in config
            $config['theme'] = $theme;
        }

        // Now, write back the config file
        $arrayExport = var_export($config, true);
        
        // Remove numeric array keys
        $arrayExport = preg_replace("/[0-9]+ => /", "", $arrayExport);
        
        $code = '<?php' . PHP_EOL . PHP_EOL . 'return ' . str_replace(
            ['array (', ')', "[\n    ]"],
            ['[', ']', '[]'],
            $arrayExport
        ) . ';' . PHP_EOL;
        
        file_put_contents($configPath, $code);

        $this->info('Aura configuration has been updated.');

         // Run Pint on the file after the file has been written
        $process = new Process(['vendor/bin/pint', $configPath]);
        $process->run();

        // Check if the process was successful
        if (!$process->isSuccessful()) {
            $this->error('Pint formatting failed: ' . $process->getErrorOutput());
        } else {
            $this->info('Pint formatting completed.');
        }
        
    

        return self::SUCCESS;
    }

    private function setEnvValue($key, $value)
    {
        $envPath = base_path('.env');

        if (file_exists($envPath)) {
            // Read the .env file
            $env = file_get_contents($envPath);

            // Replace the value
            $pattern = '/^' . preg_quote($key, '/') . '=.*/m';
            $replacement = $key . '=' . $value;

            if (preg_match($pattern, $env)) {
                // Replace existing value
                $env = preg_replace($pattern, $replacement, $env);
            } else {
                // Add new value
                $env .= PHP_EOL . $replacement;
            }

            // Write back to the .env file
            file_put_contents($envPath, $env);
        }
    }
}
```

## ./ExtendUserModel.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ExtendUserModel extends Command
{
    protected $description = 'Extend the User model with AuraUser';

    protected $signature = 'aura:extend-user-model';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $filesystem = new Filesystem;
        $userModelPath = app_path('Models/User.php');

        if ($filesystem->exists($userModelPath)) {
            $content = $filesystem->get($userModelPath);

            if (strpos($content, 'extends AuraUser') === false) {
                if ($this->confirm('Do you want to extend the User model with AuraUser?', true)) {
                    // Remove any incorrect `use` statements and add the correct one
                    $content = preg_replace('/use .+Aura\\\\Base\\\\Resources\\\\User as AuraUser;/m', '', $content);
                    $content = str_replace('extends Authenticatable', 'extends AuraUser', $content);
                    $content = preg_replace('/^namespace [^;]+;/m', "$0\nuse Aura\\Base\\Resources\\User as AuraUser;", $content);

                    $filesystem->put($userModelPath, $content);

                    $this->info('User model successfully extended with AuraUser.');
                } else {
                    $this->info('User model extension cancelled.');
                }
            } else {
                $this->info('User model already extends AuraUser.');
            }
        } else {
            $this->error('User model not found.');
        }
    }
}
```

## ./MakeField.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeField extends GeneratorCommand
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Aura Field';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aura:field {name}';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Field';

    public function handle()
    {
        parent::handle();

        $this->createViewFile();
        $this->createEditFile();

        $this->info('Field created successfully.');
    }

    protected function buildEditFileContents()
    {
        $contents = $this->files->get(__DIR__.'/Stubs/make-field-edit.stub');

        // replace :fieldSlug with the actual slug
        $contents = str_replace(':fieldSlug', str($this->argument('name'))->slug(), $contents);

        return $contents;
    }

    protected function buildViewFileContents()
    {
        return $this->files->get(__DIR__.'/Stubs/make-field-view.stub');
    }

    protected function createEditFile()
    {
        $name = $this->argument('name');
        $slug = str($name)->slug();

        $path = resource_path('views/components/fields/'.$slug.'.blade.php');

        if (! $this->files->exists(dirname($path))) {
            // create the directory if it doesn't exist
            $this->files->makeDirectory(dirname($path), 0755, true);
        }

        if (! $this->files->exists($path)) {
            $this->files->put($path, $this->buildEditFileContents());
        }
    }

    protected function createViewFile()
    {
        $name = $this->argument('name');
        $slug = str($name)->slug();

        $path = resource_path('views/components/fields/'.$slug.'-view.blade.php');

        if (! $this->files->exists(dirname($path))) {
            // create the directory if it doesn't exist
            $this->files->makeDirectory(dirname($path), 0755, true);
        }

        if (! $this->files->exists($path)) {
            $this->files->put($path, $this->buildViewFileContents());
        }
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Aura\Fields';
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/Stubs/make-field.stub';
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $stub = parent::replaceClass($stub, $name);

        $stub = str_replace('FieldName', ucfirst($this->argument('name')), $stub);
        $stub = str_replace('FieldSlug', str($this->argument('name'))->slug(), $stub);

        return $stub;
    }
}
```

## ./CreateResourceFactory.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;

use function Laravel\Prompts\search;

class CreateResourceFactory extends Command
{
    protected $description = 'Create a factory based on the fields of a resource';

    protected $files;

    protected $signature = 'aura:create-resource-factory {resource?}';

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $resourceClass = $this->argument('resource');

        if (! $resourceClass) {
            $resources = collect(\Aura\Base\Facades\Aura::getResources())->mapWithKeys(function ($resource) {
                return [$resource => $resource];
            });

            $resourceClass = search(
                'Search for the resource you want to create a factory for',
                fn (string $value) => strlen($value) > 0
                    ? $resources->filter(function ($resource) use ($value) {
                        return str_contains(strtolower($resource), strtolower($value));
                    })->all()
                    : $resources->all()
            );
        }

        if (! class_exists($resourceClass)) {
            $this->error("Resource class '{$resourceClass}' not found.");

            return 1;
        }

        $resource = app($resourceClass);

        if (! method_exists($resource, 'getFields')) {
            $this->error("Method 'getFields' not found in the '{$resourceClass}' class.");

            return 1;
        }

        $modelName = class_basename($resourceClass);
        $factoryName = "{$modelName}Factory";

        // Create the factory file
        Artisan::call('make:factory', [
            'name' => $factoryName,
            '--model' => $modelName,
        ]);

        $factoryPath = database_path("factories/{$factoryName}.php");

        if (! $this->files->exists($factoryPath)) {
            $this->error("Unable to create factory file '{$factoryName}'.");

            return 1;
        }

        // Generate factory content
        $factoryContent = $this->generateFactoryContent($resourceClass, $modelName);

        // Update the factory file
        $this->files->put($factoryPath, $factoryContent);

        $this->info("Factory '{$factoryName}' created successfully.");

        // Inform the user about adding the newFactory method to the Resource
        $this->info("Don't forget to add the following method to your {$modelName} Resource:");
        $this->info("
    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return {$factoryName}::new();
    }
        ");

    }

    protected function generateFactoryContent($resource, $modelName)
    {
        $fields = app($resource)->getFields();
        $factoryDefinition = $this->generateFactoryDefinition($fields);

        return <<<PHP
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use {$resource};

class {$modelName}Factory extends Factory
{
    protected \$model = {$modelName}::class;

    public function definition()
    {
        return [
{$factoryDefinition}
        ];
    }
}
PHP;
    }

    protected function generateFactoryDefinition($fields)
    {
        $definition = '';

        foreach ($fields as $field) {
            $faker = $this->getFakerMethod($field);
            $definition .= "            '{$field['slug']}' => {$faker},\n";
        }

        return rtrim($definition);
    }

    protected function getFakerMethod($field)
    {
        $type = class_basename($field['type']);

        switch ($type) {
            case 'Text':
                return '$this->faker->sentence';
            case 'Textarea':
                return '$this->faker->paragraph';
            case 'Email':
                return '$this->faker->unique()->safeEmail';
            case 'Number':
                return '$this->faker->randomNumber()';
            case 'DateTime':
                return '$this->faker->dateTime()';
            case 'Date':
                return '$this->faker->date()';
            case 'Boolean':
                return '$this->faker->boolean';
            case 'Select':
            case 'Radio':
                // Assuming options are available, adjust if necessary
                return '$this->faker->randomElement(["option1", "option2", "option3"])';
            case 'BelongsTo':
                $relatedModel = class_basename($field['resource']);

                return "\\{$field['resource']}::factory()";
            default:
                return '$this->faker->word';
        }
    }
}
```

## ./MigratePostMetaToMeta.php
```
<?php

namespace Aura\Base\Commands;

use Aura\Base\Resources\Post;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class MigratePostMetaToMeta extends Command
{
    protected $description = 'Migrate post_meta to meta';

    protected $signature = 'aura:migrate-post-meta-to-meta';

    public function handle()
    {
        $this->info('Starting migration of post_meta, team_meta, and user_meta to meta table...');

        if (!Schema::hasTable('meta')) {
            Schema::create('meta', function (Blueprint $table) {
                $table->id();
                $table->morphs('metable');
                $table->string('key')->nullable()->index();
                $table->longText('value')->nullable();
                $table->index(['metable_type', 'metable_id', 'key']);
            });
        }

        // Migrate post_meta
        $postMeta = DB::table('post_meta')->get();
        foreach ($postMeta as $meta) {
            $post = DB::table('posts')->where('id', $meta->post_id)->first();
            $type = $post->type;
            $metableType = \Aura\Base\Facades\Aura::findResourceBySlug($type);

            // dd($metableType::class);

            DB::table('meta')->insert([
                'key' => $meta->key,
                'value' => $meta->value,
                'metable_id' => $meta->post_id,
                'metable_type' => $metableType::class,
            ]);
        }
        $this->info('Migrated post_meta to meta table.');

        // Migrate team_meta
        $teamMeta = DB::table('team_meta')->get();
        foreach ($teamMeta as $meta) {
            DB::table('meta')->insert([
                'key' => $meta->key,
                'value' => $meta->value,
                'metable_id' => $meta->team_id,
                'metable_type' => Team::class,
            ]);
        }
        $this->info('Migrated team_meta to meta table.');

        // Migrate user_meta
        $userMeta = DB::table('user_meta')->get();
        foreach ($userMeta as $meta) {
            DB::table('meta')->insert([
                'key' => $meta->key,
                'value' => $meta->value,
                'metable_id' => $meta->user_id,
                'metable_type' => User::class,
            ]);
        }
        $this->info('Migrated user_meta to meta table.');

        $this->info('Migration completed successfully.');
    }
}
```

## ./MakeUser.php
```
<?php

namespace Aura\Base\Commands;

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class MakeUser extends Command
{
    protected $description = 'Creates an Aura Super Admin.';

    protected $signature = 'aura:user
                            {--name= : The name of the user}
                            {--email= : A valid email address}
                            {--password= : The password for the user}';

    public function handle(): int
    {
        $name = $this->option('name') ?? text('What is your name?');
        $email = $this->option('email') ?? text('What is your email?');
        $password = $this->option('password') ?? password('What is your password?');

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'fields' => [
                'password' => $password,
            ],
        ]);

        if (config('aura.teams')) {
            DB::table('teams')->insert([
                'name' => $name,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $team = Team::first();
            $user->current_team_id = $team->id;
            $user->save();
        }

        auth()->loginUsingId($user->id);

        $roleData = [
            'name' => 'Super Admin',
            'slug' => 'super_admin',
            'description' => 'Super Admin can perform everything.',
            'super_admin' => true,
            'permissions' => [],
            'user_id' => $user->id,
            'team_id' => $team->id ?? null,
        ];

        if (config('aura.teams')) {
            $roleData['team_id'] = $team->id;
        }

        $role = Role::create($roleData);

        $user->update(['roles' => [$role->id]]);

        $this->info('User created successfully.');

        return static::SUCCESS;
    }
}
```

## ./MakeResource.php
```
<?php

namespace Aura\Base\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeResource extends GeneratorCommand
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Aura Resource';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aura:resource {name} {--custom}';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Resource';

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Aura\Resources';
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('custom')) {
            return __DIR__.'/Stubs/make-custom-resource.stub';
        }

        return __DIR__.'/Stubs/make-resource.stub';
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $stub = parent::replaceClass($stub, $name);

        $stub = str_replace('PostName', ucfirst($this->argument('name')), $stub);
        $stub = str_replace('PostSlug', str($this->argument('name'))->slug(), $stub);
        $stub = str_replace('post_slug', str($this->argument('name'))->snake()->plural(), $stub);

        return $stub;
    }
}
```

