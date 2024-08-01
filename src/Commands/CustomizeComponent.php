<?php

namespace Aura\Base\Commands;

use Aura\Base\Facades\Aura;
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

        $resources = Aura::getResources();

        $resourceOptions = collect($resources)->mapWithKeys(function ($resource) {
            return [$resource => app($resource)->getType()];
        })->toArray();

        $resourceName = select(
            label: 'For which resource?',
            options: $resourceOptions,
            scroll: 10
        );

        $this->createCustomComponent($componentType, $resourceName);
        $this->updateRoute($componentType, $resourceName);

        $this->components->info("Custom {$componentType} component for {$resourceName} has been created and route has been updated.");
    }

    protected function createCustomComponent($componentType, $resourceName)
    {
        // Extract the class name from the full namespace
        $resourceClass = class_basename($resourceName);
        $componentName = "{$componentType}{$resourceClass}";

        $stubPath = __DIR__.'/Stubs/livewire.custom.stub';
        $componentPath = app_path("Http/Livewire/{$componentName}.php");

        $stub = file_get_contents($stubPath);
        $stub = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ baseClass }}', '{{ componentType }}', '{{ resourceClass }}'],
            ['App\\Http\\Livewire', $componentName, "Aura\\Base\\Livewire\\Resource\\{$componentType}", $componentType, $resourceClass],
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

    protected function updateRoute($componentType, $resourceName)
    {
        $routeFile = base_path('routes/web.php');
        $routeContents = file_get_contents($routeFile);

        $resourceClass = class_basename($resourceName);
        $resourceSlug = Str::kebab($resourceClass);

        $newRoute = "Route::get('admin/{$resourceSlug}/{id}/".Str::lower($componentType)."', App\\Http\\Livewire\\{$componentType}{$resourceClass}::class)->name('{$resourceSlug}.".Str::lower($componentType)."');";

        // Append the new route to the end of the file
        $updatedContents = $routeContents."\n".$newRoute;

        file_put_contents($routeFile, $updatedContents);

        $this->components->info("Added new route for: {$componentType}{$resourceClass}");
    }
}
