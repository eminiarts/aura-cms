<?php

namespace Aura\Base\Jobs;

use Aura\Base\Resource;
use Aura\Base\Resources\Permission;
use Aura\Base\Resources\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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

    private string $connectionIdentity;

    private string $connectionName;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $resource, ?string $connectionName = null)
    {
        $this->resource = $resource;

        /** @var resource $resourceInstance */
        $resourceInstance = app($resource);
        $authenticatedUser = auth()->user();

        if ($connectionName !== null) {
            $connection = DB::connection($connectionName);
        } elseif ($resourceInstance->getConnectionName() !== null) {
            $connection = $resourceInstance->getConnection();
        } elseif ($authenticatedUser instanceof Model) {
            $connection = $authenticatedUser->getConnection();
        } else {
            $connection = $resourceInstance->getConnection();
        }

        $this->connectionName = (string) $connection->getName();
        $this->connectionIdentity = User::connectionCacheIdentity($connection);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $connection = DB::connection($this->connectionName);

        if (User::connectionCacheIdentity($connection) !== $this->connectionIdentity) {
            throw new RuntimeException('The database connection identity changed after this permission-generation job was dispatched.');
        }

        /** @var resource $r */
        $r = clone app($this->resource);
        $r->setConnection($this->connectionName);

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
            $attributes = ['slug' => $ability.'-'.$r->getSlug()];
            $values = [
                'name' => $label.' '.$r->pluralName(),
                'group' => $r->pluralName(),
            ];

            if (config('aura.teams')) {
                Permission::firstOrCreateGlobalForSystem($attributes, $values, $connection);
            } else {
                Permission::on($this->connectionName)
                    ->withoutGlobalScopes()
                    ->firstOrCreate($attributes, $values);
            }
        }
    }
}
