<?php

namespace Eminiarts\Aura\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

        $resource = new $resourceClass();

        if (! method_exists($resource, 'getFields')) {
            $this->error("Method 'getFields' not found in the '{$resourceClass}' class.");

            return 1;
        }

        $tableName = $resource->getTable();
        $migrationName = "create_{$tableName}_table";

        if ($this->migrationExists($migrationName)) {
            $this->error("Migration '{$migrationName}' already exists.");

            return 1;
        }

        $fields = $resource->inputFields();
        $schema = $this->generateSchema($fields);

        // dd($schema);

        Artisan::call('make:migration', [
            'name' => $migrationName,
            '--table' => $tableName,
        ]);

        $migrationFile = $this->getMigrationPath($migrationName);

        if ($migrationFile === null) {
            $this->error("Unable to find migration file '{$migrationName}'.");

            return 1;
        }



        $content = $this->files->get($migrationFile);

        $upReplacement = 'public function up(): void' . PHP_EOL . '    {' . PHP_EOL . '        Schema::table(\'' . $tableName . '\', function (Blueprint $table) {' . PHP_EOL . $schema . '        });' . PHP_EOL . '    }';
        $content = preg_replace('/public function up\(\): void(.*?)\{(\s*?)Schema::table\(\'([a-zA-Z]+)\',/s', $upReplacement, $content);

        $content = preg_replace_callback('/^(\s+)(.*)/m', function ($matches) {
            return str_repeat(' ', max((strlen($matches[1]) - 4), 0)) . $matches[2];
        }, $content);

        $this->files->put($migrationFile, $content);

        $this->info("Migration '{$migrationName}' created successfully.");
    }

    protected function generateColumn($field)
    {
        // You can customize the following method to generate the schema based on your Resource fields
        $type = $field['type'];
        $slug = $field['slug'];

        return match ($type) {
            'Eminiarts\\Aura\\Fields\\Text' => "\$table->string('{$slug}');\n",
            'Eminiarts\Aura\Fields\Slug' => "\$table->string('{$slug}')->unique();\n",
            'Eminiarts\Aura\Fields\Image' => "\$table->text('{$slug}')->nullable();\n",
            'Eminiarts\Aura\Fields\Password' => "\$table->string('{$slug}')->nullable();\n",
            'Eminiarts\Aura\Fields\Number' => "\$table->integer('{$slug}')->nullable();\n",
            'Eminiarts\Aura\Fields\Date' => "\$table->date('{$slug}')->nullable();\n",
            'Eminiarts\Aura\Fields\Textarea' => "\$table->text('{$slug}')->nullable();\n",
            'Eminiarts\Aura\Fields\Color' => "\$table->string('{$slug}')->nullable();\n",
        // 'Eminiarts\Aura\Fields\Tags', 'Eminiarts\Aura\Fields\BelongsTo' => {
        //     $relatedResource = $field['resource'];
        //     $relatedModel = new $relatedResource();
        //     $relatedTableName = $relatedModel->getTable();
        //     return "{\$table}->foreignId('{$slug}')->nullable()->constrained('{$relatedTableName}')->onDelete('cascade');\n";
        // },
            'Eminiarts\Aura\Fields\HasMany' => '', // No need to add anything to the schema for HasMany relationships
            default => "// Add your custom field type schema generation here for '{$slug}' with type '{$type}'\n",
        };
    }

    protected function generateSchema($fields)
    {
        $schema = '';
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
}
