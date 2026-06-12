# Aura CMS — Remediation Plan (General Track)

> Companion to [`FINDINGS.md`](./FINDINGS.md). Security-sensitive workstreams live in a separate document: [`REMEDIATION_PLAN_SECURITY.md`](./REMEDIATION_PLAN_SECURITY.md). This file is the **index** plus the **non-security** tracks (CI/process, Livewire, performance, architecture, testing).
>
> The split is deliberate: the security track concentrates the SEC-* findings (RCE, authorization, mass assignment, XSS/uploads, unsafe codegen) so they can be reviewed, prioritized, and disclosed on their own; the general track can proceed in parallel without coupling.

## Principles

1. **One PR = one coherent concern.** A reviewer should hold the whole change in their head.
2. **Process-safety first.** Fixing CI (so green means green) comes before everything else, because every later PR relies on a trustworthy test run.
3. **Every behavioral fix ships with a test that fails before and passes after.** No fix lands on trust alone.
4. **No drive-by scope creep.** Refactors are their own PRs, never smuggled into another fix.
5. **Branch naming:** `fix/…`, `perf/…`, `refactor/…`, `test/…` off `develop`. The `audit/remediation` branch itself only carries the planning docs (PR-0).

## All workstreams (both tracks)

| PR | Track | Branch | Findings | Title |
|----|-------|--------|----------|-------|
| PR-0 | general | `audit/remediation` | — | Audit docs (this) |
| PR-1 | general | `fix/ci-trust` | PROC-01..05 | Make CI trustworthy |
| PR-2 | **security** | `fix/plugins-rce` | SEC-01 | Neutralize plugin RCE |
| PR-3 | **security** | `fix/team-cache-invalidation` | SEC-02 | Fix tenant cache invalidation |
| PR-4 | **security** | `fix/authz-mass-assignment` | SEC-03,04 | Authorization & mass-assignment hardening |
| PR-5 | general | `fix/livewire-4` | LW-01..04 | Commit to Livewire 4 |
| PR-6 | general | `test/team-isolation-and-authz` | PROC-06,08,09 | Auth-model clarity + isolation tests |
| PR-7 | **security** | `fix/xss-uploads-api` | SEC-06..10 | XSS, uploads, API surface |
| PR-8 | **security** | `fix/resource-editor-codegen` | SEC-05 | Resource Editor codegen safety |
| PR-9 | general | `perf/eav-search-and-writes` | PERF-01..07 | Performance: search, meta writes, payloads |
| PR-10 | general | `refactor/arch-and-deps` | ARCH-01..07 | Architecture & dependency hygiene |

**Security-track PRs (PR-2, 3, 4, 7, 8) are specified in [`REMEDIATION_PLAN_SECURITY.md`](./REMEDIATION_PLAN_SECURITY.md).** They are listed here only so the dependency picture is complete.

## Dependency order (the critical path)

```
PR-0 (docs) ──► PR-1 (CI trust) ──┬─► general:  PR-5, PR-6, PR-9, PR-10
                                  └─► security: PR-2, PR-3, PR-4, PR-7, PR-8
```

PR-1 is the gate for **both** tracks. Until CI runs the parallel suite and a fresh install, no other PR's "green" is meaningful. After PR-1, the two tracks are largely independent; PR-6's isolation suite doubles as the regression net for security PR-3/PR-4.

---

# General-track PRs

## PR-0 — Audit docs *(this PR — #22)*
- **Branch:** `audit/remediation`
- **Contents:** `audit/FINDINGS.md`, `audit/REMEDIATION_PLAN.md`, `audit/REMEDIATION_PLAN_SECURITY.md`.
- **Why first:** shared reference every subsequent PR links back to (`Fixes SEC-01`, etc.).
- **Risk:** none.
- **State:** ✅ open as PR #22.

---

## PR-1 — Make CI trustworthy  `PROC-01..05`
- **Branch:** `fix/ci-trust`
- **Goal:** CI green must mean the suite actually passes the way developers run it, on a fresh install.
- **Changes (as landed):**
  1. Add a **parallel test job** mirroring `composer test` (`pest --parallel`) to `run-tests.yml`, alongside the serial run; workflows also trigger on `develop` (remediation PRs target it — previously CI didn't run on them at all).
  2. Add a **fresh-install job**: clean dependency resolve on the matrix (PHP 8.2/8.3 × Laravel **11/12**) — would have caught `PROC-02`. *Deviation from plan: Laravel 10 dropped from the matrix because `laravel/sanctum ^4.0` in `require` makes a Laravel 10 install unsatisfiable today; the `^10.0` in require-dev is dead. Decide in PR-10: drop the L10 claim or fix constraints.*
  3. Fix the parallel-flakiness root. The migrations-path fix from the draft plan turned out to be one instance of a wider class: workers shared the whole testbench skeleton and also raced on `app/Aura/Resources` (generator-test cleanup vs. every boot's `Aura::getAppResources()` Finder scan → random `DirectoryNotFoundException`), published assets, compiled Blade views, `routes/`, and the skeleton `composer.json`. Landed fix: each ParaTest worker boots from its **own throwaway copy of the skeleton** (`TestCase::applicationBasePath()` keyed by `UNIQUE_TEST_TOKEN`). Narrower `useAppPath()`/`usePublicPath()` overrides break `Application::getNamespace()`. Serial runs keep the shared skeleton.
  4. Add explicit `Aura::reset()` for mutable static state (`$userModel`) and call it in `tests/Pest.php` afterEach (the rebind already clears instance state).
  5. Gate **dependabot auto-merge** on required status checks (`PROC-04`): branch protection on `main` now requires the serial test check (none existed — auto-merge was unconditional). After PR-1 merges, also require the new parallel check.
  6. PHPStan (`PROC-05`): commit a baseline of the current errors so CI fails on **new** errors; workflow bumped to PHP 8.2 (8.1 couldn't resolve deps, so the job was permanently red/ignored) and runs on PRs. Burning down the baseline is later, per-area.
- **Tests/validation:** 8 consecutive `pest --parallel` full-suite runs with only the 3 known deterministic failures owned by PR-5/PR-6.
- **Risk:** medium (CI config + test-support); no production runtime paths touched.
- **Depends on:** PR-0.
- **State:** ✅ open as PR #23.

---

## PR-5 — Commit to Livewire 4  `LW-01..04` (+ fixes 2 failing tests)
- **Branch:** `fix/livewire-4`
- **Goal:** one consistent, working Livewire version.
- **Changes:** pin `composer.json` to `^4.0`; fix the 4 `dispatch('openSlideOver', component: …)` calls (ResourceEditor.php:53,91,219,394) to the v4 payload shape; delete dead `Modal.php` + `SlideOver.php` and their views; fix `navigation.blade.php:225` dispatch + remove the `Livewire.emit()` leftover.
- **Tests:** the 2 `ResourceEditorTest` failures (`can add new tab`, `can edit tab`) must pass; assert the dispatched payload shape.
- **Risk:** medium (modal/slideover UX) — manual `verify`/`run` smoke before merge.
- **Depends on:** PR-1.

---

## PR-6 — Auth-model clarity + cross-team isolation suite  `PROC-06, PROC-08, PROC-09`
- **Branch:** `test/team-isolation-and-authz`
- **Goal:** define what "super admin" vs "global admin" means, then prove tenant isolation with tests.
- **Changes:** reconcile `ResourcePolicy::isSuperAdmin()` vs `TeamPolicy`'s `AuraGlobalAdmin` gate; rename/clarify `createSuperAdmin()` (add `createGlobalAdmin()` if needed); fix the failing `CreateTeamTest` to assert the now-correct model. Also in scope (found during PR-1): `createSuperAdmin()` creates a Team unconditionally, so the `phpunit-without-teams.xml` suite fails on `MakeResourceCommandTest` with "no such table: teams" — pre-existing on `develop`, same helper confusion.
- **Tests:** dedicated `TeamScopeTest` — users/posts/roles in team A invisible from team B across query, search, table, API. This suite is also the regression net for security **PR-3** (tenant cache) and **PR-4** (authorization).
- **Risk:** low-medium (may surface more authz gaps — good; feed any into the security track).
- **Depends on:** PR-1. Cross-references security PR-3/PR-4.

---

## PR-9 — Performance: search, meta writes, payloads  `PERF-01..07`
- **Branch:** `perf/eav-search-and-writes`
- **Goal:** remove the patterns that fail at scale, without a data-model rewrite.
- **Changes (may split into PR-9a writes/payloads, PR-9b search/indexes):** fulltext/exact-match search instead of leading-wildcard `LIKE` on `meta.value`; batched `upsert()` for meta saves + permission generation; move heavy `Table`/`Index` public arrays to `#[Computed]`; queue thumbnail generation + reduce `Storage::exists` calls; add the missing composite index + document `$customTable` thresholds; add posts↔meta FK where portable.
- **Tests:** query-count assertions (N saves ≠ N queries); search returns expected rows; document benchmark methodology (first PR warranting real measurement).
- **Risk:** medium-high (search semantics + migrations).
- **Depends on:** PR-1; ideally after PR-6 (isolation tests guard against regressions).

---

## PR-10 — Architecture & dependency hygiene  `ARCH-01..07`
- **Branch:** `refactor/arch-and-deps`
- **Goal:** lower the maintenance tax. Lowest urgency; do last, in slices.
- **Changes (each can be a sub-PR):** centralize meta casting (`ARCH-03`); request-scoped instead of `static $applying` (`ARCH-01`); loop-generate the Livewire component aliases (`ARCH-04`); stop swallowing exceptions in conditional logic (`ARCH-06`); `minimum-stability: stable` + drop unused deps after verifying (`ARCH-07`). God-object extraction (`ARCH-02`) is opportunistic, not forced.
- **Tests:** existing suite stays green; add tests around extracted units.
- **Risk:** medium (broad surface) — slice aggressively.
- **Depends on:** everything above being green.

---

## Out of scope here (product, not code)
Tracked in `FINDINGS.md §6` for the maintainer: bus factor, monetization, publishing the docs site, building Flows / a real content API, the stale README launch date.

---

## Execution checklist (per PR)
- [ ] Branch off `develop`, named per convention.
- [ ] Change + test(s) that fail-before/pass-after.
- [ ] `composer test` (parallel) green locally, 2 consecutive runs.
- [ ] `composer analyse` no new PHPStan errors above baseline.
- [ ] PR body links the finding IDs it closes and notes residual risk.
- [ ] Manual `verify`/`run` smoke for any UI-affecting PR (PR-5).

## Status tracker (general track)
| PR | Branch | Findings | State |
|----|--------|----------|-------|
| PR-0 | `audit/remediation` | docs | ✅ PR #22 open |
| PR-1 | `fix/ci-trust` | PROC-01..05 | ✅ PR #23 open |
| PR-5 | `fix/livewire-4` | LW-01..04 | not started |
| PR-6 | `test/team-isolation-and-authz` | PROC-06,08,09 | not started |
| PR-9 | `perf/eav-search-and-writes` | PERF-01..07 | not started |
| PR-10 | `refactor/arch-and-deps` | ARCH-01..07 | not started |

_Security-track status is tracked in [`REMEDIATION_PLAN_SECURITY.md`](./REMEDIATION_PLAN_SECURITY.md)._
