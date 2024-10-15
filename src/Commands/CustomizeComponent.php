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
