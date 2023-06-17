<?php

namespace Eminiarts\Aura\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeTaxonomy extends GeneratorCommand
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Aura Taxonomy';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aura:taxonomy {name}';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Taxonomy';

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Aura\Taxonomies';
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/Stubs/make-taxonomy.stub';
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

        $stub = str_replace('TaxonomyName', ucfirst($this->argument('name')), $stub);
        $stub = str_replace('TaxonomySlug', str($this->argument('name'))->slug(), $stub);

        return $stub;
    }
}
