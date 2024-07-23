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
