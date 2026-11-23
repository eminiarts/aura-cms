// Screenshots every section of the live style guide, in light and dark mode, for the deck.
//   node docs/design/deck/capture.cjs [--base=http://aura-demo-style-guide.test] [--only=buttons,forms] [--theme=site]
//
// Writes assets/screens/{id}-{light|dark}.png (2x) for every <section id> on /admin/styleguide,
// plus assets/screens/index.json (ids, titles, sizes) and assets/screens/controls.json (measured
// heights of inputs, selects and buttons). Re-run it whenever the style guide page changes.
//
// Read-only: it signs in through the local Dusk route and only opens pages. It never submits a
// form or changes a setting (the preview host shares its database with another local app).
const fs = require('fs');
const path = require('path');

function loadPlaywright() {
    const candidates = [
        path.join(__dirname, '../../../node_modules/playwright'), // worktree node_modules (or its symlink)
        path.join(__dirname, '../../../node_modules/playwright-core'),
        '/Users/ivan/projects/aura-cms/node_modules/playwright', // absolute fallback
        '/Users/ivan/projects/aura-cms/node_modules/playwright-core',
    ];
    for (const candidate of candidates) {
        if (fs.existsSync(candidate)) return require(candidate);
    }
    throw new Error('playwright not found. Run npm install in the repo or adjust the paths in capture.cjs.');
}

const arg = (name, fallback) => {
    const hit = process.argv.find((a) => a.startsWith(`--${name}=`));
    return hit ? hit.slice(name.length + 3) : fallback;
};

const BASE = arg('base', 'http://aura-demo-style-guide.test').replace(/\/$/, '');
const ONLY = arg('only', '') ? arg('only', '').split(',') : null;
// The preview host may run a non-default theme (palette, radius, density). By default the capture
// pins Aura's package defaults on the client so the deck shows the default look; --theme=site keeps
// whatever the host has. Nothing is saved: it is a <style> tag in the captured page only.
const KEEP_SITE_THEME = arg('theme', 'default') === 'site';

const DEFAULT_THEME_CSS = `
html:root, html:root.dark, html:root .dark {
    --primary-25: 251 254 255 !important; --primary-50: 240 244 254 !important; --primary-100: 224 234 253 !important; --primary-200: 194 213 251 !important;
    --primary-300: 149 181 249 !important; --primary-400: 104 148 246 !important; --primary-500: 60 115 242 !important; --primary-600: 31 85 233 !important;
    --primary-700: 22 67 215 !important; --primary-800: 18 54 185 !important; --primary-900: 14 42 150 !important; --primary-950: 8 28 107 !important;
    --gray-25: 252 252 253 !important; --gray-50: 249 250 251 !important; --gray-100: 243 244 246 !important; --gray-200: 229 231 235 !important;
    --gray-300: 209 213 219 !important; --gray-400: 156 163 175 !important; --gray-500: 107 114 128 !important; --gray-600: 75 85 99 !important;
    --gray-700: 51 65 85 !important; --gray-800: 30 41 59 !important; --gray-900: 15 23 42 !important; --gray-950: 2 6 23 !important;
    --sidebar-bg: var(--primary-700) !important; --sidebar-bg-hover: var(--primary-600) !important; --sidebar-bg-dropdown: var(--primary-800) !important;
    --sidebar-text: var(--primary-400) !important; --sidebar-icon: var(--primary-300) !important; --sidebar-icon-hover: var(--primary-200) !important;
    --aura-panel-padding: 1.25rem !important; --aura-panel-small-padding: 0.5rem !important; --aura-layout-gap: 1.5rem !important; --aura-section-gap: 1rem !important;
    --aura-panel-radius: 0.75rem !important; --aura-control-radius: 0.5rem !important;
    --aura-sidebar-item-height: 2.25rem !important; --aura-sidebar-item-padding-x: 0.75rem !important; --aura-sidebar-icon-size: 1.25rem !important;
    --aura-sidebar-group-gap: 0.25rem !important; --aura-sidebar-heading-gap: 1.25rem !important;
    --aura-color-primary: var(--primary-600) !important;
}
html:root:not(.dark) {
    --aura-color-background: 255 255 255 !important; --aura-color-panel: 250 250 250 !important; --aura-color-border: 228 228 231 !important;
    --aura-color-text: 24 24 27 !important; --aura-color-muted: 82 82 91 !important;
    --aura-color-success: 22 163 74 !important; --aura-color-warning: 217 119 6 !important; --aura-color-danger: 220 38 38 !important;
}
html:root.dark, html:root .dark {
    --aura-color-background: 9 9 11 !important; --aura-color-panel: 24 24 27 !important; --aura-color-border: 63 63 70 !important;
    --aura-color-text: 244 244 245 !important; --aura-color-muted: 161 161 170 !important;
    --aura-color-success: 22 163 74 !important; --aura-color-warning: 217 119 6 !important; --aura-color-danger: 220 38 38 !important;
}`;
const OUT = path.join(__dirname, 'assets/screens');

(async () => {
    const { chromium } = loadPlaywright();
    fs.mkdirSync(OUT, { recursive: true });

    // A fulfilled (rewritten) response loses Chrome's local-network trust, which then blocks the
    // page's own CSS and JS on the .test host; these flags keep the local host loading normally.
    const browser = await chromium.launch({
        args: ['--disable-features=LocalNetworkAccessChecks,BlockInsecurePrivateNetworkRequests,PrivateNetworkAccessSendPreflights,PrivateNetworkAccessRespectPreflightResults'],
    });
    const context = await browser.newContext({
        viewport: { width: 1440, height: 1000 },
        deviceScaleFactor: 2,
        reducedMotion: 'reduce',
    });
    // Pin the default theme in the style guide's HTML, after the theme's own <style>, so it wins and
    // scripts that read the variables (the charts) see the defaults too. Only this browser's copy of
    // the response changes; nothing is saved on the host.
    if (!KEEP_SITE_THEME) {
        await context.route((url) => url.pathname.endsWith('/admin/styleguide'), async (route) => {
            const response = await route.fetch();
            const html = (await response.text()).replace('</head>', `<style id="deck-default-theme">${DEFAULT_THEME_CSS}</style></head>`);
            await route.fulfill({ response, body: html });
        });
    }
    const page = await context.newPage();

    // Sign in once; the context keeps the session cookie.
    const login = await page.goto(`${BASE}/_dusk/login/1`);
    if (!login || login.status() >= 400) throw new Error(`Login route answered ${login && login.status()}`);

    const index = { base: BASE, capturedAt: new Date().toISOString(), theme: KEEP_SITE_THEME ? 'site' : 'package defaults', modes: {}, sections: [] };
    const controls = {};

    for (const mode of ['light', 'dark']) {
        // Prefer the page's own ?mode=dark; fall back to toggling the class on <html>.
        const url = `${BASE}/admin/styleguide${mode === 'dark' ? '?mode=dark' : '?mode=light'}`;
        await page.emulateMedia({ colorScheme: mode, reducedMotion: 'reduce' });
        const response = await page.goto(url, { waitUntil: 'networkidle' });
        if (!response || response.status() >= 400) throw new Error(`${url} answered ${response && response.status()}`);
        if (new URL(page.url()).pathname.includes('login')) throw new Error('Not signed in: the Dusk login route did not create a session.');

        const isDark = await page.evaluate(() => document.documentElement.classList.contains('dark'));
        let how = 'query';
        if (mode === 'dark' && !isDark) {
            await page.evaluate(() => { document.documentElement.classList.remove('light'); document.documentElement.classList.add('dark'); });
            how = 'class';
        }
        if (mode === 'light' && isDark) {
            await page.evaluate(() => { document.documentElement.classList.remove('dark'); document.documentElement.classList.add('light'); });
            how = 'class';
        }
        // The colour behind the sections, so the deck can frame each picture on the same ground.
        const background = await page.evaluate(() => {
            for (let el = document.querySelector('section[id]'); el; el = el.parentElement) {
                const bg = getComputedStyle(el).backgroundColor;
                if (bg && bg !== 'transparent' && !/rgba\(.*, 0\)$/.test(bg)) return bg;
            }
            return getComputedStyle(document.body).backgroundColor;
        });
        index.modes[mode] = { via: how, background };

        // Calm the page for stills: no sticky bars over the content, no transitions, no caret.
        await page.addStyleTag({ content: `
            *, *::before, *::after { transition: none !important; animation-duration: 0s !important; caret-color: transparent !important; }
            section[id] ~ * nav.sticky, nav.sticky { position: static !important; }
            #herd-ai-host { display: none !important; }
        ` });
        await page.evaluate(() => document.fonts.ready);
        await page.waitForTimeout(500);

        const sections = await page.$$eval('section[id]', (els) => els.map((el) => ({
            id: el.id,
            title: (el.querySelector('h2, h3') || {}).textContent?.trim() || el.id,
        })));

        // The admin scrolls inside its main column, not the window, so the viewport has to be tall
        // enough for the tallest section; each section is then scrolled to the top and clipped.
        const tallest = await page.$$eval('section[id]', (els) => Math.max(...els.map((el) => el.getBoundingClientRect().height)));
        await page.setViewportSize({ width: 1440, height: Math.ceil(tallest) + 160 });
        await page.waitForTimeout(300);

        for (const { id, title } of sections) {
            if (ONLY && !ONLY.includes(id)) continue;
            const el = await page.$(`section[id="${id}"]`);
            // Leave out the section header (number, title, intro): the slide has its own headline.
            // The picture starts at the first child that is not a heading or a paragraph.
            const top = await el.evaluate((node) => {
                node.style.scrollMarginTop = '40px';
                node.scrollIntoView({ block: 'start' });
                const body = [...node.children].find((child) => !['SPAN', 'H1', 'H2', 'H3', 'H4', 'P'].includes(child.tagName));
                const start = body ? body.getBoundingClientRect().top : node.getBoundingClientRect().top + (parseFloat(getComputedStyle(node).paddingTop) || 0);
                return start - node.getBoundingClientRect().top;
            });
            await page.waitForTimeout(150);
            const box = await el.boundingBox();
            const file = `${id}-${mode}.png`;
            const pad = 16;
            await page.screenshot({
                path: path.join(OUT, file),
                clip: { x: box.x - pad, y: box.y + top - pad, width: box.width + pad * 2, height: box.height - top + pad * 2 },
            });
            const padTop = top;
            // Rows of panels, in picture pixels (CSS px), so the deck can show one row on its own.
            const rows = await el.evaluate((node, { start, pad }) => {
                const origin = node.getBoundingClientRect().top + start - pad;
                // Everything after the header, including closing notes, so a crop never shows half of one.
                const children = [...node.children];
                const first = children.findIndex((child) => !['SPAN', 'H1', 'H2', 'H3', 'H4', 'P'].includes(child.tagName));
                const body = first === -1 ? [] : children.slice(first);
                // A wrapper has no surface of its own (no fill, ring or border); look through it to its panels.
                const isWrapper = (el) => {
                    const cs = getComputedStyle(el);
                    return el.children.length > 0 && cs.boxShadow === 'none' && parseFloat(cs.borderTopWidth) === 0
                        && (cs.backgroundColor === 'transparent' || /, 0\)$/.test(cs.backgroundColor));
                };
                const expand = (el, depth) => (depth < 3 && isWrapper(el) ? [...el.children].flatMap((child) => expand(child, depth + 1)) : [el]);
                const parts = body.flatMap((child) => expand(child, 0));
                const found = [];
                for (const part of parts) {
                    const r = part.getBoundingClientRect();
                    if (!r.height) continue;
                    const row = found.find((f) => Math.abs(f.top - (r.top - origin)) < 3);
                    if (row) row.bottom = Math.max(row.bottom, r.bottom - origin);
                    else found.push({ top: r.top - origin, bottom: r.bottom - origin });
                }
                return found.sort((a, b) => a.top - b.top).map((f) => ({ top: Math.round(f.top), bottom: Math.round(f.bottom) }));
            }, { start: top, pad });
            if (mode === 'light') index.sections.push({ id, title, width: Math.round(box.width + pad * 2), height: Math.round(box.height - padTop + pad * 2), rows });
            console.log(`${file}  ${Math.round(box.width)}x${Math.round(box.height - padTop)}`);
        }
        await page.setViewportSize({ width: 1440, height: 1000 });

        // Open states that only exist after a click. All of them are client-side (Alpine): nothing is sent.
        await page.setViewportSize({ width: 1440, height: 900 });
        const clickByText = async (sectionId, text) => {
            const trigger = page.locator(`section[id="${sectionId}"]`).getByRole('button', { name: text, exact: false }).first();
            if (!(await trigger.count())) return false;
            await trigger.scrollIntoViewIfNeeded();
            await trigger.click();
            await page.waitForTimeout(400);
            return true;
        };
        const extras = [
            { file: `menus-open-${mode}.png`, section: 'menus', text: 'Actions', shot: 'section' },
            { file: `overlays-modal-${mode}.png`, section: 'overlays', text: 'Open modal', shot: 'viewport' },
            { file: `overlays-slideover-${mode}.png`, section: 'overlays', text: 'Open slide-over', shot: 'viewport' },
        ];
        for (const extra of extras) {
            if (ONLY && !ONLY.includes(extra.section)) continue;
            // Room below the section, so an opened menu hangs over blank page instead of the next section.
            if (extra.shot === 'section') await page.locator(`section[id="${extra.section}"]`).evaluate((node) => { node.style.paddingBottom = '240px'; });
            if (!(await clickByText(extra.section, extra.text))) {
                console.log(`skip ${extra.file}: no "${extra.text}" button in #${extra.section}`);
                continue;
            }
            if (extra.shot === 'viewport') {
                await page.screenshot({ path: path.join(OUT, extra.file) });
            } else {
                // The section body plus whatever opened out of it (a menu hangs below the card).
                const clip = await page.locator(`section[id="${extra.section}"]`).evaluate((node) => {
                    const body = [...node.children].filter((child) => !['SPAN', 'H1', 'H2', 'H3', 'H4', 'P'].includes(child.tagName));
                    const rects = [...node.querySelectorAll('*')].filter((el) => el.offsetParent !== null).map((el) => el.getBoundingClientRect());
                    const box = node.getBoundingClientRect();
                    const top = body.length ? body[0].getBoundingClientRect().top : box.top;
                    const bottom = Math.max(...rects.map((r) => r.bottom), ...body.map((el) => el.getBoundingClientRect().bottom));
                    return { x: box.left - 16, y: top - 16, width: box.width + 32, height: bottom - top + 32 };
                });
                await page.screenshot({ path: path.join(OUT, extra.file), clip });
            }
            console.log(extra.file);
            await page.locator(`section[id="${extra.section}"]`).evaluate((node) => { node.style.paddingBottom = ''; });
            await page.keyboard.press('Escape');
            await page.mouse.click(4, 4);
            await page.waitForTimeout(400);
        }

        // Measure the controls that should share one height.
        controls[mode] = await page.evaluate(() => {
            const h = (el) => Math.round(el.getBoundingClientRect().height * 10) / 10;
            const pick = (selector) => [...document.querySelectorAll(selector)].filter((el) => el.offsetParent !== null);
            const summary = (els) => {
                const heights = [...new Set(els.map(h))].sort((a, b) => a - b);
                return { count: els.length, heights };
            };
            return {
                input: summary(pick('section input[type="text"], section input[type="email"], section input[type="number"], section input[type="date"], section input[type="time"]')),
                select: summary(pick('section select')),
                button: summary(pick('section button.aura-button, section a.aura-button')),
            };
        });
    }

    const indexFile = path.join(OUT, 'index.json');
    if (ONLY && fs.existsSync(indexFile)) {
        // A partial run keeps the other sections of the last full run.
        const previous = JSON.parse(fs.readFileSync(indexFile, 'utf8'));
        index.sections = [...previous.sections.filter((s) => !ONLY.includes(s.id)), ...index.sections];
    }
    fs.writeFileSync(indexFile, JSON.stringify(index, null, 2) + '\n');
    if (!ONLY) fs.writeFileSync(path.join(OUT, 'controls.json'), JSON.stringify(controls, null, 2) + '\n');
    console.log(`${index.sections.length} sections x 2 modes -> ${path.relative(process.cwd(), OUT)} (dark via ${index.modes.dark.via})`);
    await browser.close();
})().catch((error) => {
    console.error(error.message || error);
    process.exit(1);
});
