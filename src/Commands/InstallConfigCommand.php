<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class InstallConfigCommand extends Command
{
    public $description = 'Install Aura Configuration';

    public $signature = 'aura:install-config';

    public function handle(): int
    {
        // Teams configuration
        $useTeams = confirm('Do you want to use teams?', true);
        $this->updateConfig('aura.teams', $useTeams);

        // Features configuration
        $modifyFeatures = confirm('Do you want to modify default features?', false);
        if ($modifyFeatures) {
            $features = config('aura.features');
            foreach ($features as $feature => $enabled) {
                $features[$feature] = confirm("Enable {$feature}?", $enabled);
            }
            $this->updateConfig('aura.features', $features);
        }

        // Registration configuration
        $allowRegistration = confirm('Do you want to allow registration?', true);
        $this->updateEnv('AURA_REGISTRATION', $allowRegistration ? 'true' : 'false');

        // Theme configuration
        $modifyTheme = confirm('Do you want to modify the default theme?', false);
        if ($modifyTheme) {
            $theme = config('aura.theme');
            
            $theme['color-palette'] = select(
                'Select color palette:',
                ['aura', 'blue', 'red', 'green', 'yellow', 'purple', 'pink', 'indigo']
            );
            
            $theme['gray-color-palette'] = select(
                'Select gray color palette:',
                ['slate', 'gray', 'zinc', 'neutral', 'stone']
            );
            
            $theme['darkmode-type'] = select(
                'Select darkmode type:',
                ['auto', 'light', 'dark']
            );
            
            $theme['sidebar-size'] = select(
                'Select sidebar size:',
                ['standard', 'compact', 'expanded']
            );
            
            $theme['sidebar-type'] = select(
                'Select sidebar type:',
                ['primary', 'secondary', 'transparent']
            );

            $this->updateConfig('aura.theme', $theme);
        }

        $this->info('Aura configuration has been updated successfully!');

        return self::SUCCESS;
    }

    protected function updateConfig(string $key, $value): void
    {
        Config::set($key, $value);
        $this->info("Updated config: {$key}");
    }

    protected function updateEnv(string $key, string $value): void
    {
        $path = base_path('.env');
        
        if (File::exists($path)) {
            $content = File::get($path);
            
            if (strpos($content, $key) !== false) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}\n";
            }
            
            File::put($path, $content);
            $this->info("Updated .env: {$key}={$value}");
        }
    }
}
