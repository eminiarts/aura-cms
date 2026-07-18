# Role identity is its slug, and Shadowing resolves at check time

Roles in the catalog are either **Global Roles** (`roles.team_id = null`, defined
once for the instance) or **Team Roles** (`roles.team_id` set, defined by one
team). A team **Shadows** a Global Role by creating a Team Role with the same
slug. We resolve which definition applies **by slug, at permission-check time**,
rather than by rewriting Membership pivot rows when a Shadow is created or
deleted.

A Membership (`user_role`: `user_id`, `role_id`, `team_id`) records the team a
user belongs to and the role slug they hold there. When we evaluate that
Membership we resolve the slug within the team: if the team owns a role with that
slug (a Shadow), it wins; otherwise the Global Role with that slug applies. In
Teams-off mode there is a single flat catalog, so the Global Role simply is the
role. All of this funnels through one seam — `Role::resolveForTeam(string $slug,
?int $teamId)` — with `User::cachedRoles()` memoizing the resolved set per team
context and invalidating via a catalog version that bumps on every role write,
so a Shadow created or deleted mid-request still takes effect immediately.

We considered the alternative of **re-pointing Membership rows** when a Shadow is
created (rewrite `user_role.role_id` to the Team Role) and again when it is
deleted (rewrite back to the Global Role). We rejected it: it makes creating or
deleting a role a bulk write across every affected Membership, it races with
concurrent Membership changes, it loses the distinction between "assigned the
catalog role" and "assigned a specific row", and a half-applied rewrite leaves
permissions in an inconsistent state. Treating the slug as the role's identity
keeps Membership rows immutable and makes catalog edits O(1) and atomic.

## Consequences

- The slug is a role's identity **within a team context**. `unique(slug, team_id)`
  (Teams-on) and `unique(slug)` (Teams-off) enforce one definition per scope, and
  a Team Role may deliberately share a slug with a Global Role — that is a Shadow,
  not a conflict.
- Creating or deleting a Shadow changes permission outcomes instantly for every
  member holding that slug, with no data migration and no pivot rewrite.
- Permission checks (`hasPermissionTo`, `hasPermission`, `isSuperAdmin`,
  `hasRole`, `hasAnyRole`) all read through `User::cachedRoles()`, which resolves
  each Membership through the seam. Resolution deliberately bypasses `TeamScope`
  (via `withoutGlobalScopes`) so a Global Role is visible even though it carries
  no `team_id`.
- Permission sets are normalized in exactly one place (`User::normalizePermissions`),
  so a set persisted as a cast array or as a JSON string behaves identically. The
  previous ad-hoc `json_decode` "temporary fix" in `hasPermissionTo` is removed.
- The base catalog (`admin` Super Admin + `user`) is seeded by
  `Aura\Base\Database\Seeders\RoleCatalogSeeder`, invoked from the install command
  after migrations, in both Teams-on and Teams-off mode. Tests that need the
  catalog invoke the same seeder, so they exercise the real seeding path.
- The merged, de-duplicated Roles index and the role pickers that present the
  resolved catalog in the UI are out of scope here (tracked separately); this ADR
  covers only identity and check-time resolution.
- Moving existing per-team admin rows onto the shared global `admin` role ships as
  a best-effort consolidation migration — a deliberate, narrow exception to ADR
  0003's "no upgrade machinery" stance, justified by the pre-release posture and
  decided in the parent spec (#45).
