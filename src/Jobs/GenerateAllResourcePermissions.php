<?php

namespace Aura\Base\Jobs;

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Permission;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateAllResourcePermissions
{
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach (Aura::getResources() as $resource) {

            // Skip if the resource is not a Aura Resource
            if (! is_subclass_of($resource, \Aura\Base\Resource::class)) {
                continue;
            }

            $r = app($resource);

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
    }
}
