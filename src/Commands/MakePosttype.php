<?php

namespace Eminiarts\Aura\Commands;

use Illuminate\Console\GeneratorCommand;

class MakePosttype extends GeneratorCommand
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Aura Posttype';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aura:posttype {name}';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Posttype';

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
        // current dir /Stubs/make-posttype.stub
        return __DIR__.'/Stubs/make-posttype.stub';
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

        $stub = str_replace('PostName', $this->argument('name'), $stub);
        $stub = str_replace('PostSlug', str($this->argument('name'))->slug(), $stub);

        return $stub;
    }
}
