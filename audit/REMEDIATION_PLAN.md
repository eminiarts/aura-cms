# Aura CMS — Remediation Plan

> Companion to [`FINDINGS.md`](./FINDINGS.md). Each finding ID (`SEC-01`, `LW-02`, …) maps to a workstream below. The plan is **modular**: every workstream is an independent PR against `develop`, sized to be reviewable on its own and orderable by dependency.

## Principles

1. **One PR = one coherent concern.** A reviewer should hold the whole change in their head.
2. **Security and process-safety first.** Fixing CI (so the suite is trustworthy) and the two criticals come before everything else, because every later PR relies on a green, honest test run.
3. **Every behavioral fix ships with a test that fails before and passes after.** No fix lands on trust alone.
4. **No drive-by scope creep.** Refactors (god-objects, casting) are their own PRs, never smuggled into a security fix.
5. **Branch naming:** `fix/<area>-<short>` off `audit/remediation`'s base (`develop`). The `audit/remediation` branch itself only carries these two docs.

## Dependency order (the critical path)

```
PR-0 (docs, this) ──► PR-1 (CI trust) ──► PR-2..PR-4 (security criticals/highs)
                                      └─► PR-5 (Livewire 4) ──► fixes 2 failing tests
PR-1 ──► PR-6 (auth-model + isolation tests)
(independent, after PR-1 green) ──► PR-7 (perf), PR-8 (arch refactors), PR-9 (docs site), PR-10 (deps)
```

PR-1 is the gate. Until CI runs the parallel suite and a fresh install, no other PR's "green" is meaningful.

---

## PR-0 — Audit docs *(this PR)*
- **Branch:** `audit/remediation`
- **Contents:** `audit/FINDINGS.md`, `audit/REMEDIATION_PLAN.md`.
- **Why first:** shared reference every subsequent PR links back to (`Fixes SEC-01`, etc.).
- **Risk:** none.

---

## PR-1 — Make CI trustworthy  `PROC-01..05`
- **Branch:** `fix/ci-trust`
- **Goal:** CI green must mean the suite actually passes the way developers run it, on a fresh install.
- **Changes:**
  1. Add a **parallel test job** mirroring `composer test` (`pest --parallel`) to `run-tests.yml`, alongside the existing serial run.
  2. Add a **fresh-install job**: clean `composer install` from scratch on the matrix (PHP 8.2/8.3 × Laravel 10/11/12) — would have caught `PROC-02`.
  3. Fix the parallel-flakiness roots so the new job is green: unique temp paths in `CreatePluginTest`/`ViewPostTest` (use `sys_get_temp_dir().'/aura-'.uniqid()`); add a real `Aura::reset()` (clears `$resources/$fields/$widgets/$config`) and call it in `tests/Pest.php` afterEach; `Cache::flush()` (or `cache.default=array` per-process) in afterEach.
  4. Gate **dependabot auto-merge** on required status checks (`PROC-04`).
  5. PHPStan (`PROC-05`): generate a baseline of the current 362 so CI fails on *new* errors, and flip the workflow to fail on non-baseline findings. (Burning down the baseline is later, per-area.)
- **Tests:** the new CI jobs themselves; locally verify `pest --parallel` is green across 3 consecutive runs.
- **Risk:** medium (CI config); no runtime code paths touched except test-support.
- **Depends on:** PR-0.

---

## PR-2 — Neutralize the plugin RCE  `SEC-01`
- **Branch:** `fix/plugins-rce`
- **Goal:** remove arbitrary shell execution reachable from a Livewire endpoint.
- **Changes:** delete `runComposerUpdate()` and `updatePackage()` shell paths (or hard-gate behind an explicit, off-by-default `local`+global-admin guard with `escapeshellarg` and no hardcoded paths — **default is removal**; composer-at-runtime is not a CMS responsibility). Add `authorize()` to any remaining public method on `PluginsPage`.
- **Tests:** new `PluginsPageTest` asserting the dangerous methods are gone/forbidden for non-admin and in non-local env (`PROC-08` partly addressed here).
- **Risk:** low (feature was Mac-only/broken anyway).
- **Depends on:** PR-1.

---

## PR-3 — Fix tenant cache invalidation  `SEC-02`
- **Branch:** `fix/team-cache-invalidation`
- **Goal:** switching teams must immediately re-scope queries.
- **Changes:** in `User::switchTeam()` (and anywhere `current_team_id` changes), `Cache::forget("user_{$id}_current_team_id")`. Consider a `User::saved` hook so any path that mutates the column is covered. Optionally add a short TTL as defense-in-depth.
- **Tests:** **the linchpin test** — create two teams + posts, switch, assert queries no longer return the old team's rows; assert the cache key is gone after switch. Remove the workaround forget in `tests/Pest.php:66` once the real fix lands (or keep + document).
- **Risk:** low; high value (correctness + isolation).
- **Depends on:** PR-1.

---

## PR-4 — Authorization & mass-assignment hardening  `SEC-03, SEC-04`
- **Branch:** `fix/authz-mass-assignment`
- **Goal:** close the remotely-callable-without-authz and arbitrary-field-write gaps.
- **Changes:**
  1. `authorize()` on `Table::updateCardStatus`, `BulkActions::bulkAction` (also whitelist allowed action names), `ResourceEditor` mutating methods.
  2. Replace raw `$this->form` → `create()` with an **allowlist derived from the validated rules / declared field slugs**, explicitly excluding system columns (`current_team_id`, `two_factor_*`, `remember_token`). Apply the same on `Edit::save()`.
- **Tests:** attempt to set `current_team_id`/`two_factor_secret` via form → rejected; non-authorized user calling `updateCardStatus`/`bulkAction` → 403; ties into the isolation suite from PR-6.
- **Risk:** medium — could reject inputs previously (wrongly) accepted; needs care that legit fields still save. This is why it's its own PR with focused tests.
- **Depends on:** PR-1; pairs with PR-6.

---

## PR-5 — Commit to Livewire 4  `LW-01..04` (+ fixes 2 failing tests)
- **Branch:** `fix/livewire-4`
- **Goal:** one consistent, working Livewire version.
- **Changes:** pin `composer.json` to `^4.0`; fix the 4 `dispatch('openSlideOver', component: …)` calls to the v4 payload shape; delete dead `Modal.php` + `SlideOver.php` (and dead views); fix `navigation.blade.php:225` dispatch + remove the `Livewire.emit()` leftover.
- **Tests:** the 2 `ResourceEditorTest` failures (`can add new tab`, `can edit tab`) must pass; add an assertion on the dispatched payload shape.
- **Risk:** medium (touches modal/slideover UX) — needs a manual smoke via the `verify`/`run` skill before merge.
- **Depends on:** PR-1.

---

## PR-6 — Auth-model clarity + cross-team isolation suite  `PROC-06, PROC-08, PROC-09(CreateTeam)`
- **Branch:** `test/team-isolation-and-authz`
- **Goal:** define what "super admin" vs "global admin" means, then prove tenant isolation.
- **Changes:** reconcile `ResourcePolicy::isSuperAdmin()` vs `TeamPolicy`'s `AuraGlobalAdmin` gate; rename/clarify `createSuperAdmin()` (add `createGlobalAdmin()` if needed); fix `CreateTeamTest` to assert the now-correct model.
- **Tests:** dedicated `TeamScopeTest` — users/posts/roles in team A invisible from team B across query, search, table, API. This is the test that lets the product *claim* isolation.
- **Risk:** low-medium (may surface more authz gaps — good).
- **Depends on:** PR-1; informs/validates PR-4.

---

## PR-7 — XSS, uploads, API surface  `SEC-06, SEC-07, SEC-08, SEC-09, SEC-10`
- **Branch:** `fix/xss-uploads-api`
- **Goal:** the remaining medium/high security items, grouped because they're all "untrusted data reaching a sink."
- **Changes:** escape or sanitize the `{!! !!}` action/wysiwyg sinks (allowlist HTML via a sanitizer); sanitize/deny SVG uploads; whitelist `app($request->field)` against registered fields in `FieldsController` + add authz; ownership/authz check on the thumbnail controller; harden the `orderByRaw` direction with a strict `asc|desc` cast.
- **Tests:** XSS payload in action/wysiwyg rendered escaped; script-SVG rejected; API rejects unregistered class + unauthorized caller.
- **Risk:** medium (wysiwyg sanitization can change accepted markup — needs a clear allowlist decision).
- **Depends on:** PR-1.

---

## PR-8 — Resource Editor codegen safety  `SEC-05`
- **Branch:** `fix/resource-editor-codegen`
- **Goal:** make the crown-jewel feature safe to write real code.
- **Changes:** replace regex rewriting with `nikic/php-parser` AST manipulation; `php -l` validation before write; write-to-temp + atomic rename + timestamped backup; soft-delete (archive) instead of `unlink`. Reuses the authz from PR-4.
- **Tests:** round-trip a resource through the editor and assert the file parses; corrupt/odd-style input doesn't destroy the file; delete is recoverable.
- **Risk:** higher (new dependency + core feature) — isolated PR, thorough tests, manual verify.
- **Depends on:** PR-4 (authz), PR-1.

---

## PR-9 — Performance: search, meta writes, payloads  `PERF-01..07`
- **Branch:** `perf/eav-search-and-writes`
- **Goal:** remove the patterns that fail at scale, without a data-model rewrite.
- **Changes (can split if large):** fulltext/exact-match search instead of leading-wildcard LIKE on `meta.value`; batched `upsert()` for meta saves + permission generation; move heavy `Table`/`Index` public arrays to `#[Computed]`; queue thumbnail generation + reduce `Storage::exists` calls; add the missing composite index + document `$customTable` thresholds; add posts↔meta FK where portable.
- **Tests:** query-count assertions (assert N saves ≠ N queries), search returns expected rows; a documented note on benchmark methodology (this PR is the first to warrant actual measurement).
- **Risk:** medium-high (search semantics + migrations) — may itself split into PR-9a (writes/payloads) and PR-9b (search/indexes).
- **Depends on:** PR-1; ideally after PR-6 (isolation tests guard against regressions).

---

## PR-10 — Architecture & dependency hygiene  `ARCH-01..07`
- **Branch:** `refactor/arch-and-deps`
- **Goal:** lower the maintenance tax. Lowest urgency; do last, in slices.
- **Changes (each can be its own sub-PR):** centralize meta casting (`ARCH-03`); request-scoped instead of `static $applying` (`ARCH-01`); loop-generate the Livewire component aliases (`ARCH-04`); stop swallowing exceptions in conditional logic (`ARCH-06`); set `minimum-stability: stable` + drop unused deps after verifying (`ARCH-07`). God-object extraction (`ARCH-02`) is opportunistic, not forced.
- **Tests:** existing suite must stay green; add tests around extracted units.
- **Risk:** medium (broad surface) — slice aggressively.
- **Depends on:** everything above being green.

---

## Out of scope here (product, not code)
Tracked in `FINDINGS.md §6` for the maintainer, not actioned by these PRs: bus factor, monetization, publishing docs (PR-9 adjacent but a separate effort), building Flows / a real content API, the stale README launch date.

---

## Execution checklist (per PR)
- [ ] Branch off `develop`, named per convention.
- [ ] Change + test(s) that fail-before/pass-after.
- [ ] `composer test` (parallel) green locally, 2 consecutive runs.
- [ ] `composer analyse` no new PHPStan errors above baseline.
- [ ] PR body links the finding IDs it closes and notes residual risk.
- [ ] Manual `verify`/`run` smoke for any UI-affecting PR (PR-5, PR-8).

## Status tracker
| PR | Branch | Findings | State |
|----|--------|----------|-------|
| PR-0 | `audit/remediation` | docs | in progress |
| PR-1 | `fix/ci-trust` | PROC-01..05 | not started |
| PR-2 | `fix/plugins-rce` | SEC-01 | not started |
| PR-3 | `fix/team-cache-invalidation` | SEC-02 | not started |
| PR-4 | `fix/authz-mass-assignment` | SEC-03,04 | not started |
| PR-5 | `fix/livewire-4` | LW-01..04 | not started |
| PR-6 | `test/team-isolation-and-authz` | PROC-06,08,09 | not started |
| PR-7 | `fix/xss-uploads-api` | SEC-06..10 | not started |
| PR-8 | `fix/resource-editor-codegen` | SEC-05 | not started |
| PR-9 | `perf/eav-search-and-writes` | PERF-01..07 | not started |
| PR-10 | `refactor/arch-and-deps` | ARCH-01..07 | not started |
