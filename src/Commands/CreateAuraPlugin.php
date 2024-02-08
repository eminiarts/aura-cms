<?php

namespace Eminiarts\Aura\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class CreateAuraPlugin extends Command
{
    protected $description = 'Create a new Aura plugin';

    protected $signature = 'aura:plugin {name}';

    public function getStubsDirectory($path)
    {
        return __DIR__.'/../../stubs/'.$path;
    }

    public function handle()
    {
        $vendorAndName = $this->argument('name');
        [$vendor, $name] = explode('/', $vendorAndName);

        $options = [
            'Complete plugin',
            'Resource plugin',
            'Field plugin',
            'Widget plugin',
        ];

        $pluginType = $this->choice('Select the type of plugin you want to create', $options);

        $folder = [
            'Complete plugin' => 'plugin',
            'Resource plugin' => 'plugin-resource',
            'Field plugin' => 'plugin-field',
            'Widget plugin' => 'plugin-widget',
        ];

        $pluginDirectory = base_path("plugins/{$vendor}/{$name}");
        File::makeDirectory($pluginDirectory, 0755, true);

        $stubDirectory = $this->getStubsDirectory($folder[$pluginType]);

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
