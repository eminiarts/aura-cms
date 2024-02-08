<?php

namespace Aura\Base\Commands;

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

    public function handle()
    {
        parent::handle();

        $this->createViewFile();
        $this->createEditFile();

        $this->info('Field created successfully.');
    }

    protected function buildEditFileContents()
    {
        $contents = $this->files->get(__DIR__.'/Stubs/make-field-edit.stub');

        // replace :fieldSlug with the actual slug
        $contents = str_replace(':fieldSlug', str($this->argument('name'))->slug(), $contents);

        return $contents;
    }

    protected function buildViewFileContents()
    {
        return $this->files->get(__DIR__.'/Stubs/make-field-view.stub');
    }

    protected function createEditFile()
    {
        $name = $this->argument('name');
        $slug = str($name)->slug();

        $path = resource_path('views/components/fields/'.$slug.'.blade.php');

        if (! $this->files->exists(dirname($path))) {
            // create the directory if it doesn't exist
            $this->files->makeDirectory(dirname($path), 0755, true);
        }

        if (! $this->files->exists($path)) {
            $this->files->put($path, $this->buildEditFileContents());
        }
    }

    protected function createViewFile()
    {
        $name = $this->argument('name');
        $slug = str($name)->slug();

        $path = resource_path('views/components/fields/'.$slug.'-view.blade.php');

        if (! $this->files->exists(dirname($path))) {
            // create the directory if it doesn't exist
            $this->files->makeDirectory(dirname($path), 0755, true);
        }

        if (! $this->files->exists($path)) {
            $this->files->put($path, $this->buildViewFileContents());
        }
    }

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
