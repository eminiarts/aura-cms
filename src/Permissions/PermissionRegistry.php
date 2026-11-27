<?php

namespace Aura\Base\Permissions;

use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

final class PermissionRegistry
{
    /** @var array<string, array{name: string, group: string, description: string|null}> */
    private array $permissions = [];

    /** @return array<string, array{name: string, group: string, description: string|null}> */
    public function all(): array
    {
        return $this->permissions;
    }

    /** @param array<string, array{name: string, group: string, description?: string|null}> $permissions */
    public function register(array $permissions): void
    {
        foreach ($permissions as $key => $definition) {
            if (! is_string($key) || $key === '' || preg_match('/[\s*]/', $key)) {
                throw new InvalidArgumentException('Permission keys must be nonempty strings without whitespace or wildcards.');
            }

            if (! is_array($definition)
                || ! is_string($definition['name'] ?? null) || trim($definition['name']) === ''
                || ! is_string($definition['group'] ?? null) || trim($definition['group']) === ''
                || (isset($definition['description']) && ! is_string($definition['description']))) {
                throw new InvalidArgumentException("Permission [{$key}] requires a name, a group and an optional string description.");
            }

            if (isset($this->permissions[$key]) || Gate::has($key)) {
                throw new InvalidArgumentException("Permission [{$key}] is already registered or has an existing Gate.");
            }

            $this->permissions[$key] = [
                'name' => $definition['name'],
                'group' => $definition['group'],
                'description' => $definition['description'] ?? null,
            ];

            Gate::define($key, static function (object $user) use ($key): bool {
                return (method_exists($user, 'hasPermission') && $user->hasPermission($key))
                    || Gate::forUser($user)->allows(User::GLOBAL_ADMIN_GATE);
            });
        }
    }
}
