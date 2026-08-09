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

The reference Tailwind 4 host at commit
`c46654793eb36d91fb56f40297237978f91c0373` locked Tailwind CSS and
`@tailwindcss/vite` `4.3.3`, PostCSS `8.5.26`, Vite `8.2.1`, and
laravel-vite-plugin `3.1.3`. Its host CSS is already a separate Vite input,
which validates the integration shape above.

## Reproducible evidence

Fixtures live in `tests/Fixtures/FrontendCompatibility`. Generated CSS belongs
in a temporary directory and is not committed.

### Package install and build

```bash
npm ci
npm ls --depth=0 tailwindcss postcss vite laravel-vite-plugin autoprefixer postcss-import

AURA_GATE_OUT=$(mktemp -d /tmp/aura-frontend-pre-main.XXXXXX)
npm run build -- --outDir "$AURA_GATE_OUT" --emptyOutDir

AURA_GATE_LIB_OUT=$(mktemp -d /tmp/aura-frontend-pre-lib.XXXXXX)
npm run build:lib -- --outDir "$AURA_GATE_LIB_OUT" --emptyOutDir
```

Results on the audit snapshot:

- `npm ci`: pass; 190 packages installed.
- Main build: pass; Vite `8.1.5`, 158 modules transformed, CSS 212,231
  bytes and JavaScript 267,729 bytes before gzip.
- The fresh CSS was `app-BvIzBNVo.css`, SHA-256
  `61093ab67d0d115cbebbd2b393bde1e8e147727f753791bbd47637c76c7aa2b1`.
  The tracked manifest references `app-BzQlU9Hi.css` (213,714 bytes,
  SHA-256
  `ac245d6802619eaa6bc8549be8dcc0164909224663931e9dde4c3f2bc4913e08`).
  The committed main CSS is therefore not reproducible from the current lock.
- Library build: fail during Vite resolution because
  `monaco-themes` does not export `./themes/GitHub Dark.json` for the active
  production import conditions. This is independent of Tailwind but blocks a
  fully green package asset build.
- `npm audit`: three known development-tree findings (one moderate, two high):
  PostCSS, nanoid, and brace-expansion. Dependency updates were deliberately
  outside this gate.

### Tailwind 3 contract fixture

```bash
AURA_V3_OUT=$(mktemp -d /tmp/aura-tailwind-v3.XXXXXX)
./node_modules/.bin/tailwindcss \
  -c tests/Fixtures/FrontendCompatibility/tailwind-v3.config.cjs \
  -i tests/Fixtures/FrontendCompatibility/tailwind-v3.css \
  -o "$AURA_V3_OUT/output.css" \
  --minify
node tests/Fixtures/FrontendCompatibility/assert-output.mjs \
  "$AURA_V3_OUT/output.css"
```

Tailwind `3.4.19`: pass; minified output 7,856 bytes. The output contains the
shared runtime variables, semantic utilities including opacity, legacy
`primary-*`/`sidebar-*` utilities, `font-sans`, and the `.dark` selector
variant.

### Tailwind 4 contract fixture

Use an isolated directory so Aura's v3 `node_modules` cannot satisfy imports:

```bash
AURA_V4_DIR=$(mktemp -d /tmp/aura-tailwind-v4.XXXXXX)
cp tests/Fixtures/FrontendCompatibility/{assert-output.mjs,representative.html,token-contract.css,tailwind-v4.css,tailwind-v3-source-under-v4.css} "$AURA_V4_DIR"
npm install --prefix "$AURA_V4_DIR" --no-package-lock --no-save \
  tailwindcss@4.3.3 @tailwindcss/cli@4.3.3

cd "$AURA_V4_DIR"
./node_modules/.bin/tailwindcss -i tailwind-v4.css -o output.css --minify
node assert-output.mjs output.css
```

Tailwind `4.3.3`: pass; minified output 8,329 bytes. The output contains the
same runtime variables and representative utility names, including opacity,
with dark utilities scoped through `.dark`.

### Proved cross-major failures

Compiling the v4 fixture with Aura's v3 CLI exits `1`:

```text
Error: Failed to find 'tailwindcss'
```

Compiling the representative Aura v3 source entry with the isolated v4 CLI
also exits `1`:

```text
"./base" is not exported under the condition "style" from package tailwindcss
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

1. Keep Aura's production entrypoint on Tailwind 3 and implement the semantic
   runtime variables/config renderer first.
2. Rebuild and commit `resources/dist` with the token change; verify the
   resulting manifest and published asset behavior. Do not treat the current
   tracked CSS as a reproducible baseline.
3. Run both compatibility fixtures after every token/config change.
4. Keep host Tailwind source out of Aura's package build and Aura source out of
   the host build.
5. Resolve the independent `build:lib` export failure and npm audit findings in
   dependency-scoped work; do not hide them inside the theme rollout.
6. The zero-byte `stubs/scaffold` frontend files are not an integration API and
   must not become the token source of truth.
