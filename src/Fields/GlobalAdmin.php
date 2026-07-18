<?php

namespace Aura\Base\Fields;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * The Global Admin flag field.
 *
 * `global_admin` is a real users-table column but is deliberately kept out of
 * the mass-assignment surface: the value only ever reaches the column through
 * this guarded write. Mirroring the escalation guard on the Roles field, the
 * flag can only be changed by an acting user who is already a Global Admin.
 * Every other actor — a guest during registration, a team Super Admin, mass
 * assignment, or form tampering — leaves the persisted flag untouched (silent
 * refusal), so the flag can never be self-granted.
 */
class GlobalAdmin extends Boolean
{
    public function saved($post, $field, $value)
    {
        $new = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        $current = (bool) $post->getOriginal('global_admin');

        // No change requested (e.g. a non-GA re-submitting a hidden field at its
        // current value): nothing to guard, nothing to write.
        if ($new === $current) {
            return;
        }

        // Only a Global Admin may change the flag. Anyone else is silently
        // refused: the persisted value stays as it was, so the escalation
        // attempt changes nothing.
        if (! (Auth::check() && Gate::allows('AuraGlobalAdmin'))) {
            return;
        }

        // Trusted write. saveQuietly bypasses the field pipeline (no recursion,
        // no re-guarding) and persists the column directly.
        $post->forceFill(['global_admin' => $new])->saveQuietly();
    }
}
