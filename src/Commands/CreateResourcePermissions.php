<?php

namespace Aura\Base\Commands;

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Permission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class CreateResourcePermissions extends Command
{
    protected $description = 'Create permissions for all resources';

    protected $signature = 'aura:create-resource-permissions';

    public function handle()
    {
        // Permissions
        foreach (Aura::getResources() as $resource) {
            $r = app($resource);

            $this->info('Creating missing permissions for '.$r->pluralName().'...');

            // login user 1
            Auth::loginUsingId(1);

            Permission::firstOrCreate(
                ['slug' => 'view-'.$r::$slug],
                [
                    'name' => 'View '.$r->pluralName(),
                    'slug' => 'view-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'viewAny-'.$r::$slug],
                [
                    'name' => 'View Any '.$r->pluralName(),
                    'slug' => 'viewAny-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'create-'.$r::$slug],
                [
                    'name' => 'Create '.$r->pluralName(),
                    'slug' => 'create-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'update-'.$r::$slug],
                [
                    'name' => 'Update '.$r->pluralName(),
                    'slug' => 'update-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'restore-'.$r::$slug],
                [
                    'name' => 'Restore '.$r->pluralName(),
                    'slug' => 'restore-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'delete-'.$r::$slug],
                [
                    'name' => 'Delete '.$r->pluralName(),
                    'slug' => 'delete-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'forceDelete-'.$r::$slug],
                [
                    'name' => 'Force Delete '.$r->pluralName(),
                    'slug' => 'forceDelete-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'scope-'.$r::$slug],
                [
                    'name' => 'Scope '.$r->pluralName(),
                    'slug' => 'scope-'.$r::$slug,
                    'group' => $r->pluralName(),
                ]
            );
        }

        $this->info('Resource permissions created successfully');
    }
}
