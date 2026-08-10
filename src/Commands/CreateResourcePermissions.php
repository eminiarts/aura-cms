<?php

namespace Aura\Base\Commands;

use Aura\Base\Facades\Aura;
use Aura\Base\Jobs\GenerateResourcePermissions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class CreateResourcePermissions extends Command
{
    protected $description = 'Create permissions for all resources';

    protected $signature = 'aura:create-resource-permissions';

    public function handle(): void
    {
        foreach (Aura::getResources() as $resource) {
            $r = app($resource);

            $this->info('Creating missing permissions for '.$r->pluralName().'...');

            Auth::loginUsingId(1);

            (new GenerateResourcePermissions($resource))->handle();
        }

        $this->info('Resource permissions created successfully');
    }
}
