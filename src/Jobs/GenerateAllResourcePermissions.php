<?php

namespace Aura\Base\Jobs;

use Aura\Base\Resource;
use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Team;
use Illuminate\Support\Facades\DB;
use Aura\Base\Resources\Permission;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

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
            return is_subclass_of($resource, Resource::class) && !is_subclass_of($resource, Team::class);
        });

        DB::transaction(function () use ($resources) {
            foreach ($resources as $resource) {
                $this->generatePermissionsForResource(app($resource));
            }
        });
    }

    private function generatePermissionsForResource(Resource $resource)
    {
        $permissions = [
            'view' => "View {$resource->pluralName()}",
            'viewAny' => "View Any {$resource->pluralName()}",
            'create' => "Create {$resource->pluralName()}",
            'update' => "Update {$resource->pluralName()}",
            'restore' => "Restore {$resource->pluralName()}",
            'delete' => "Delete {$resource->pluralName()}",
            'forceDelete' => "Force Delete {$resource->pluralName()}",
            'scope' => "Scope {$resource->pluralName()}",
        ];

        foreach ($permissions as $action => $name) {
            try {
                Permission::withoutGlobalScopes()->updateOrCreate(
                    [
                        'slug' => "{$action}-{$resource::$slug}",
                        'team_id' => $this->teamId,
                    ],
                    [
                        'name' => $name,
                        'group' => $resource->pluralName(),
                    ]
                );
            } catch (\Illuminate\Database\QueryException $e) {
                // Check if it's a duplicate entry error
                Log::error($e->getMessage());
            }
        }
    }
}
