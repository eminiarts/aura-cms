# Aura CMS — Audit Findings

> Date: 2026-06-12 · Branch at audit time: `develop` · Method: static code analysis (multi-agent fan-out + manual verification). No exploit testing or benchmarking was performed; severity and exploitability are code-confirmed where marked, otherwise suspected.

## Legend

- **Verification** — `confirmed` (read the code and the claim holds), `structural` (the dangerous pieces are all present; a working exploit/measurement was not built), `suspected` (pattern-based, not traced end-to-end).
- **Severity** — impact if exploited/triggered in a production multi-tenant deployment.
- Each finding has a stable ID (e.g. `SEC-01`) referenced by the remediation plan.

---

## 1. Security

| ID | Sev | Verification | Title | Location |
|----|-----|--------------|-------|----------|
| SEC-01 | Critical | confirmed | Remote command execution via `exec`/`Process` with user input + hardcoded dev paths | `src/Livewire/PluginsPage.php:125,133` |
| SEC-02 | Critical | confirmed | Team-switch never invalidates `rememberForever` tenancy cache → stale tenant scope | `src/Models/Scopes/TeamScope.php:106`, `src/Resources/User.php:735` |
| SEC-03 | High | structural | Dynamic `$fillable` + raw form to `create()` enables mass assignment of sensitive cols | `src/Resource.php:59`, `src/Livewire/Resource/Create.php:183`, `src/Resources/User.php:75` |
| SEC-04 | High | confirmed | Remotely-callable Livewire methods without `authorize()` | `src/Livewire/Table/Table.php:412`, `src/Livewire/Table/Traits/BulkActions.php`, `src/Livewire/ResourceEditor.php:136` |
| SEC-05 | High | confirmed | Runtime PHP codegen via regex + `var_export`, no validation/backup, `unlink()` on delete | `src/Traits/SaveFields.php:29-57`, `src/Livewire/ResourceEditor.php:166` |
| SEC-06 | High | confirmed | Stored XSS: unescaped `{!! !!}` rendering of action `onclick` + wysiwyg content | `resources/views/livewire/resource/actions.blade.php`, `resources/views/components/fields/wysiwyg.blade.php` |
| SEC-07 | Medium | confirmed | SVG uploads accepted without sanitization (script-in-SVG XSS) | `src/Livewire/MediaUploader.php:58` |
| SEC-08 | Medium | confirmed | `/api/fields/values` instantiates client-supplied class name `app($request->field)` | `src/Http/Controllers/Api/FieldsController.php` |
| SEC-09 | Medium | suspected | Thumbnail controller serves any path without ownership/authorization check | `src/Http/Controllers/ImageController.php`, `src/Services/ThumbnailGenerator.php` |
| SEC-10 | Low | suspected | `orderByRaw('... '.$direction)` concatenation in sorting (guarded, but unhardened) | `src/Livewire/Table/Traits/Sorting.php:79` |
| SEC-11 | Medium | confirmed | 8 open Dependabot alerts (1 high) on default branch; auto-merge not gated on green CI | GitHub Security tab, `.github/workflows/dependabot-auto-merge.yml` |

### Detail on the critical/high items

**SEC-01 — RCE via PluginsPage.** `runComposerUpdate()` runs `exec('cd .. && /opt/homebrew/bin/php /usr/local/bin/composer update 2>&1')` and `updatePackage($name, $version)` builds `'composer require '.$name.':'.$version` passed to `Process::fromShellCommandline` with no escaping. Both are **public Livewire methods** — any client able to render the component can invoke them with arbitrary arguments. The hardcoded `/opt/homebrew` paths also mean the feature only ever worked on the original developer's machine.

**SEC-02 — Tenant cache never cleared.** `TeamScope::getCurrentTeamId()` caches via `Cache::rememberForever("user_{id}_current_team_id")`. `User::switchTeam()` updates `current_team_id` but issues no `Cache::forget` for that key — and no such forget exists anywhere in `src/`. After switching teams, queries remain scoped to the previous team indefinitely. `tests/Pest.php:66` manually forgets this key, i.e. the suite already works around the bug.

**SEC-03 — Mass assignment.** `Resource::__construct` merges every field slug into `$fillable` via `mergeFillable($this->inputFieldsSlugs())`. `Create::save()` passes the raw Livewire `$this->form` (non-custom-table path) into `create()`. Validation does not strip unknown keys, and the User resource's fillable explicitly includes `current_team_id`, `two_factor_secret`, `remember_token`. The components for cross-tenant writes / 2FA tampering are present; a working exploit was not built (hence *structural*).

**SEC-04 — Unauthorized Livewire endpoints.** Every public method on a Livewire component is an HTTP endpoint. `Table::updateCardStatus($cardId, $newStatus)` mutates any record's status with no policy check; `BulkActions::bulkAction()` calls a client-supplied method name on each selected model; `ResourceEditor::checkAuthorization()` validates only the feature flag and app-vs-vendor path, not the actor — and that component writes PHP into the host app.

**SEC-05 — Unsafe codegen.** `SaveFields` rewrites resource class files with regex against `getFields()` and injects `var_export`-style user content. No AST, no `php -l`, no backup; delete does `unlink()`. Unusual code style in the target or crafted field content can corrupt or inject into real application code.

### Verified NON-issues (agent false positives)

- **GlobalSearch / MediaManager cross-team leak** — *not* a leak. Both query through `Resource`, so `TeamScope` applies. Dismissed after reading `src/Livewire/GlobalSearch.php`.
- **SQL injection in sort direction (as "exploitable")** — `$direction` is set internally by `sortBy()`, not directly client-settable; downgraded to SEC-10 (hardening, not active vuln).

---

## 2. Livewire 3/4 split-brain

| ID | Sev | Verification | Title | Location |
|----|-----|--------------|-------|----------|
| LW-01 | High | confirmed | `composer.json` claims `^3.6\|^4.0` but `Livewire\Finder\Finder` is v4-only → breaks on v3 | `src/Livewire/Modals.php:7`, `composer.json` |
| LW-02 | High | confirmed | `dispatch('openSlideOver', component: …)` collides with v4 reserved `component:` routing → breaks on v4 (the 2 failing ResourceEditor tests) | `src/Livewire/ResourceEditor.php:53,91,219,394` |
| LW-03 | Medium | confirmed | Three overlapping modal systems; two are dead code | `src/Livewire/Modal.php`, `src/Livewire/SlideOver.php` (dead); `Modals.php` (active) |
| LW-04 | Low | confirmed | v2-era `Livewire.emit()` leftover + malformed `$wire.dispatch('openSlideOver','notifications')` | `resources/views/livewire/navigation.blade.php:192,225` |

**Verdict:** the package has *already* committed to v4 via the `Finder` dependency but hasn't finished the migration. The cheapest correct path is **v4-only**: pin `^4.0`, fix the 4 dispatch calls, delete the two dead components, fix the navigation dispatch. This also fixes 2 of the 3 deterministic test failures and makes the version constraint honest.

---

## 3. Data model & performance (EAV)

| ID | Sev | Verification | Title | Location |
|----|-----|--------------|-------|----------|
| PERF-01 | High | confirmed | Search does leading-wildcard `LIKE '%term%'` on `meta.value` LONGTEXT (full scan per resource) | `src/Livewire/GlobalSearch.php:57-70`, `src/Livewire/Table/Traits/Search.php` |
| PERF-02 | High | confirmed | Per-field `leftJoin` to meta for each sorted/filtered meta field, stacking | `src/Livewire/Table/Traits/Sorting.php:85-100` |
| PERF-03 | Medium | confirmed | `updateOrCreate` per meta field on save (N queries/save); permission gen loops too | `src/Traits/SaveMetaFields.php:134`, `src/Jobs/GenerateAllResourcePermissions.php` |
| PERF-04 | Medium | confirmed | Large public Livewire state (columns/settings/filters arrays; full Resource instance) serialized every request | `src/Livewire/Table/Table.php`, `src/Livewire/Resource/Index.php` |
| PERF-05 | Medium | confirmed | Per-row field accessor work during table render (N+1 risk via custom field `get()`) | `src/Resource.php:267-298`, `src/Traits/AuraModelConfig.php:104-145` |
| PERF-06 | Low | confirmed | No FK constraints posts↔meta; MySQL-only `value(255)` prefix index | `database/migrations/*aura_tables*.stub` |
| PERF-07 | Medium | confirmed | Synchronous thumbnail generation in request path + repeated `Storage::exists` | `src/Http/Controllers/ImageController.php`, `src/Services/ThumbnailGenerator.php:65,70,109` |

These are fine at demo scale and bite around tens of thousands of records / hundreds of thousands of meta rows — i.e. exactly the "SaaS backend" scale the product targets. The fix is not to abandon EAV but to: honest limits + earlier `$customTable`, fulltext/exact-match search, batched `upsert()`, computed Livewire properties, queued thumbnails.

---

## 4. Architecture & maintainability

| ID | Sev | Verification | Title | Location |
|----|-----|--------------|-------|----------|
| ARCH-01 | Medium | confirmed | `static $applying` flag in TeamScope is process-global, not request/coroutine safe | `src/Models/Scopes/TeamScope.php:14` |
| ARCH-02 | Medium | confirmed | God objects: `User` (817 LOC), `AuraModelConfig` (632, ~77 methods), `ResourceEditor` (590), `Settings` (548), `Table` (505 + 11 traits) | see paths |
| ARCH-03 | Medium | confirmed | No centralized meta casting; `is_numeric()`/`(int)` juggling scattered across 10+ sites | `src/Traits/AuraModelConfig.php:533`, `Create.php:129-150`, others |
| ARCH-04 | Low | confirmed | Duplicate Livewire component registration (2-3 aliases each, hand-maintained) | `src/AuraServiceProvider.php:112-213` |
| ARCH-05 | Low | confirmed | Field view/component resolution by magic string; no versioned plugin contract | `src/Fields/Field.php:46-77` |
| ARCH-06 | Low | confirmed | Silent `catch (\Exception) { return false; }` in conditional logic hides bugs | `src/ConditionalLogic.php:121-135` |
| ARCH-07 | Low | confirmed | `minimum-stability: beta`; likely-unused `doctrine/dbal`, `sanctum` deps | `composer.json` |

**Genuinely good (keep / build on):** the field-processing **Pipeline** pattern (17 single-purpose pipes), correctly-implemented global scopes, the Facade + `AuraFake` test seam, morph-based meta relation, and the modular service-provider boot.

---

## 5. Testing & process

| ID | Sev | Verification | Title | Location |
|----|-----|--------------|-------|----------|
| PROC-01 | High | confirmed | CI runs serial `pest`; `composer test` runs `--parallel` → parallel flakiness invisible in CI | `.github/workflows/run-tests.yml`, `composer.json` |
| PROC-02 | High | confirmed | No fresh-install CI job → `composer.json` was unsatisfiable on `main` for months undetected | `composer.json` history; fixed in `f0945cfb` |
| PROC-03 | Medium | confirmed | Parallel flakiness roots: SQLite `:memory:` per-process; non-unique FS paths; `Aura` singleton `$resources/$fields` never cleared | `phpunit.xml.dist`, `tests/Feature/Aura/CreatePluginTest.php`, `tests/Pest.php`, `src/Aura.php` |
| PROC-04 | Medium | confirmed | Dependabot auto-merge not gated on passing checks | `.github/workflows/dependabot-auto-merge.yml` |
| PROC-05 | Medium | confirmed | PHPStan level 3 → 362 errors, empty baseline, not failing CI | `phpstan-baseline.neon`, `.github/workflows/phpstan.yml` |
| PROC-06 | High | confirmed | No cross-team isolation test suite (the #1 test for a multi-tenant product) | `tests/` |
| PROC-07 | Medium | confirmed | ~20 of 43 field types untested (incl. all relation fields BelongsTo/BelongsToMany/HasOne) | `src/Fields/` vs `tests/Feature/Fields/` |
| PROC-08 | Medium | confirmed | Zero tests for PluginsPage, FieldsController API; misnamed `createSuperAdmin` (team-scoped, not global) drives the CreateTeamTest failure & exposes auth-model confusion | `tests/Pest.php`, `tests/Feature/Team/CreateTeamTest.php` |
| PROC-09 | Low | confirmed | 3 deterministic failures (2× ResourceEditor → LW-02; 1× CreateTeam → auth-model) | see LW-02 / PROC-08 |

**Auth-model confusion (root of PROC-08):** `ResourcePolicy` checks `isSuperAdmin()`, `TeamPolicy` checks a separate `AuraGlobalAdmin` gate, and the helper named `createSuperAdmin()` actually creates a *team-scoped* admin. These three disagree about what "super admin" means; the failing test is a symptom.

---

## 6. Product / ecosystem (potential & strategic risk)

### Potential (rank-ordered, with the unlock)
1. **Visual Resource Editor → real PHP** — unique vs Filament (runtime arrays) / Statamic (YAML). Unlock: AST-safe codegen + authz (SEC-05/SEC-04) makes it shippable as a headline feature.
2. **43 field types + Pipeline architecture** — unusually deep; the pipeline is the right extensibility base.
3. **Teams-by-default multi-tenancy** — enterprise wedge Filament lacks natively. Unlock: fix tenancy bugs (SEC-02) + isolation test suite (PROC-06) so it can be *claimed*.
4. **30k lines of unpublished docs** — highest-leverage/lowest-effort growth move: publish them (Starlight/VitePress + domain).
5. **Livewire 4-native** — first-mover positioning once LW-01..04 are resolved.

### Strategic risk
- **Bus factor** — ~78% of commits from one author; cadence falling (160/mo → single digits).
- **Beta stability**, no published breaking-change policy; README still promises a "January 2025" launch.
- **No monetization model.**
- **Documented-but-unbuilt features** — Flows is a 344-line design doc with no `src/Flows/`; "API Ready" = one endpoint. The headless/content-API gap excludes SPA/mobile adopters entirely.

---

## Severity rollup

| Severity | Count |
|----------|-------|
| Critical | 2 (SEC-01, SEC-02) |
| High | 11 |
| Medium | 17 |
| Low | 9 |

Two caveats on method: security findings are code-confirmed but **not exploit-tested**; performance findings are **proven patterns, not benchmarks**. A follow-up pass can turn the security cluster into verified, reproduced cases with patches.
