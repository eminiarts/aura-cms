<?php

namespace Aura\Base\Jobs;

use Aura\Base\Facades\Aura;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resource;
use Aura\Base\Resources\Permission;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateAllResourcePermissions
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    private ?string $connectionName;

    private ?int $teamId;

    public function __construct(?int $teamId = null, ?string $connectionName = null)
    {
        $authenticatedUser = auth()->user();
        $this->connectionName = $connectionName ?? ($authenticatedUser instanceof Model
            ? $authenticatedUser->getConnectionName()
            : null);
        $this->teamId = $teamId;

        if ($this->teamId === null
            && $authenticatedUser instanceof Model
            && User::connectionCacheIdentity($authenticatedUser->getConnection())
                === User::connectionCacheIdentity(DB::connection($this->connectionName))
        ) {
            $this->teamId = $authenticatedUser->getAttribute('current_team_id');
        }
    }

    public function handle(): void
    {
        $resources = collect(Aura::getResources())->filter(function ($resource) {
            try {
                $resourceInstance = app($resource);
                $resourceInstance->setConnection($this->connectionName);

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

        DB::connection($this->connectionName)->transaction(function () use ($resources) {
            foreach ($resources as $resource) {
                $resourceInstance = app($resource);
                $resourceInstance->setConnection($this->connectionName);

                $this->generatePermissionsForResource($resourceInstance);
            }
        });
    }

    private function generatePermissionsForResource(Resource $resource): void
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
                $slug = "{$action}-{$resource::$slug}";
                $values = [
                    'name' => $name,
                    'group' => $resource->pluralName(),
                ];

                if (! config('aura.teams')) {
                    Permission::on($this->connectionName)
                        ->withoutGlobalScopes()
                        ->updateOrCreate(['slug' => $slug], $values);
                } elseif ($this->teamId === null) {
                    Permission::updateOrCreateGlobalForSystem(
                        ['slug' => $slug],
                        $values,
                        DB::connection($this->connectionName),
                    );
                } else {
                    TeamScope::forTeam(
                        $this->teamId,
                        fn () => Permission::on($this->connectionName)->updateOrCreate(
                            ['slug' => $slug, 'team_id' => $this->teamId],
                            $values
                        ),
                    );
                }
            } catch (QueryException $e) {
                // Check if it's a duplicate entry error
                Log::error($e->getMessage());
            }
        }
    }
}
