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
