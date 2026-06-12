# Aura CMS — Remediation Plan (Security Track)

> Companion to [`FINDINGS.md`](./FINDINGS.md) and the general plan [`REMEDIATION_PLAN.md`](./REMEDIATION_PLAN.md). This document isolates the **security-sensitive** workstreams (the SEC-* findings) so they can be reviewed, prioritized, and — where appropriate — disclosed independently of the general engineering work.
>
> These findings are **code-confirmed but not exploit-tested**. Before fixing, each should be reproduced into a concrete failing test (the fix then makes it pass). Treat specifics here as defensive remediation notes for the maintainer of this repository.

## How this track relates to the general plan

- **PR-1 (Make CI trustworthy)** in the general plan is the shared gate. Land it first so security fixes get a trustworthy parallel + fresh-install CI run.
- **PR-6 (cross-team isolation suite)** in the general plan is the regression net for PR-3 and PR-4 below. Coordinate: isolation assertions added there protect these fixes from silent regression.
- Branch naming and the per-PR execution checklist follow the general plan's conventions.

## Priority order within this track

```
PR-1 (general, CI gate) ──► PR-2 (RCE, critical) ──► PR-3 (tenant cache, critical)
                                                  ──► PR-4 (authz + mass assignment, high)
                                                  ──► PR-7 (XSS/uploads/API, mixed)
                                                  ──► PR-8 (codegen safety, high)
```

PR-2 and PR-3 are the two **criticals** and should land first. PR-8 depends on PR-4 (it reuses the authorization added there).

---

## PR-2 — Neutralize the plugin RCE  `SEC-01`
- **Branch:** `fix/plugins-rce`
- **Finding:** `src/Livewire/PluginsPage.php` exposes public Livewire methods that run shell commands built from user input, with hardcoded developer paths (`/opt/homebrew/bin/php`, `/usr/local/bin/composer`). Any client able to render the component can invoke them.
- **Goal:** remove arbitrary shell execution reachable from a remote endpoint.
- **Approach (default = removal):** delete the `runComposerUpdate()` / `updatePackage()` shell paths. Running `composer` at runtime is not a CMS responsibility and the feature only ever worked on one machine. If a managed-update flow is genuinely wanted later, it belongs behind an explicit, off-by-default, local-only + global-admin gate with fully escaped arguments and no hardcoded paths — out of scope for the fix.
- **Tests:** assert the dangerous methods no longer exist / are not remotely invokable; component still renders.
- **Risk:** low (feature was broken/non-portable anyway).
- **Depends on:** PR-1.

---

## PR-3 — Fix tenant cache invalidation  `SEC-02`
- **Branch:** `fix/team-cache-invalidation`
- **Finding:** `TeamScope` resolves the active team via `Cache::rememberForever("user_{id}_current_team_id")`, but `User::switchTeam()` never forgets that key — and no such forget exists in `src/`. After switching teams, queries stay scoped to the previous team indefinitely. `tests/Pest.php` already works around this by manually forgetting the key.
- **Goal:** switching teams must immediately re-scope queries.
- **Approach:** forget the cache key wherever `current_team_id` changes (in `switchTeam()`, and ideally via a `User::saved` hook so every path is covered). Consider a bounded TTL as defense-in-depth instead of `rememberForever`.
- **Tests:** create two teams + data, switch, assert queries no longer return the old team's rows and the cache key is cleared. This is the linchpin isolation test (coordinate with general PR-6).
- **Risk:** low; high value (correctness + tenant isolation).
- **Depends on:** PR-1.

---

## PR-4 — Authorization & mass-assignment hardening  `SEC-03, SEC-04`
- **Branch:** `fix/authz-mass-assignment`
- **Findings:**
  - `SEC-04` — remotely-callable Livewire methods without `authorize()`: `Table::updateCardStatus`, `BulkActions::bulkAction` (client-supplied method name), mutating methods on `ResourceEditor`.
  - `SEC-03` — `Resource::__construct` merges every field slug into `$fillable`; `Create::save()` passes the raw form to `create()`; the User resource's fillable includes `current_team_id`, `two_factor_secret`, `remember_token`.
- **Goal:** close remotely-callable-without-authz and arbitrary-field-write gaps.
- **Approach:**
  1. Add `authorize()` to the listed public methods; whitelist allowed bulk-action names.
  2. Replace raw-form → `create()` with an allowlist derived from the validated rules / declared field slugs, explicitly excluding system columns. Apply the same to `Edit::save()`.
- **Tests:** setting `current_team_id`/`two_factor_secret` via form is rejected; an unauthorized caller of `updateCardStatus`/`bulkAction` gets 403. Pairs with general PR-6's isolation suite.
- **Risk:** medium — may reject inputs previously (wrongly) accepted; focused tests ensure legitimate fields still save.
- **Depends on:** PR-1; coordinates with PR-6.

---

## PR-7 — XSS, uploads, API surface  `SEC-06, SEC-07, SEC-08, SEC-09, SEC-10`
- **Branch:** `fix/xss-uploads-api`
- **Findings (grouped — all "untrusted data reaching a sink"):**
  - `SEC-06` — unescaped `{!! !!}` rendering of action `onclick` handlers and wysiwyg content.
  - `SEC-07` — SVG uploads accepted without sanitization.
  - `SEC-08` — `/api/fields/values` instantiates a client-supplied class name (`app($request->field)`).
  - `SEC-09` — thumbnail controller serves any path without ownership/authorization check.
  - `SEC-10` — `orderByRaw('... '.$direction)` concatenation (guarded today, unhardened).
- **Goal:** sanitize/validate each untrusted input before it reaches its sink.
- **Approach:** escape or allowlist-sanitize the wysiwyg/action HTML sinks; sanitize or deny script-bearing SVG uploads; whitelist `app($request->field)` against the registered-fields list and add authorization; add an ownership/authz check to the thumbnail controller; strict-cast the sort direction to `asc|desc`.
- **Tests:** XSS payload renders escaped; script-SVG rejected; API rejects unregistered class + unauthorized caller; thumbnail of a non-owned asset is denied.
- **Risk:** medium — wysiwyg sanitization can change accepted markup; needs a clear allowlist decision.
- **Depends on:** PR-1.

---

## PR-8 — Resource Editor codegen safety  `SEC-05`
- **Branch:** `fix/resource-editor-codegen`
- **Finding:** `SaveFields` rewrites resource class files using regex against `getFields()` and injects `var_export`-style user content — no AST, no `php -l`, no backup; delete does `unlink()`. Odd code style or crafted field content can corrupt or inject into real application code.
- **Goal:** make the crown-jewel feature safe to write real code.
- **Approach:** replace regex rewriting with AST manipulation (`nikic/php-parser`); validate with `php -l` before writing; write-to-temp + atomic rename + timestamped backup; soft-delete (archive) instead of `unlink`. Reuses the authorization added in PR-4.
- **Tests:** round-trip a resource through the editor and assert the file parses; odd-style/crafted input does not destroy the file; delete is recoverable.
- **Risk:** higher (new dependency + core feature) — isolated PR, thorough tests, manual verify.
- **Depends on:** PR-4 (authorization), PR-1.

---

## Status tracker (security track)
| PR | Branch | Findings | Severity | State |
|----|--------|----------|----------|-------|
| PR-2 | `fix/plugins-rce` | SEC-01 | Critical | not started |
| PR-3 | `fix/team-cache-invalidation` | SEC-02 | Critical | not started |
| PR-4 | `fix/authz-mass-assignment` | SEC-03,04 | High | not started |
| PR-7 | `fix/xss-uploads-api` | SEC-06..10 | High/Medium | not started |
| PR-8 | `fix/resource-editor-codegen` | SEC-05 | High | not started |

Also tracked separately: **SEC-11** — 8 open Dependabot alerts (1 high) on the default branch; the auto-merge gating is handled in general PR-1, but the alerts themselves need triage (bump or patch) as a small standalone security PR.
