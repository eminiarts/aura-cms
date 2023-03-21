<?php

namespace Eminiarts\Aura\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Eminiarts\Aura\Facades\Aura;
use Illuminate\Support\Facades\Auth;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Eminiarts\Aura\Resources\Permission;

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
                'title' => 'View '.$r->pluralName(),
                'name' => 'View '.$r->pluralName(),
                'slug' => 'view-'.$r::$slug,
                'group' => $r->pluralName(),
            ]
            );

            Permission::firstOrCreate(
                ['slug' => 'viewAny-'.$r::$slug],
                [
                'title' => 'View Any '.$r->pluralName(),
                'name' => 'View Any '.$r->pluralName(),
                'slug' => 'viewAny-'.$r::$slug,
                'group' => $r->pluralName(),
            ]
            );

            Permission::firstOrCreate(
                ['slug' => 'create-'.$r::$slug],
                [
                'title' => 'Create '.$r->pluralName(),
                'name' => 'Create '.$r->pluralName(),
                'slug' => 'create-'.$r::$slug,
                'group' => $r->pluralName(),
            ]
            );

            Permission::firstOrCreate(
                ['slug' => 'update-'.$r::$slug],
                [
                'title' => 'Update '.$r->pluralName(),
                'name' => 'Update '.$r->pluralName(),
                'slug' => 'update-'.$r::$slug,
                'group' => $r->pluralName(),
            ]
            );

            Permission::firstOrCreate(
                ['slug' => 'restore-'.$r::$slug],
                [
                'title' => 'Restore '.$r->pluralName(),
                'name' => 'Restore '.$r->pluralName(),
                'slug' => 'restore-'.$r::$slug,
                'group' => $r->pluralName(),
            ]
            );

            Permission::firstOrCreate(
                ['slug' => 'delete-'.$r::$slug],
                [
                'title' => 'Delete '.$r->pluralName(),
                'name' => 'Delete '.$r->pluralName(),
                'slug' => 'delete-'.$r::$slug,
                'group' => $r->pluralName(),
            ]
            );

            Permission::firstOrCreate(
                ['slug' => 'forceDelete-'.$r::$slug],
                [
                'title' => 'Force Delete '.$r->pluralName(),
                'name' => 'Force Delete '.$r->pluralName(),
                'slug' => 'forceDelete-'.$r::$slug,
                'group' => $r->pluralName(),
            ]
            );

            Permission::firstOrCreate(
                ['slug' => 'scope-'.$r::$slug],
                [
                'title' => 'Scope '.$r->pluralName(),
                'name' => 'Scope '.$r->pluralName(),
                'slug' => 'scope-'.$r::$slug,
                'group' => $r->pluralName(),
            ]
            );
        }

        $this->info("Resource permissions created successfully");
    }
}
