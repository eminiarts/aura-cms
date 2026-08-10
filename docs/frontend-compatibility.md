# Frontend compatibility contract

Status: **accepted for CORE-24**. This is a build and integration decision, not
the theme-token implementation.

Audit snapshot: Aura commit `23720cdb265b1ec542291a51f59199c717b246a6`
on 2026-08-09.

## Decision

Aura 1.x supports host applications that use Tailwind CSS 3 or Tailwind CSS 4,
but it does not cross-compile framework source between those majors:

| Surface | Supported contract |
| --- | --- |
| Aura package source | Built only by Aura's locked Tailwind 3 toolchain |
| Published Aura UI | Precompiled CSS; the host needs no Tailwind dependency |
| Tailwind 3 host | Builds host-owned CSS as a separate entrypoint |
| Tailwind 4 host | Builds host-owned CSS as a separate entrypoint |
| Runtime customization | Version-neutral `--aura-*` CSS custom properties |
| Aura's v3 CSS imported into v4 | Unsupported |
| A v4 `@theme` file compiled by v3 | Unsupported |

Tailwind is an internal build dependency, not an Aura peer dependency. The
compatibility bridge is ordinary CSS custom properties plus separately
compiled stylesheets. It is not a shared Tailwind configuration.

## Binding contract for CORE-24

### Token source of truth

- `config/aura.php` owns public token defaults and host overrides. A host must
  be able to change them through its published config; it must not publish an
  Aura Blade view.
- The effective Aura theme/settings resolution remains responsible for
  per-installation values. The package-owned
  `components/layout/colors.blade.php` is the renderer, not a second source of
  defaults.
- Runtime variables use space-separated RGB channels so both
  `rgb(var(--token))` and `rgb(var(--token) / <alpha-value>)` remain possible.
- The first semantic namespace is fixed as:
  `--aura-font-sans`, `--aura-color-primary`,
  `--aura-color-background`, `--aura-color-panel`,
  `--aura-color-border`, `--aura-color-text`,
  `--aura-color-muted`, `--aura-color-success`,
  `--aura-color-warning`, and `--aura-color-danger`.
- Existing `--primary-*`, `--gray-*`, and `--sidebar-*` variables and their
  compiled utility classes remain available throughout Aura 1.x. Semantic
  defaults may reference those palette variables; palette values must not be
  duplicated in Tailwind-specific files.

The fixture in `tests/Fixtures/FrontendCompatibility/token-contract.css`
demonstrates the version-neutral boundary. It is evidence for the contract,
not a production token file.

### Compilation and entrypoints

- Aura maintainers own `resources/css/app.css`, `tailwind.config.js`, the Vite
  build, and committed `resources/dist` output. Aura 1.x keeps that source on
  Tailwind 3 syntax.
- Composer consumers run `php artisan aura:publish`. They consume the
  published manifest and compiled stylesheet through `Aura::viteStyles()`;
  they do not run Aura's Node build.
- A host owns its Vite/PostCSS/Tailwind version and compiles only host source.
  Host CSS may be injected with `components.layouts.aura-head`, but that hook
  currently renders before Aura's compiled stylesheet. It is not a guaranteed
  later cascade override. Token values therefore flow through Aura config and
  the package renderer.
- Importing `resources/css/app.css` or `tailwind.config.js` into a host build is
  outside the supported API.

### Content scanning

- Aura's build scans package-owned Blade/PHP source. CORE-24 must retain
  `resources/views/**/*.blade.php`, make PHP scanning recursive where needed,
  and keep paths relative to the config rather than the caller's working
  directory.
- Hosts scan host-owned source only. A published view override is host-owned
  source and is therefore scanned by the host.
- Public compatibility utilities must appear as complete literal class names
  in package content or an explicit package safelist. Dynamic class fragments
  are not a compatibility mechanism.
- A Tailwind 4 host that intentionally maps Aura variables to host utilities
  owns a v4 CSS entrypoint using `@theme inline`; a Tailwind 3 host owns a v3
  JavaScript config mapping. Neither file is fed to the other compiler.

### Dark mode

- Aura's runtime contract is the `.dark` class on the root element. Existing
  `auto`, `light`, and `dark` settings continue to control that class.
- Aura's Tailwind 3 build keeps `darkMode: 'selector'`.
- Tailwind 4 host utilities that must follow Aura use
  `@custom-variant dark (&:where(.dark, .dark *));`; the default media-query
  variant is not equivalent.
- Semantic surface tokens provide light values on `:root` and dark values
  under `.dark`, so a tokenized surface does not need parallel `dark:*`
  classes merely to switch token values.

### Compatibility aliases and fonts

- The package's Tailwind 3 config maps semantic colors to `aura.*`, yielding
  names such as `bg-aura-panel`, `text-aura-muted`, and
  `border-aura-border`. `font-sans` maps to `--aura-font-sans`.
- Existing `primary-*`, `gray-*`, and `sidebar-*` classes remain the Aura 1.x
  compatibility path while templates migrate incrementally.
- Aura's default font token is a system stack. Aura must not fetch a remote
  font or require a font that is absent from the package.
- A host that wants another font owns and serves the local font files, defines
  `@font-face` in host CSS, and selects that family through Aura config.
- The currently bundled Inter files may remain as deprecated compatibility
  assets in Aura 1.x, but CORE-24 stops loading `inter.css` automatically.

### Versioning

- Adding the semantic variables and aliases is additive in Aura 1.x.
- Removing legacy palette variables/classes or the bundled Inter assets needs
  deprecation and a new major release.
- Replacing Aura's package compiler with Tailwind 4 drops the v3 source path
  and is a versioned breaking change. Do it only in a new major, or ship a
  distinct full v4 entrypoint with output-equivalence tests first.
- Host Tailwind majors remain decoupled from Aura's major as long as the host
  consumes compiled Aura assets and the runtime variable contract.

## Audited build pipeline

The lockfile resolved these package-build versions:

| Tool | Declared | Locked/installed |
| --- | --- | --- |
| Node | `^20.19.0 || >=22.12.0` | `22.23.2` used for this audit |
| npm | n/a | `10.9.8` used for this audit |
| Tailwind CSS | `^3.4.13` | `3.4.19` |
| PostCSS | `^8.5.19` | `8.5.19` |
| Autoprefixer | `^10.4.2` | `10.5.4` |
| postcss-import | `^14.0.1` | `14.1.0` |
| Vite | `^8.1.5` | `8.1.5` |
| laravel-vite-plugin | `^3.1.3` | `3.1.3` |

The Tailwind 3 build also uses forms `0.5.11`, typography `0.5.20`,
aspect-ratio `0.4.2`, and tailwind-scrollbar `2.1.0`.

`vite.config.js` has two package-owned builds:

- default: `resources/css/app.css` plus `resources/js/app.js` into
  `resources/dist`;
- `lib` mode: six JavaScript library entrypoints into `resources/libs`.

`AuraServiceProvider` publishes `resources/dist` to `public/vendor/aura`,
`resources/libs` to `public/vendor/aura/libs`, and `resources/public` to
`public/vendor/aura/public`. The last directory contains local Inter files and
favicons; it contains no remote font dependency.

The isolated host fixture under
`tests/Fixtures/FrontendCompatibility/v4` commits its own `package.json` and
`package-lock.json`. It pins Tailwind CSS and `@tailwindcss/vite` `4.3.3` and
Vite `8.2.1`; none of those packages enter Aura's Tailwind 3 dependency tree.
Its HTML-linked CSS entrypoint is built by the real Vite plugin, which validates
the supported host integration shape.

## Reproducible evidence

Fixtures live in `tests/Fixtures/FrontendCompatibility`. The Node runner uses
`os.tmpdir()`, `path`, and `execFile()` and removes its generated workspace.
Generated CSS and the isolated Tailwind 4 install are never committed.

### Package install and build

```bash
composer install
npm ci
npm ls --depth=0 tailwindcss postcss vite laravel-vite-plugin autoprefixer postcss-import
npm run build
npm run test:frontend-compatibility
```

Current reproducible results (including CORE-24):

- `npm ci`: pass; 190 packages installed.
- Main build: pass twice from the current locks; each run used Vite `8.1.5`,
  transformed 158 modules, and left `resources/dist` byte-for-byte unchanged.
- `production-output-baseline.json` independently pins the complete committed
  `resources/dist` file set by path, byte count, and SHA-256. The compatibility
  gate compares both working-tree bytes and Git-index blobs to those values, so
  an unstaged or staged rebuild cannot validate itself merely by making
  `git diff` empty.
- The manifest is 331 bytes (SHA-256
  `81b0acf8adceed01a3bfc3531ef793c1f32a1f398a7630d4553347c348b230ce`)
  and references `assets/app-CKyC0Fy1.css` and
  `assets/app-ccnW50-_.js`.
- The reproduced CSS is 218,229 bytes (SHA-256
  `5d214cdfc9f2e554987f444ecbba647620ab6fcf44f6e60d202e8c522a051dea`);
  the reproduced JavaScript is 267,729 bytes (SHA-256
  `9fdf0e55d0f5a74ddb65467a2686701e989bba921de45dfdc339c77f13647253`).
- Composer dependencies are a build precondition: Aura's Tailwind content
  configuration deliberately scans Laravel pagination templates under
  `vendor/laravel/framework`. A temporary source archive without `vendor`
  emits a smaller stylesheet and is not valid reproducibility evidence.
- Library build: fail during Vite resolution because
  `monaco-themes` does not export `./themes/GitHub Dark.json` for the active
  production import conditions. This is independent of Tailwind but blocks a
  fully green package asset build.
- `npm audit`: three known development-tree findings (one moderate, two high):
  PostCSS, nanoid, and brace-expansion. Dependency updates were deliberately
  outside this gate.

### Cross-major contract fixture

```bash
npm run test:frontend-compatibility
```

The runner snapshots seven full, representative Aura sources declared in
`source-files.json`: the application shell, primary and light buttons, form
input, list table, value widget, and PHP status field. It verifies their
expected literal classes before copying them. Both compiler lanes scan that
same source snapshot plus the gate-only semantic probe. The audited source
snapshot contains seven files and uses the fixed, machine-readable expectation
in `source-baseline.json`.
Audited source SHA-256: `cc8ee06b18ad39bcb5d7974e13296b83ce32a7d6b6f337b8f15329e79dc37d40`.

The `aura-source-records-v2-length-prefixed` canonicalization starts with a
domain identifier and record count. Each manifest-ordered record contains the
length-prefixed UTF-8 path, class count, every length-prefixed expected class,
and length-prefixed exact file bytes. Lengths and counts are unsigned 64-bit
big-endian integers. The manifest schema permits only `path` and `classes`, so
no accepted metadata remains outside the digest. Source line endings are not
normalized.

The baselines, source and production manifests, and generated Vite manifest are
decoded as strict UTF-8 and parsed by a JSON parser that rejects duplicate
decoded member names before an object is constructed. The parser accepts at
most 1,048,576 bytes and 64 container levels; its file reader never buffers
more than the limit plus one byte. Regressions cover a duplicate at the deepest
accepted level and a 5,000-level input, which must fail deterministically with
`SyntaxError` instead of exhausting the JavaScript stack. All accepted string
fields must be well-formed Unicode; lone high or low surrogates fail before
UTF-8 digest encoding.

Every selected path must equal its canonical repository-relative realpath and
remain inside the real source root. Manifest paths are restricted to portable
ASCII before filesystem resolution, removing Unicode normalization identities
that vary by platform. Case-folded canonical paths and filesystem device/inode
identities must both be unique, preventing case variants, symlinks, and
hardlinks from adding aliases.

The exact authenticated buffers and semantic probe are captured after copying.
The runner records the `lstat` type, device/inode identity, link count, and
original mode of the capture root, every containing directory, and every file.
Files and directories are made read-only while each compiler runs. Every
positive and negative compiler invocation checks persistent identity, mode,
and content drift both before and after execution. Regression lanes replace a
file with an exact-byte hardlink, substitute the complete root with an
exact-copy symlink, and change a protected mode; all must fail even though a
content-only digest can remain unchanged.

Normal cleanup first validates the complete captured tree and restores the
exact original modes only when every recorded path still has its original type,
device/inode identity, and link count. If that validation fails, removal
preparation checks each recorded directory with `lstat` and restores only
directories whose recorded identities still match. A substituted path is never
passed to `chmod`; cleanup reports the drift, then normal non-keep runs attempt
removal of the isolated temporary tree without following substituted symlinks.
POSIX mode checks are defence in depth; Windows retains the persistent identity
and content checks.

This fixture is deterministic compatibility evidence, not a security boundary
against a malicious process running concurrently as the same OS user. A
same-UID process can race filesystem checks or mutate and restore bytes entirely
between checkpoints. Those transient attacks are explicitly out of scope. The
trust boundary is the dedicated CI job's fresh checkout and isolated process;
do not treat a run beside untrusted same-UID processes as authenticated build
provenance.

Before either compiler runs, the gate compares the selected real-source digest
to that committed baseline and checks this documented value for consistency.
Each compiler lane repeats the fixed-baseline check. A self-test mutates a
temporary copy of a selected source and proves the same check rejects drift.
Additional regressions reproduce the old record-boundary ambiguity and prove
the v2 digest separates it, then reject class value, count, and order mutations
using otherwise valid real-source expectations. Unexpected manifest fields are
rejected outright. Input regressions also cover ordinary and escaped duplicate
JSON members, nested duplicates, U+D800/U+D801 replacement-byte collisions,
portable-path violations, and case, symlink, hardlink, and root-escape aliases.
Intentional selected-source content changes therefore require a reviewed
baseline and documentation update; changing the selection also requires a
manifest update.

The assertions parse generated CSS with PostCSS. They compare declarations and
normalized values rather than checking output substrings. Coverage includes:

- exact light and distinct dark token values;
- semantic color and font mappings;
- legacy primary, sidebar, and dark-mode aliases;
- semantic and legacy alpha modifiers;
- utilities found in the real Blade and PHP sources; and
- absence of remote stylesheet and asset URLs.

`output-baseline.json` pins the semantic assertion count, exact byte count, and
SHA-256 digest for both positive lanes. The CSS contract reads each output once
and derives all three values from that same buffer. Any intentional compiler or
fixture change must therefore review and update the output baseline explicitly.

`production-output-baseline.json` separately pins all publishable files under
`resources/dist`, including the Vite manifest, stylesheet, JavaScript, and
source map. Both the worktree and stage-zero Git index must match that external
manifest exactly. An intentional production rebuild therefore requires an
explicitly reviewed baseline update as well as the rebuilt assets.

Current reproducible results (including CORE-24):

- Tailwind `3.4.19`: pass, 551 parsed assertions, 19,651 output bytes,
  SHA-256 `74a17638884f659441bff193b7d5edd06f8eb504217c40d312bfbd89d87cb318`.
- Tailwind `4.3.3` through Vite `8.2.1`: pass, 534 parsed assertions,
  23,503 output bytes, SHA-256
  `ed063b7f48b89de1589e9f81afc2f0d2f63ed1a19a137ea6227793eea843ca3b`.

The v4 lane copies only the committed isolated fixture and shared contract into
the temporary workspace, runs `npm ci` against its lockfile, and builds its
actual Vite HTML/CSS entrypoint. It never performs an unlocked package install
and does not alter Aura's root lockfile or runtime dependencies.

The `Frontend compatibility` job in `.github/workflows/run-tests.yml` installs
the locked root dependencies and runs this gate on every supported push and
pull request. That clean checkout and single-purpose job provide the isolation
assumed by the gate's threat model.

### Proved cross-major failures

The same npm script also proves both unsupported boundaries. Compiling the v4
entrypoint with Aura's v3 CLI exits `1` because v3 cannot resolve the v4
`tailwindcss` import. Building a source-verified extract of Aura's v3
entrypoint with the v4 Vite host exits `1` because the legacy
`tailwindcss/base`, `components`, and `utilities` subpaths are not v4 style
exports. Vite resolves these imports in parallel, so any one can be reported
first:

```text
Error: Failed to find 'tailwindcss'
"./<base|components|utilities>" is not exported under the conditions ["style", "production", "import"]
```

These failures are why CORE-24 must use the runtime variable bridge and keep
the compiler entrypoints separate.

### Relevant package tests

```bash
php -d memory_limit=1G vendor/bin/pest --no-coverage \
  tests/Feature/Aura/PublishCommandTest.php \
  tests/Feature/Aura/AuraLayoutCommandTest.php \
  tests/Feature/Aura/InstallationTest.php
```

Result: pass, 8 tests and 19 assertions. These tests cover asset publication,
the package-source layout path, and command registration.

## Preconditions and follow-ups

CORE-24 may proceed against this exact build shape, subject to these rules:

1. Install Composer and npm dependencies before package asset verification;
   Tailwind's package build scans both package and framework-owned source.
2. Keep Aura's production entrypoint on Tailwind 3 and implement the semantic
   runtime variables/config renderer first.
3. Rebuild and commit `resources/dist` with the token change; compare the
   resulting manifest, content hashes, and published asset behavior against the
   reproducible baseline above.
4. Run `npm run test:frontend-compatibility` after every token/config change.
5. Keep host Tailwind source out of Aura's package build and Aura source out of
   the host build.
6. Resolve the independent `build:lib` export failure and npm audit findings in
   dependency-scoped work; do not hide them inside the theme rollout.
7. The zero-byte `stubs/scaffold` frontend files are not an integration API and
   must not become the token source of truth.
