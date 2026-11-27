<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Jobs\GenerateAllResourcePermissions;
use Aura\Base\Resource;
use Aura\Base\Resources\Permission;

class PermissionNameFilesResource extends Resource
{
    public static $pluralName = 'File library';

    public static ?string $slug = 'permission-name-files';

    public static string $type = 'PermissionNameFiles';

    // The permissions grant access to the files, not to the page.
    public function permissionName()
    {
        return 'Files';
    }
}

beforeEach(fn () => $this->actingAs($this->user = createSuperAdmin()));

test('generated permissions use the resource permission name, also when regenerated', function () {
    Aura::registerResources([PermissionNameFilesResource::class]);
    $teamId = config('aura.teams') ? $this->user->currentTeam->id : null;

    GenerateAllResourcePermissions::dispatchSync($teamId);

    $permission = Permission::withoutGlobalScopes()->where('slug', 'viewAny-permission-name-files')->firstOrFail();

    expect($permission->name)->toBe('View Any Files')
        ->and($permission->group)->toBe('Files')
        ->and($permission->display('name'))->toBe('View all files');
});
