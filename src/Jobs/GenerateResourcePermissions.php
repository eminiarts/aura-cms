<?php

namespace Aura\Base\Jobs;

use Aura\Base\Resource;
use Aura\Base\Resources\Permission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateResourcePermissions implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The resource to generate the permissions for.
     *
     * @var class-string<\Aura\Base\Resource>
     */
    public $resource;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $r = app($this->resource);
        $name = $r->permissionName();

        Permission::firstOrCreate(
            ['slug' => 'view-'.$r::$slug],
            [
                'name' => 'View '.$name,
                'slug' => 'view-'.$r::$slug,
                'group' => $name,
            ]
        );

        Permission::firstOrCreate(
            ['slug' => 'viewAny-'.$r::$slug],
            [
                'name' => 'View Any '.$name,
                'slug' => 'viewAny-'.$r::$slug,
                'group' => $name,
            ]
        );

        Permission::firstOrCreate(
            ['slug' => 'create-'.$r::$slug],
            [
                'name' => 'Create '.$name,
                'slug' => 'create-'.$r::$slug,
                'group' => $name,
            ]
        );

        Permission::firstOrCreate(
            ['slug' => 'update-'.$r::$slug],
            [
                'name' => 'Update '.$name,
                'slug' => 'update-'.$r::$slug,
                'group' => $name,
            ]
        );

        Permission::firstOrCreate(
            ['slug' => 'restore-'.$r::$slug],
            [
                'name' => 'Restore '.$name,
                'slug' => 'restore-'.$r::$slug,
                'group' => $name,
            ]
        );

        Permission::firstOrCreate(
            ['slug' => 'delete-'.$r::$slug],
            [
                'name' => 'Delete '.$name,
                'slug' => 'delete-'.$r::$slug,
                'group' => $name,
            ]
        );

        Permission::firstOrCreate(
            ['slug' => 'forceDelete-'.$r::$slug],
            [
                'name' => 'Force Delete '.$name,
                'slug' => 'forceDelete-'.$r::$slug,
                'group' => $name,
            ]
        );

        Permission::firstOrCreate(
            ['slug' => 'scope-'.$r::$slug],
            [
                'name' => 'Scope '.$name,
                'slug' => 'scope-'.$r::$slug,
                'group' => $name,
            ]
        );
    }
}
