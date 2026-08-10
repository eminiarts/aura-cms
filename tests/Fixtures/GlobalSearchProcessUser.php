<?php

namespace Aura\Base\Tests\Fixtures;

use Aura\Base\Resources\User;

final class GlobalSearchProcessUser extends User
{
    public static $globalSearch = false;

    public function getOptionBookmarks(): array
    {
        return [];
    }
}
