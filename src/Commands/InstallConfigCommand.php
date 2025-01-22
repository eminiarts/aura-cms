<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class InstallConfigCommand extends Command
{
    public $description = 'Install Aura Configuration';

    public $signature = 'aura:install-config';

    public function handle(): int
    {
        // 1. Do you want to use teams?
        $useTeams = confirm('Do you want to use teams?');

        // Get the config path
        $configPath = config_path('aura.php');

        // Include the config array
        $config = include $configPath;

        // Modify the 'teams' value
        $config['teams'] = $useTeams;

        // 2. Do you want to modify default features?
        $modifyFeatures = confirm('Do you want to modify the default features?');

        if ($modifyFeatures) {
            // For each feature, ask if they want to enable/disable it
            $features = $config['features'];

            foreach ($features as $feature => $value) {
                $features[$feature] = confirm("Enable feature '{$feature}'?", $value);
            }

            // Update the features in config
            $config['features'] = $features;
        }

        // 3. Do you want to allow registration?
        $allowRegistration = confirm('Do you want to allow registration?');

        // Update the env variable AURA_REGISTRATION
        $this->setEnvValue('AURA_REGISTRATION', $allowRegistration ? 'true' : 'false');

        // 4. Do you want to modify the default theme?
        $modifyTheme = confirm('Do you want to modify the default theme?');

        if ($modifyTheme) {
            $theme = $config['theme'];

            foreach ($theme as $option => $currentValue) {

                if (in_array($option, ['login-bg', 'login-bg-darkmode', 'app-favicon', 'app-favicon-darkmode', 'sidebar-darkmode-type'])) {
                    continue;
                }

                if ($option == 'color-palette') {
                    $choices = ['aura', 'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose', 'mountain-meadow', 'sandal', 'slate', 'gray', 'zinc', 'neutral', 'stone'];
                    $theme[$option] = select(
                        label: "Select value for '{$option}':",
                        options: $choices,
                        default: $currentValue
                    );
                } elseif ($option == 'gray-color-palette') {
                    $choices = ['slate', 'purple-slate', 'gray', 'zinc', 'neutral', 'stone', 'blue', 'smaragd', 'dark-slate', 'blackout'];
                    $theme[$option] = select(
                        label: "Select value for '{$option}':",
                        options: $choices,
                        default: $currentValue
                    );
                } elseif ($option == 'darkmode-type') {
                    $choices = ['auto', 'light', 'dark'];
                    $theme[$option] = select(
                        label: "Select value for '{$option}':",
                        options: $choices,
                        default: $currentValue
                    );
                } elseif ($option == 'sidebar-size') {
                    $choices = ['standard', 'compact'];
                    $theme[$option] = select(
                        label: "Select value for '{$option}':",
                        options: $choices,
                        default: $currentValue
                    );
                } elseif ($option == 'sidebar-type') {
                    $choices = ['primary', 'light', 'dark'];
                    $theme[$option] = select(
                        label: "Select value for '{$option}':",
                        options: $choices,
                        default: $currentValue
                    );
                } elseif (is_bool($currentValue)) {
                    // Boolean option
                    $theme[$option] = confirm("Enable '{$option}'?", $currentValue);
                } else {
                    // For other options, just ask for the value
                    $theme[$option] = text(
                        label: "Enter value for '{$option}':",
                        default: $currentValue
                    );
                }
            }

            // Update the theme in config
            $config['theme'] = $theme;
        }

        // Now, write back the config file
        $arrayExport = var_export($config, true);

        // Remove numeric array keys
        $arrayExport = preg_replace('/[0-9]+ => /', '', $arrayExport);

        $code = '<?php'.PHP_EOL.PHP_EOL.'return '.str_replace(
            ['array (', ')', "[\n    ]"],
            ['[', ']', '[]'],
            $arrayExport
        ).';'.PHP_EOL;

        file_put_contents($configPath, $code);

        $this->info('Aura configuration has been updated.');

        // Run Pint on the file after the file has been written
        $process = new Process(['vendor/bin/pint', $configPath]);
        $process->run();

        // Check if the process was successful
        if (! $process->isSuccessful()) {
            $this->error('Pint formatting failed: '.$process->getErrorOutput());
        } else {
            $this->info('Pint formatting completed.');
        }

        // Cache clear
        $this->info('Clearing cache...');
        $this->call('cache:clear');

        return self::SUCCESS;
    }

    private function setEnvValue($key, $value)
    {
        $envPath = base_path('.env');

        if (file_exists($envPath)) {
            // Read the .env file
            $env = file_get_contents($envPath);

            // Replace the value
            $pattern = '/^'.preg_quote($key, '/').'=.*/m';
            $replacement = $key.'='.$value;

            if (preg_match($pattern, $env)) {
                // Replace existing value
                $env = preg_replace($pattern, $replacement, $env);
            } else {
                // Add new value
                $env .= PHP_EOL.$replacement;
            }

            // Write back to the .env file
            file_put_contents($envPath, $env);
        }
    }
}
