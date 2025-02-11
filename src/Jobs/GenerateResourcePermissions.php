<?php

namespace Aura\Base\Jobs;

use Aura\Base\Resources\Permission;
use Aura\Base\Resources\resource;
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
     * @var resource
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
