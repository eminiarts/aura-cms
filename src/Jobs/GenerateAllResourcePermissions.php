<?php

namespace Eminiarts\Aura\Jobs;

use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Resources\Permission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateAllResourcePermissions implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
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
            if (! is_subclass_of($resource, \Eminiarts\Aura\Resource::class)) {
                continue;
            }

            $r = app($resource);

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
    }
}
