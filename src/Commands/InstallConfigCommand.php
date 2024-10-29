<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\ask;
use function Laravel\Prompts\choice;
use function Laravel\Prompts\confirm;

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
                if ($option == 'color-palette') {
                    $choices = ['aura', 'other1', 'other2'];
                    $theme[$option] = choice("Select value for '{$option}':", $choices, $currentValue);
                } elseif ($option == 'gray-color-palette') {
                    $choices = ['slate', 'gray', 'cool'];
                    $theme[$option] = choice("Select value for '{$option}':", $choices, $currentValue);
                } elseif ($option == 'darkmode-type') {
                    $choices = ['auto', 'manual'];
                    $theme[$option] = choice("Select value for '{$option}':", $choices, $currentValue);
                } elseif (is_bool($currentValue)) {
                    // Boolean option
                    $theme[$option] = confirm("Enable '{$option}'?", $currentValue);
                } else {
                    // For other options, just ask for the value
                    $theme[$option] = ask("Enter value for '{$option}':", $currentValue);
                }
            }

            // Update the theme in config
            $config['theme'] = $theme;
        }

        // Now, write back the config file
        $code = '<?php' . PHP_EOL . PHP_EOL . 'return ' . var_export($config, true) . ';' . PHP_EOL;
        file_put_contents($configPath, $code);

        $this->info('Aura configuration has been updated.');

        return self::SUCCESS;
    }

    private function setEnvValue($key, $value)
    {
        $envPath = base_path('.env');

        if (file_exists($envPath)) {
            // Read the .env file
            $env = file_get_contents($envPath);

            // Replace the value
            $pattern = '/^' . preg_quote($key, '/') . '=.*/m';
            $replacement = $key . '=' . $value;

            if (preg_match($pattern, $env)) {
                // Replace existing value
                $env = preg_replace($pattern, $replacement, $env);
            } else {
                // Add new value
                $env .= PHP_EOL . $replacement;
            }

            // Write back to the .env file
            file_put_contents($envPath, $env);
        }
    }
}
