<?php

namespace Aura\Base\Settings;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

final readonly class SettingsPageAuthorizer
{
    public function canUpdate(SettingsPage $page, ?Authenticatable $user): bool
    {
        if ($page->updateAbility !== null) {
            return $user !== null && Gate::forUser($user)->allows($page->updateAbility);
        }

        return $this->isSuperAdmin($user);
    }

    public function canView(SettingsPage $page, ?Authenticatable $user): bool
    {
        if ($page->viewAbility === null && $page->updateAbility === null) {
            return $this->isSuperAdmin($user);
        }

        // Declared abilities decide alone; whoever may update a page may also view it.
        return ($page->viewAbility !== null && $user !== null && Gate::forUser($user)->allows($page->viewAbility))
            || ($page->updateAbility !== null && $this->canUpdate($page, $user));
    }

    private function isSuperAdmin(?Authenticatable $user): bool
    {
        return $user !== null
            && method_exists($user, 'isSuperAdmin')
            && $user->isSuperAdmin();
    }
}
