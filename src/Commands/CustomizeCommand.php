<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

class CustomizeCommand extends Command
{
    protected const MODES = ['full', 'view', 'component'];

    protected const TYPES = ['index', 'create', 'edit', 'view'];

    protected $description = 'Customize a resource page: copy its Blade view and/or generate a custom Livewire component that takes over the default route';

    protected $signature = 'aura:customize
        {resource? : Resource class name, slug, or fully qualified class}
        {type?* : Page types to customize: index, create, edit, view}
        {--mode= : What to generate: full (component + view), view (Blade only), or component (class only)}
        {--force : Overwrite existing generated files}';

    public function handle()
    {
        $resourceClass = $this->resolveResource();

        if (! $resourceClass) {
            return self::FAILURE;
        }

        $types = $this->resolveTypes();

        if (! $types) {
            return self::FAILURE;
        }

        $mode = $this->resolveMode();

        if (! $mode) {
            return self::FAILURE;
        }

        $slug = app($resourceClass)->getSlug();
        $baseName = class_basename($resourceClass);

        $resourceFile = $this->resolveWritableResourceFile($resourceClass, $baseName);

        if (! $resourceFile) {
            return self::FAILURE;
        }

        foreach ($types as $type) {
            $this->customize($type, $mode, $resourceFile, $slug, $baseName);
        }

        return self::SUCCESS;
    }

    protected function copyBladeView(string $type, string $slug): void
    {
        $published = resource_path("views/vendor/aura/livewire/resource/{$type}.blade.php");
        $source = File::exists($published)
            ? $published
            : dirname(__DIR__, 2)."/resources/views/livewire/resource/{$type}.blade.php";

        $target = resource_path("views/aura/{$slug}/{$type}.blade.php");

        if (File::exists($target) && ! $this->option('force')) {
            $this->components->warn("View already exists, skipping: {$this->relativePath($target)} (use --force to overwrite)");

            return;
        }

        File::ensureDirectoryExists(dirname($target));
        File::copy($source, $target);

        $this->components->info("Copied view: {$this->relativePath($target)}");
    }

    protected function createComponent(string $componentType, string $componentClass, string $slug, ?string $viewName): void
    {
        $target = app_path("Livewire/{$componentClass}.php");

        if (File::exists($target) && ! $this->option('force')) {
            $this->components->warn("Component already exists, skipping: {$this->relativePath($target)} (use --force to overwrite)");

            return;
        }

        $needsId = in_array($componentType, ['Edit', 'View']);

        $methods = $needsId
            ? <<<PHP
    public function mount(\$id, \$slug = null)
    {
        parent::mount(\$id, \$slug ?? '{$slug}');
    }
PHP
            : <<<PHP
    public function mount(\$slug = null)
    {
        parent::mount(\$slug ?? '{$slug}');
    }
PHP;

        if ($viewName) {
            $methods .= "\n\n".<<<PHP
    public function render()
    {
        return view('{$viewName}')->layout('aura::components.layout.app');
    }
PHP;
        }

        $stub = File::get(__DIR__.'/Stubs/customize.stub');
        $stub = str_replace(
            ['{{ baseClass }}', '{{ type }}', '{{ class }}', '{{ methods }}'],
            ["Aura\\Base\\Livewire\\Resource\\{$componentType}", $componentType, $componentClass, $methods],
            $stub
        );

        File::ensureDirectoryExists(dirname($target));
        File::put($target, $stub);

        $this->components->info("Created component: {$this->relativePath($target)}");
    }

    protected function customize(string $type, string $mode, string $resourceFile, string $slug, string $baseName): void
    {
        $componentType = ucfirst($type);
        $componentClass = "{$componentType}{$baseName}";
        $viewName = "aura.{$slug}.{$type}";

        if ($mode !== 'component') {
            $this->copyBladeView($type, $slug);
        }

        if ($mode === 'view') {
            $this->injectMethod($resourceFile, "{$type}View", <<<PHP
    public function {$type}View()
    {
        return '{$viewName}';
    }
PHP);

            return;
        }

        $this->createComponent($componentType, $componentClass, $slug, $mode === 'full' ? $viewName : null);

        $this->injectMethod($resourceFile, "{$type}Component", <<<PHP
    public static function {$type}Component(): string
    {
        return \App\Livewire\\{$componentClass}::class;
    }
PHP);
    }

    protected function injectMethod(string $resourceFile, string $methodName, string $methodCode): void
    {
        $content = File::get($resourceFile);

        if (preg_match('/function\s+'.$methodName.'\s*\(/', $content)) {
            $this->components->warn("Method {$methodName}() already exists in {$this->relativePath($resourceFile)}, skipping.");

            return;
        }

        $position = strrpos($content, '}');

        if ($position === false) {
            $this->components->error("Could not find closing brace in {$this->relativePath($resourceFile)}.");

            return;
        }

        $content = substr($content, 0, $position)."\n".$methodCode."\n".substr($content, $position);

        File::put($resourceFile, $content);

        $this->components->info("Added {$methodName}() to {$this->relativePath($resourceFile)}");
    }

    protected function relativePath(string $path): string
    {
        return Str::after($path, base_path().'/');
    }

    protected function resolveMode(): ?string
    {
        $mode = $this->option('mode');

        if ($mode === null) {
            if (! $this->input->isInteractive()) {
                return 'full';
            }

            return select(
                label: 'What would you like to customize?',
                options: [
                    'full' => 'Full: custom component + copied Blade view',
                    'view' => 'View only: copy the Blade view into your project',
                    'component' => 'Component only: custom Livewire class, package view',
                ],
                default: 'full'
            );
        }

        if (! in_array($mode, self::MODES)) {
            $this->components->error('Invalid mode. Allowed: '.implode(', ', self::MODES));

            return null;
        }

        return $mode;
    }

    protected function resolveResource(): ?string
    {
        $resources = collect(app('aura')::getResources())
            ->filter(fn ($resource) => class_exists($resource))
            ->values();

        if ($input = $this->argument('resource')) {
            if (str_contains($input, '\\')) {
                if (! class_exists($input)) {
                    $this->components->error("Resource class {$input} does not exist.");

                    return null;
                }

                return $input;
            }

            $match = $resources->first(function ($resource) use ($input) {
                return strcasecmp(class_basename($resource), $input) === 0
                    || app($resource)->getSlug() === Str::kebab($input);
            });

            if (! $match) {
                $this->components->error("Resource '{$input}' not found. Registered resources: ".$resources->map(fn ($r) => class_basename($r))->implode(', '));
            }

            return $match;
        }

        if ($resources->isEmpty()) {
            $this->components->error('No resources registered.');

            return null;
        }

        return select(
            label: 'Which resource would you like to customize?',
            options: $resources->mapWithKeys(fn ($resource) => [$resource => class_basename($resource)])->toArray(),
            scroll: 10
        );
    }

    protected function resolveTypes(): ?array
    {
        $types = array_map('strtolower', $this->argument('type'));

        if ($types) {
            if ($invalid = array_diff($types, self::TYPES)) {
                $this->components->error('Invalid type(s): '.implode(', ', $invalid).'. Allowed: '.implode(', ', self::TYPES));

                return null;
            }

            return array_values(array_unique($types));
        }

        if (! $this->input->isInteractive()) {
            $this->components->error('Specify at least one page type: '.implode(', ', self::TYPES));

            return null;
        }

        return multiselect(
            label: 'Which pages would you like to customize?',
            options: [
                'index' => 'Index (table)',
                'create' => 'Create',
                'edit' => 'Edit',
                'view' => 'View (detail page)',
            ],
            default: ['view'],
            required: true
        );
    }

    protected function resolveWritableResourceFile(string $resourceClass, string $baseName): ?string
    {
        $file = (new ReflectionClass($resourceClass))->getFileName();

        if (Str::startsWith($file, app_path())) {
            return $file;
        }

        // Package/vendor resource (e.g. User, Team): the overrides have to live in
        // an app-level subclass, since we cannot write into vendor code.
        $subclassPath = app_path("Aura/Resources/{$baseName}.php");

        if (File::exists($subclassPath)) {
            $this->components->info("Using existing app subclass: {$this->relativePath($subclassPath)}");

            return $subclassPath;
        }

        $this->components->warn("{$resourceClass} is a package resource — customizations need an app-level subclass.");

        if (! confirm("Create app/Aura/Resources/{$baseName}.php extending {$resourceClass}?", true)) {
            return null;
        }

        File::ensureDirectoryExists(dirname($subclassPath));
        File::put($subclassPath, <<<PHP
<?php

namespace App\Aura\Resources;

class {$baseName} extends \\{$resourceClass}
{
}

PHP);

        $this->components->info("Created subclass: {$this->relativePath($subclassPath)}");
        $this->updateResourceConfig($resourceClass, "App\\Aura\\Resources\\{$baseName}");

        return $subclassPath;
    }

    protected function updateResourceConfig(string $originalClass, string $subclass): void
    {
        $key = array_search($originalClass, config('aura.resources') ?? [], true);

        if ($key === false) {
            $this->components->warn("Register {$subclass} in place of {$originalClass} wherever the resource is registered.");

            return;
        }

        $configPath = config_path('aura.php');

        if (File::exists($configPath)) {
            $content = File::get($configPath);
            $updated = preg_replace(
                "/('{$key}'\s*=>\s*)[^,\n]+/",
                "$1\\{$subclass}::class",
                $content,
                1
            );

            if ($updated !== null && $updated !== $content) {
                File::put($configPath, $updated);
                $this->components->info("Updated config/aura.php: 'resources.{$key}' now points to {$subclass}.");

                return;
            }
        }

        $this->components->warn("Set config('aura.resources.{$key}') to \\{$subclass}::class to activate the subclass.");
    }
}
