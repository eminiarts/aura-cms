# V1 supports Laravel 13, Livewire 4, PHP 8.4+ only

For the 1.0 release we deliberately narrow the support matrix to Laravel 13, Livewire 4 only, and PHP 8.4+. The previously advertised matrix (PHP 8.2+, Laravel 10–13, Livewire 3–4) was never fully tested in CI and partially did not work; Laravel 10/11 are out of security support, and dual Livewire 3/4 support carries real compatibility code (the codebase already contained a v3/v4 split-brain: `composer.json` claimed `^3.6|^4.0` while importing v4-only classes). A V1 launch targets projects that start fresh; anyone on older stacks can stay on the 0.x series.

## Amendment 2026-08-23: Laravel 12 dropped

The original decision kept Laravel 12 alongside 13. It is dropped before the stable 1.0 tag. 1.0 targets fresh projects, which start on the latest framework major, so there is no install base on Laravel 12 to protect. Supporting two framework majors doubles the CI matrix and every compatibility decision downstream of it for no benefit. Aura CMS 1.0 therefore supports exactly one Laravel major: the latest one.

The rationale above is unchanged — this narrows the matrix further along the same reasoning.

## Consequences

- Livewire 3 compatibility code is removed, not maintained.
- The CI matrix tests exactly this range and nothing else: Laravel 13 on PHP 8.4 and 8.5.
- Widening the matrix later is easy; narrowing it after 1.0 would be a breaking promise. That asymmetry is why we start narrow.
- `composer.json` requires `^8.4`, so both PHP 8.4 and 8.5 installs are permitted, and CI gates on both. PHP 8.5 was temporarily removed from the CI matrix because its parallel-test scheduling deterministically triggered pre-existing test-isolation pollution (a `fields` attribute bleeding into a `posts` insert). That was test debt, not an Aura runtime incompatibility; it was fixed and PHP 8.5 was restored to the matrix in PR #68.
