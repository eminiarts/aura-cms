<?php

namespace Aura\Base\Resources;

use Aura\Base\Models\Meta;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property string $email
 * @property string $role
 */
class UserInvitation extends Resource
{
    public static $createEnabled = false;

    public static ?string $slug = 'userinvitation';

    public static string $type = 'userinvitation';

    protected static bool $showInNavigation = false;

    public static function getFields(): array
    {
        return [
            ['name' => 'Email', 'slug' => 'email', 'type' => 'Aura\\Base\\Fields\\Email'],
            ['name' => 'Role', 'slug' => 'role', 'type' => 'Aura\\Base\\Fields\\Text'],
        ];
    }

    public function meta(): MorphMany
    {
        return $this->morphMany(Meta::class, 'metable');
    }
}
