# Aura design guidelines deck

A slide deck (1920 × 1080) that summarises the style guide page (`/admin/styleguide`) for review. The page is the source of truth; this deck is rebuilt from it.

```bash
node docs/design/deck/capture.cjs   # 1. screenshots of the style guide page
node docs/design/deck/build.cjs     # 2. slides/*.html + deck.css -> index.html
node docs/design/deck/export.cjs    # 3. index.html -> export/aura-design-guidelines.pdf + export/slide-NN.png
```

## What each step needs

**capture.cjs** signs in through the local Dusk route (`/_dusk/login/1`) on `http://aura-demo-style-guide.test` and screenshots every `section[id]` of `/admin/styleguide` in light and dark mode (2× scale). It writes `assets/screens/{id}-{light|dark}.png`, a few open states (`menus-open`, `overlays-modal`, `overlays-slideover`), `index.json` (sections, the rows of panels in each, page backgrounds) and `controls.json` (measured input, select and button heights).

- Needs Playwright with Chromium. It uses the repo's `node_modules/playwright` if present, otherwise `/Users/ivan/projects/aura-cms/node_modules/playwright`.
- Needs the preview host running with the Dusk login route enabled.
- Read-only: it opens pages and clicks only client-side triggers (a dropdown, two dialogs). It never submits a form or changes a setting.
- The host's own theme is replaced in the captured page by Aura's package defaults (aura palette, slate gray, comfortable, rounded, standard sidebar), so the deck shows the default look. `--theme=site` keeps the host's theme. `--only=buttons,forms` re-captures some sections. `--base=http://…` points at another host.
- Dark mode uses `?mode=dark`; if the page ignores it, the script adds the `dark` class to `<html>`.

**build.cjs** needs only Node. It reads the slides, the screenshots and a few sources in the repo (`application-logo.blade.php` for the logo, `layout/colors.blade.php` for the colour scales, `src/ThemeTokens.php` for the palettes, `components/icon/*` for icons). It fails on an unknown placeholder and warns about missing screenshots, which render as a dashed "Screenshot pending" box. Placeholders are listed at the top of the file; the main ones are `{{top:Chapter}}`, `{{logo}}`, `{{shot:id,light|dark[,rows-1-2]}}` and `{{split:id[,rows-2]}}` (light on the left half, dark on the right).

**export.cjs** needs Playwright (same lookup as capture). It prints a warning for text that runs past a slide edge or an image that did not load. Output goes to `export/`, which is not committed.

The deck is set in Inter from `resources/public/fonts/inter`, so it renders the same offline on any machine. The type-scale slide uses the system stack, which is the admin's default font.
