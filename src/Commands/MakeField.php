<?php

namespace Eminiarts\Aura\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeField extends GeneratorCommand
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Aura Field';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aura:field {name}';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Field';

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Aura\Fields';
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/Stubs/make-field.stub';
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

        $stub = str_replace('FieldName', ucfirst($this->argument('name')), $stub);
        $stub = str_replace('FieldSlug', str($this->argument('name'))->slug(), $stub);

        return $stub;
    }
}
