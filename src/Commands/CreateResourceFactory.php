<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use function Laravel\Prompts\select;
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

        if (!$resourceClass) {
            $resources = collect(\Aura\Base\Facades\Aura::getResources())->mapWithKeys(function ($resource) {
                return [$resource => $resource];
            });

            $resourceClass = search(
                'Search for the resource you want to create a factory for',
                fn (string $value) => strlen($value) > 0
                    ? $resources->filter(function($resource) use ($value) {
                        return str_contains(strtolower($resource), strtolower($value));
                      })->all()
                    : $resources->all()
            );
        }

        if (!class_exists($resourceClass)) {
            $this->error("Resource class '{$resourceClass}' not found.");
            return 1;
        }

        $resource = app($resourceClass);

        if (!method_exists($resource, 'getFields')) {
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

        if (!$this->files->exists($factoryPath)) {
            $this->error("Unable to create factory file '{$factoryName}'.");
            return 1;
        }

        // Generate factory content
        $factoryContent = $this->generateFactoryContent($resource, $modelName);

        // Update the factory file
        $this->files->put($factoryPath, $factoryContent);

        $this->info("Factory '{$factoryName}' created successfully.");
    }

    protected function generateFactoryContent($resource, $modelName)
    {
        $fields = $resource->getFields();
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