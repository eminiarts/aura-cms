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
    public string $resource;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $r = app($this->resource);

        $permissions = [
            'view' => 'View',
            'viewAny' => 'View Any',
            'create' => 'Create',
            'update' => 'Update',
            'restore' => 'Restore',
            'delete' => 'Delete',
            'forceDelete' => 'Force Delete',
            'scope' => 'Scope',
        ];

        foreach ($permissions as $ability => $label) {
            $attributes = ['slug' => $ability.'-'.$r::$slug];
            $values = [
                'name' => $label.' '.$r->pluralName(),
                'group' => $r->pluralName(),
            ];

            if (config('aura.teams')) {
                Permission::firstOrCreateGlobalForSystem($attributes, $values);
            } else {
                Permission::withoutGlobalScopes()->firstOrCreate($attributes, $values);
            }
        }
    }
}
