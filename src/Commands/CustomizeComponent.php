<?php

namespace Aura\Base\Commands;

use Aura\Base\Facades\Aura;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use function Laravel\Prompts\select;
use function Laravel\Prompts\confirm;

class CustomizeComponent extends Command
{
    protected $signature = 'aura:customize-component';
    protected $description = 'Customize a component for a specific resource';

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

        if (confirm("Do you want to customize the {$componentType} component for {$resourceName}?", default: true)) {
            $this->createCustomComponent($componentType, $resourceName);
            $this->updateRoute($componentType, $resourceName);

            $this->components->info("Custom {$componentType} component for {$resourceName} has been created and route has been updated.");
        } else {
            $this->components->info('Operation cancelled.');
        }
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
            ['{{ namespace }}', '{{ class }}', '{{ baseClass }}'],
            ['App\\Http\\Livewire', $componentName, "Aura\\Http\\Livewire\\{$componentType}"],
            $stub
        );

        // Ensure the directory exists
        $directory = dirname($componentPath);
        
        if (!is_dir($directory)) {
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
        $search = "Route::get('/{slug}/{id}/" . Str::lower($componentType) . "', {$componentType}::class)->name('resource." . Str::lower($componentType) . "');";
        $replace = "Route::get('/{slug}/{id}/" . Str::lower($componentType) . "', App\\Http\\Livewire\\{$componentType}{$resourceClass}::class)->name('resource." . Str::lower($componentType) . "');";

        $updatedContents = str_replace($search, $replace, $routeContents);
        file_put_contents($routeFile, $updatedContents);

        $this->components->info("Updated route for: {$componentType}{$resourceClass}");
    }
}