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

        dd($vendor, $name, $pluginType);

        $pluginDirectory = app_path("Aura/Plugins/{$vendor}/{$name}");

        File::makeDirectory($pluginDirectory, 0755, true);

        $composerJson = [
            'name' => "{$vendor}/{$name}",
            'description' => '',
            'type' => 'laravel-package',
            'license' => 'MIT',
            'authors' => [
                [
                    'name' => '',
                    'email' => '',
                ],
            ],
            'autoload' => [
                'psr-4' => [
                    "{$vendor}\\{$name}\\" => "src/",
                ],
            ],
            'require' => [
                'illuminate/support' => '^8.0',
            ],
            'extra' => [
                'laravel' => [
                    'providers' => [
                        "{$vendor}\\{$name}\\{$name}ServiceProvider",
                    ],
                ],
            ],
        ];

        File::put("{$pluginDirectory}/composer.json", json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $providerClassName = "{$name}ServiceProvider";
        $providerStub = File::get(__DIR__."/stubs/{$pluginType}/ServiceProvider.stub");
        $providerContent = str_replace('{{providerClassName}}', $providerClassName, $providerStub);
        File::put("{$pluginDirectory}/{$providerClassName}.php", $providerContent);

        $this->info("{$pluginType} plugin created at {$pluginDirectory}");

        if ($this->confirm("Do you want to append {$providerClassName} to config/app.php?")) {
            $configFile = base_path('config/app.php');
            $configContent = File::get($configFile);
            $newProvider = "{$vendor}\\{$name}\\{$name}ServiceProvider::class";
            $configContent = str_replace("/* providers */", "/* providers */\n        {$newProvider},", $configContent);
            File::put($configFile, $configContent);
            $this->info("{$providerClassName} added to config/app.php");
        }
    }
}