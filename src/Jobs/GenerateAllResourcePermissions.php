<?php

namespace Aura\Base\Jobs;

use Aura\Base\Facades\Aura;
use Aura\Base\Resource;
use Aura\Base\Resources\Permission;
use Aura\Base\Resources\Team;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateAllResourcePermissions
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    private $teamId;

    public function __construct(?int $teamId = null)
    {
        $this->teamId = $teamId ?? auth()->user()?->current_team_id;
    }

    public function handle()
    {
        $resources = collect(Aura::getResources())->filter(function ($resource) {
            try {
                $resourceInstance = app($resource);

                return is_subclass_of($resourceInstance, Resource::class) &&
                       ! is_a($resourceInstance, Team::class) &&
                       ! is_subclass_of($resourceInstance, Team::class);
            } catch (\Throwable $e) {
                if (! app()->environment('testing')) {
                    Log::warning("Resource class not found: $resource");
                }

                return false;
            }
        });

        DB::transaction(function () use ($resources) {
            foreach ($resources as $resource) {
                $this->generatePermissionsForResource(app($resource));
            }
        });
    }

    private function generatePermissionsForResource(Resource $resource)
    {
        // The resource's name in permissions (permissionName(), the plural name unless the resource says
        // otherwise, as GenerateResourcePermissions does): a media plugin grants "View all media files".
        $name = $resource->permissionName();

        $permissions = [
            'view' => "View {$name}",
            'viewAny' => "View Any {$name}",
            'create' => "Create {$name}",
            'update' => "Update {$name}",
            'restore' => "Restore {$name}",
            'delete' => "Delete {$name}",
            'forceDelete' => "Force Delete {$name}",
            'scope' => "Scope {$name}",
        ];

        foreach ($permissions as $action => $label) {
            $identity = ['slug' => "{$action}-{$resource::$slug}"];

            // The permissions table only has a team_id column when teams are on.
            if (config('aura.teams')) {
                $identity['team_id'] = $this->teamId;
            }

            try {
                Permission::withoutGlobalScopes()->updateOrCreate(
                    $identity,
                    [
                        'name' => $label,
                        'group' => $name,
                    ]
                );
            } catch (QueryException $e) {
                // Check if it's a duplicate entry error
                Log::error($e->getMessage());
            }
        }
    }
}
