<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use function Laravel\Prompts\text;
use function Laravel\Prompts\select;

class CreateAuraPlugin extends Command
{
    protected $description = 'Create a new Aura plugin';

    protected $signature = 'aura:plugin {name?}';

    public function getStubsDirectory($path)
    {
        return __DIR__.'/../../stubs/'.$path;
    }

    public function handle()
    {
        
        if($this->argument('name')) {

            $vendorAndName = $this->argument('name');

        } else {

            $vendorAndName = text(
                label: 'What is the name of your plugin?',
                placeholder: 'E.g. aura/plugin (vendor/name)',
            );

        }

        [$vendor, $name] = explode('/', $vendorAndName);

        $pluginType = select(
            label: 'What type of plugin do you want to create?',
            options: [
                'plugin' => 'Complete plugin',
                'plugin-resource' => 'Resource plugin',
                'plugin-field' => 'Field plugin',
                'plugin-widget' => 'Widget plugin'
            ],
            default: 'plugin',
        );

    
        $pluginDirectory = base_path("plugins/{$vendor}/{$name}");
        File::makeDirectory($pluginDirectory, 0755, true);

        $stubDirectory = $this->getStubsDirectory($pluginType);

        File::copyDirectory($stubDirectory, $pluginDirectory);

        $this->line("{$pluginType} created at {$pluginDirectory}");

        $this->line('Replacing placeholders...');
        // $this->runProcess("php {$pluginDirectory}/configure.php --vendor={$vendor} --name={$name}");

        $result = Process::path($pluginDirectory)->run("php ./configure.php --vendor={$vendor} --name={$name}");

        $this->line($result->output());

        if ($this->confirm('Do you want to append '.str($name)->title().'ServiceProvider to config/app.php?')) {
            $providerClassName = str($name)->title().'ServiceProvider';
            $configFile = base_path('config/app.php');
            $configContent = File::get($configFile);
            $newProvider = str($vendor)->title().'\\'.str($name)->title()."\\{$providerClassName}::class";
            $configContent = str_replace("App\Providers\AppServiceProvider::class,", "{$newProvider},\n\n        App\Providers\AppServiceProvider::class,", $configContent);
            File::put($configFile, $configContent);
            $this->line("{$providerClassName} added to config/app.php");
        }

        $this->line('Updating composer.json...');
        $composerJsonFile = base_path('composer.json');
        $composerJson = json_decode(File::get($composerJsonFile), true);
        $composerJson['autoload']['psr-4'][ucfirst($vendor).'\\'.ucfirst($name).'\\']
        = "plugins/{$vendor}/{$name}/src";
        File::put($composerJsonFile, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->line('composer.json updated');

        $this->line('composer dump-autoload...');

        Process::run('composer dump-autoload');

        $this->line('Plugin created successfully!');
    }
}
