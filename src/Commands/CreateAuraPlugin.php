<?php

namespace Eminiarts\Aura\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreateAuraPlugin extends Command
{
    protected $signature = 'aura:plugin {name}';
    protected $description = 'Create a new Aura plugin';

    public function handle()
    {
        $vendorAndName = $this->argument('name');
        [$vendor, $name] = explode('/', $vendorAndName);

        $options = [
            'Complete plugin',
            'Posttype plugin',
            'Field plugin',
            'Widget plugin',
        ];

        $pluginType = $this->choice('Select the type of plugin you want to create', $options);

        $folder = [
            'Complete plugin' => 'plugin',
            'Posttype plugin' => 'plugin-posttype',
            'Field plugin' => 'plugin-field',
            'Widget plugin' => 'plugin-widget',
        ];

        $pluginDirectory = app_path("Aura/Plugins/{$vendor}/{$name}");
        File::makeDirectory($pluginDirectory, 0755, true);

        $stubDirectory = $this->getStubsDirectory($folder[$pluginType]);
        
        File::copyDirectory($stubDirectory, $pluginDirectory);

        $this->info("{$pluginType} plugin created at {$pluginDirectory}");

        dd('stop', $pluginDirectory, $stubDirectory, $vendor, $name);

        $this->info("Replacing placeholders...");
        $this->runProcess("php {$pluginDirectory}/configure.php --vendor={$vendor} --name={$name}");

        if ($this->confirm("Do you want to append {$name}ServiceProvider to config/app.php?")) {
            $providerClassName = "{$name}ServiceProvider";
            $configFile = base_path('config/app.php');
            $configContent = File::get($configFile);
            $newProvider = "{$vendor}\\{$name}\\{$providerClassName}::class";
            $configContent = str_replace("/* providers */", "/* providers */\n        {$newProvider},", $configContent);
            File::put($configFile, $configContent);
            $this->info("{$providerClassName} added to config/app.php");
        }
    }

    public function getStubsDirectory($path)
    {
        return __DIR__.'/../../stubs/' . $path;
    }

    private function runProcess($command)
    {
        $process = proc_open($command, [
            0 => STDIN,
            1 => STDOUT,
            2 => STDERR,
        ], $pipes);

        if (is_resource($process)) {
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            return proc_close($process);
        }

        return -1;
    }
}