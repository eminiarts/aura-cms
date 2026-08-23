<?php

namespace Aura\Base\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

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
    protected $signature = 'aura:resource {name : The name of the resource class, e.g. Article} {--custom : Store the resource in its own database table instead of the shared posts table}';

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
        // Generate where Aura::getAppResources() discovers (config/aura-settings.php).
        return trim(config('aura-settings.paths.resources.namespace', $rootNamespace.'\Aura\Resources'), '\\');
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

        $input = $this->argument('name');

        // Readable names are set explicitly: the slug is kebab-case, and the
        // Resource falls back to Str::title($slug), which would yield "Blog-Post".
        $stub = str_replace('PostSingularName', Str::headline($input), $stub);
        $stub = str_replace('PostPluralName', Str::plural(Str::headline($input)), $stub);
        $stub = str_replace('PostName', ucfirst($input), $stub);
        $stub = str_replace('PostSlug', Str::kebab($input), $stub);
        $stub = str_replace('post_slug', str($input)->snake()->plural(), $stub);

        return $stub;
    }
}
