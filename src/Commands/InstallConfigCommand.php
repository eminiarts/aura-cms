<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;

class InstallConfigCommand extends Command
{
    public $description = 'Install Aura Config Options';

    public $signature = 'aura:install-config';

    public function handle(): int
    {
        $installMultitenancy = confirm('Do you want to install multitenancy?', default: true);
        $keepPostsExample = confirm('Do you want to keep posts as an example?', default: true);
        $installPlugins = confirm('Do you want to install plugins?', default: true);

        if ($installMultitenancy) {
            $this->info('Multitenancy will be installed.');
            // Add logic to install multitenancy
        } else {
            $this->info('Multitenancy will not be installed.');
        }

        if ($keepPostsExample) {
            $this->info('Posts example will be kept.');
            // Add logic to keep posts example
        } else {
            $this->info('Posts example will be removed.');
            // Add logic to remove posts example
        }

        if ($installPlugins) {
            $this->info('Plugins will be installed.');
            // Add logic to install plugins
        } else {
            $this->info('Plugins will not be installed.');
        }

        return self::SUCCESS;
    }
}
