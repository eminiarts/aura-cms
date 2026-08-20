<?php

namespace Aura\Base\Livewire\ComponentSlots;

use Closure;
use ReflectionProperty;

/**
 * Lightweight collision inspector for record-layout panel transports.
 *
 * DEST intentionally does not pin Livewire ~4.3.5, so the full Livewire 4.3
 * internals inspector is not ported. This implementation tracks Aura-owned
 * claims and reads Livewire's Finder classComponents map only.
 */
final class ArrayLivewireCollisionInspector implements LivewireCollisionInspector
{
    private const MAX_OWNED_CLAIMS = 100;

    /** @var array<string, string> */
    private static array $ownedClaims = [];

    public function assertCompatible(): void
    {
        // No Livewire-version hard pin on DEST.
    }

    public function assertOwnedOrReservable(
        string $identifier,
        string $intrinsicComponent,
        Closure $auraResolver,
    ): void {
        $owned = self::$ownedClaims[$identifier] ?? null;
        $existing = $this->existingComponent($identifier);

        if ($owned === $intrinsicComponent) {
            if ($existing !== null && $existing !== $intrinsicComponent) {
                throw new ComponentSlotCollision(
                    "Record layout transport [{$identifier}] collides with [{$existing}]."
                );
            }

            return;
        }

        if ($existing !== null) {
            throw new ComponentSlotCollision(
                "Record layout transport [{$identifier}] collides with [{$existing}]."
            );
        }
    }

    public function assertReservable(
        string $identifier,
        string $intrinsicComponent,
        Closure $auraResolver,
    ): void {
        $existing = $this->existingComponent($identifier);

        if ($existing !== null) {
            throw new ComponentSlotCollision(
                "Record layout transport [{$identifier}] collides with [{$existing}]."
            );
        }
    }

    public function assertUnclaimed(array $identifiers, Closure $auraResolver): void
    {
        foreach ($identifiers as $identifier) {
            if ($this->existingComponent($identifier) !== null) {
                throw new ComponentSlotCollision(
                    "Record layout transport [{$identifier}] is already claimed."
                );
            }
        }
    }

    public function rememberOwnedClaim(string $identifier, string $component): void
    {
        if (! array_key_exists($identifier, self::$ownedClaims)
            && count(self::$ownedClaims) >= self::MAX_OWNED_CLAIMS) {
            array_shift(self::$ownedClaims);
        }

        self::$ownedClaims[$identifier] = $component;
    }

    private function existingComponent(string $identifier): ?string
    {
        if (! app()->bound('livewire.finder')) {
            return null;
        }

        $finder = app('livewire.finder');
        $property = new ReflectionProperty($finder, 'classComponents');
        $property->setAccessible(true);
        $components = $property->getValue($finder);

        if (! is_array($components)) {
            return null;
        }

        $class = $components[$identifier] ?? null;

        return is_string($class) ? $class : null;
    }
}
