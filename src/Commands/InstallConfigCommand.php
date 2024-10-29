<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;

class InstallConfigCommand extends Command
{
    public $description = 'Install Aura Configuration';

    public $signature = 'aura:install-config';

    public function handle(): int
    {
        // Here we want to ask:
        // - Do you want to use teams?
        // If yes, add env AURA_TEAMS=true
        // If no, add env AURA_TEAMS=false
        // - Do you want to modify theme defaults?
        // If yes, add env AURA_THEME_DEFAULTS=true
        // If no, add env AURA_THEME_DEFAULTS=false

        return self::SUCCESS;
    }
}
