<?php

namespace Aura\Base\Fields;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * The Global Admin flag field.
 *
 * `global_admin` is a real users-table column but is deliberately kept out of
 * the mass-assignment surface: the value only ever reaches the column through
 * this guarded write. The flag can only be changed by an acting user who is
 * already a Global Admin; every other actor — a guest during registration, a
 * team Super Admin, mass assignment, or form tampering — leaves the persisted
 * flag untouched, so the flag can never be self-granted.
 *
 * The refusal here is a SILENT no-op, not the 403 abort the Roles field's
 * escalation guard raises — a deliberate difference, not an inconsistency. This
 * field rides the ordinary create/update save pipeline (registration, the user
 * form), where the flag defaults into every submitted payload and a hidden or
 * tampered value must simply be ignored: aborting would strand a half-created
 * user or fail a legitimate edit that never intended to touch the flag. The
 * Roles field, by contrast, guards an explicit, deliberate role assignment, so
 * a 403 is the correct, visible answer there. Same goal (no privilege
 * escalation), different failure mode fitted to each write path.
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
