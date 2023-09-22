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

        $baseFields = collect([
            [
                'name' => 'ID',
                'type' => 'Eminiarts\\Aura\\Fields\\ID',
                'slug' => 'id',
            ],
            [
                'name' => 'Title',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'slug' => 'title',
            ],
            [
                'name' => 'Slug',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'slug' => 'slug',
            ],
            [
                'name' => 'Content',
                'type' => 'Eminiarts\\Aura\\Fields\\Textarea',
                'slug' => 'content',
            ],
            [
                'name' => 'Status',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'slug' => 'status',
            ],
            [
                'name' => 'Parent ID',
                'type' => 'Eminiarts\\Aura\\Fields\\ForeignId',
                'slug' => 'parent_id',
            ],
            [
                'name' => 'Order',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'slug' => 'order',
            ],
             
        ]);

        $fields = $resource->inputFields();

        $combined = $baseFields->merge($fields)->merge(collect([
            [
                'name' => 'User Id',
                'type' => 'Eminiarts\\Aura\\Fields\\ForeignId',
                'slug' => 'user_id',
            ],
             [
                'name' => 'Team Id',
                'type' => 'Eminiarts\\Aura\\Fields\\ForeignId',
                'slug' => 'team_id',
            ],
            [
                'name' => 'timestamps',
                'type' => 'Eminiarts\\Aura\\Fields\\Timestamps',
                'slug' => 'timestamps',
            ],
        ]));

        $schema = $this->generateSchema($combined);

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

        // Up method
        $pattern = '/(public function up\(\): void[\s\S]*?Schema::create\(.*?function \(Blueprint \$table\) \{[\s\S]*?)\/\/([\s\S]*?\}\);[\s\S]*?\})/';
        $replacement = '${1}'.$schema.'${2}';
        $replacedContent = preg_replace($pattern, $replacement, $content);

        // Down method
        $down = "Schema::dropIfExists('{$tableName}');";
        $pattern = '/(public function down\(\): void[\s\S]*?{)[\s\S]*?Schema::table\(.*?function \(Blueprint \$table\) \{[\s\S]*?\/\/[\s\S]*?\}\);[\s\S]*?\}/';
        $replacement = '${1}'.PHP_EOL.'    '.$down.PHP_EOL.'}';
        $replacedContent2 = preg_replace($pattern, $replacement, $replacedContent);

        $this->files->put($migrationFile, $replacedContent2);

        $this->info("Migration '{$migrationName}' created successfully.");

        // Run "pint" on the migration file
        exec('./vendor/bin/pint '.$migrationFile);

        $this->info("Pint applied to '{$migrationName}'.");
    }

    protected function generateColumn($field)
    {
        // You can customize the following method to generate the schema based on your Resource fields
        $type = $field['type'];
        $slug = $field['slug'];

        return match ($type) {
            'Eminiarts\\Aura\\Fields\\ID' => "\$table->id();\n",
            'Eminiarts\\Aura\\Fields\\ForeignId' => "\$table->foreignId('{$slug}')->nullable();\n",
            'Eminiarts\\Aura\\Fields\\Timestamps' => "\$table->timestamps();\n",
            'Eminiarts\\Aura\\Fields\\Text' => "\$table->string('{$slug}')->nullable();\n",
            'Eminiarts\\Aura\\Fields\\Slug' => "\$table->string('{$slug}')->unique();\n",
            'Eminiarts\\Aura\\Fields\\Image' => "\$table->text('{$slug}')->nullable();\n",
            'Eminiarts\\Aura\\Fields\\Password' => "\$table->string('{$slug}')->nullable();\n",
            'Eminiarts\\Aura\\Fields\\Number' => "\$table->integer('{$slug}')->nullable();\n",
            'Eminiarts\\Aura\\Fields\\Date' => "\$table->date('{$slug}')->nullable();\n",
            'Eminiarts\\Aura\\Fields\\Textarea' => "\$table->text('{$slug}')->nullable();\n",
            'Eminiarts\\Aura\\Fields\\Color' => "\$table->string('{$slug}')->nullable();\n",
            'Eminiarts\\Aura\\Fields\\BelongsTo' => "\$table->foreignId('{$slug}')->nullable();\n",
            'Eminiarts\\Aura\\Fields\\Boolean' => "\$table->boolean('{$slug}')->nullable();\n",
            // 'Eminiarts\Aura\Fields\Tags', 'Eminiarts\Aura\Fields\BelongsTo' => {
            //     $relatedResource = $field['resource'];
            //     $relatedModel = new $relatedResource();
            //     $relatedTableName = $relatedModel->getTable();
            //     return "{\$table}->foreignId('{$slug}')->nullable()->constrained('{$relatedTableName}')->onDelete('cascade');\n";
            // },
            'Eminiarts\Aura\Fields\HasMany' => '', // No need to add anything to the schema for HasMany relationships
            default => "\$table->text('{$slug}')->nullable(); // Add your custom field type schema generation here for '{$slug}' with type '{$type}'\n",
        };
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
}
