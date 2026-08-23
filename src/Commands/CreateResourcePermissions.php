<?php

namespace Aura\Base\Commands;

use Aura\Base\Jobs\GenerateAllResourcePermissions;
use Illuminate\Console\Command;

class CreateResourcePermissions extends Command
{
    protected $description = 'Create permissions for all resources';

    protected $signature = 'aura:create-resource-permissions {--team= : Team ID the permissions belong to. Defaults to the current team of the authenticated user, or none.}';

    public function handle()
    {
        $team = $this->option('team');

        if ($team !== null && ! ctype_digit((string) $team)) {
            $this->error('The --team option must be a numeric team ID.');

            return self::FAILURE;
        }

        GenerateAllResourcePermissions::dispatchSync($team !== null ? (int) $team : null);

        $this->info('Resource permissions created successfully');

        return self::SUCCESS;
    }
}
