# Global Admins are defined in config and work in a URL-defined area

Global Admins come from `aura.global_admin.emails`, populated by `AURA_GLOBAL_ADMINS`. This reverses the `users.global_admin` flag introduced in PR #48. Database writes and user forms must not grant instance-level access. Reserved email addresses cannot be claimed through registration, invitations, or email changes unless the actor is already a Global Admin. The administrator bootstrap command is an explicit trusted path.

Global Admin work lives under `/{aura.path}/{aura.global_admin.path}`. Route defaults identify the area, and Livewire updates recover it from the checksummed original path. Meeting Metrics' session-based mode switch left its sidebar stale because the navigation cache key did not include the mode. URL-defined areas and separate navigation cache keys remove that switching state. The Global Admin gate still authorizes every global route and action.

## Consequences

- Team navigation and Users queries stay within the current team, including for visiting Global Admins. The global Users index includes users without Memberships.
- Any HTTP route without an `aura_area` default counts as the team area, host-app routes included. A host route that needs the cross-team Users bypass opts in with `->defaults('aura_area', 'global')` behind the Global Admin gate.
- Global navigation is independent of the current team and works in Teams-off mode.
- New installs have no `global_admin` column. Existing columns remain inert. No destructive migration is introduced, following ADR 0002's ownership rule.
- `aura:user --global-admin` appends the email to `.env`. Production must configure `AURA_GLOBAL_ADMINS` explicitly. Host apps may still override the gate.
