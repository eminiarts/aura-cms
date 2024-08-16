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

    public $teamId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(?int $teamId = null)
    {
        $this->teamId = $teamId ?? optional(auth()->user())->current_team_id;
    }

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

            // Skip if the resource is Team
            if (is_subclass_of($resource, \Aura\Base\Resources\Team::class) || $resource === \Aura\Base\Resources\Team::class) {
                continue;
            }

            $r = app($resource);

            Permission::firstOrCreate(
                ['slug' => 'view-'.$r::$slug, 'team_id' => $this->teamId],
                [
                    'name' => 'View '.$r->pluralName(),
                    'slug' => 'view-'.$r::$slug,
                    'group' => $r->pluralName(),
                    'team_id' => $this->teamId,
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'viewAny-'.$r::$slug, 'team_id' => $this->teamId],
                [
                    'name' => 'View Any '.$r->pluralName(),
                    'slug' => 'viewAny-'.$r::$slug,
                    'group' => $r->pluralName(),
                    'team_id' => $this->teamId,
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'create-'.$r::$slug, 'team_id' => $this->teamId],
                [
                    'name' => 'Create '.$r->pluralName(),
                    'slug' => 'create-'.$r::$slug,
                    'group' => $r->pluralName(),
                    'team_id' => $this->teamId,
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'update-'.$r::$slug, 'team_id' => $this->teamId],
                [
                    'name' => 'Update '.$r->pluralName(),
                    'slug' => 'update-'.$r::$slug,
                    'group' => $r->pluralName(),
                    'team_id' => $this->teamId,
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'restore-'.$r::$slug, 'team_id' => $this->teamId],
                [
                    'name' => 'Restore '.$r->pluralName(),
                    'slug' => 'restore-'.$r::$slug,
                    'group' => $r->pluralName(),
                    'team_id' => $this->teamId,
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'delete-'.$r::$slug, 'team_id' => $this->teamId],
                [
                    'name' => 'Delete '.$r->pluralName(),
                    'slug' => 'delete-'.$r::$slug,
                    'group' => $r->pluralName(),
                    'team_id' => $this->teamId,
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'forceDelete-'.$r::$slug, 'team_id' => $this->teamId],
                [
                    'name' => 'Force Delete '.$r->pluralName(),
                    'slug' => 'forceDelete-'.$r::$slug,
                    'group' => $r->pluralName(),
                    'team_id' => $this->teamId,
                ]
            );

            Permission::firstOrCreate(
                ['slug' => 'scope-'.$r::$slug, 'team_id' => $this->teamId],
                [
                    'name' => 'Scope '.$r->pluralName(),
                    'slug' => 'scope-'.$r::$slug,
                    'group' => $r->pluralName(),
                    'team_id' => $this->teamId,
                ]
            );
        }
    }
}
