<?php

namespace Aura\Base\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Artisan;

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
    protected $signature = 'aura:resource {name} {--custom} {--dynamic} {--no-migration}';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Resource';

    /**
     * Generate a migration for the resource.
     *
     * @return void
     */
    protected function generateMigration()
    {
        $tableName = str($this->argument('name'))->snake()->plural()->toString();

        $this->info("Creating migration for table: {$tableName}");

        Artisan::call('make:migration', [
            'name' => "create_{$tableName}_table",
            '--create' => $tableName,
        ]);

        $this->info(Artisan::output());
        $this->info('Remember to define your table schema in the migration file.');
        $this->info("Tip: After defining fields in your resource, run: php artisan aura:create-resource-migration {$this->getDefaultNamespace($this->rootNamespace())}\\{$this->argument('name')}");
    }

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
        if ($this->shouldUseCustomTable()) {
            return __DIR__.'/Stubs/make-custom-resource.stub';
        }

        return __DIR__.'/Stubs/make-resource.stub';
    }

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        $result = parent::handle();

        // Generate migration for custom table resources (unless --no-migration flag is set)
        if ($this->shouldUseCustomTable() && ! $this->option('no-migration')) {
            $this->generateMigration();
        }

        return $result;
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

    /**
     * Determine if the resource should use a custom table.
     *
     * @return bool
     */
    protected function shouldUseCustomTable()
    {
        // --dynamic explicitly requests EAV (posts/meta) pattern
        if ($this->option('dynamic')) {
            return false;
        }

        // --custom explicitly requests custom table
        if ($this->option('custom')) {
            return true;
        }

        // Check config for default behavior
        return config('aura.features.custom_tables_for_resources', false);
    }
}
