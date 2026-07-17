# V1 supports Laravel 12/13, Livewire 4, PHP 8.4+ only

For the 1.0 release we deliberately narrow the support matrix to Laravel 12 and 13, Livewire 4 only, and PHP 8.4+. The previously advertised matrix (PHP 8.2+, Laravel 10–13, Livewire 3–4) was never fully tested in CI and partially did not work; Laravel 10/11 are out of security support, and dual Livewire 3/4 support carries real compatibility code (the codebase already contained a v3/v4 split-brain: `composer.json` claimed `^3.6|^4.0` while importing v4-only classes). A V1 launch targets projects that start fresh; anyone on older stacks can stay on the 0.x series.

## Consequences

- Livewire 3 compatibility code is removed, not maintained.
- The CI matrix tests exactly this range and nothing else.
- Widening the matrix later is easy; narrowing it after 1.0 would be a breaking promise. That asymmetry is why we start narrow.
- `composer.json` requires `^8.4`, so PHP 8.5 installs are permitted. CI, however, gates only on PHP 8.4 for 1.0: PHP 8.5's parallel-test scheduling deterministically triggers pre-existing test-isolation pollution (a `fields` attribute bleeding into a `posts` insert). That is test debt, not an Aura runtime incompatibility; PHP 8.5 is added back to the CI matrix once the pollution is fixed (tracked as a follow-up issue).
