// Assembles the Aura design deck: slides/*.html + deck.css -> index.html.
//   node docs/design/deck/build.cjs
// Then export to PDF and PNG with export.cjs. Screenshots come from capture.cjs.
//
// Placeholders a slide may use:
//   {{top:Chapter}}                  chapter label left, page number right
//   {{logo}}                         the Aura wordmark (inline SVG, takes the text colour)
//   {{mark}}                         one "A" of the wordmark, as large artwork
//   {{shot:id,light|dark}}           a screenshot from assets/screens/{id}-{mode}.png
//   {{shot:tabs|table,light}}        first id that has a screenshot wins (fallbacks for sections not on the page yet)
//   {{split:id}}                     the same section, light on the left half and dark on the right
//   {{shot:forms,light,rows-1-2}}    only rows 1 to 2 of the section's panels (rows come from index.json)
//   {{scale:primary}} {{scale:gray}} the default primary (aura) and gray (slate) scales, read from colors.blade.php
//   {{palettes}}                     every named palette, read from src/ThemeTokens.php
//   {{controls:light}}               measured control heights from assets/screens/controls.json
//   {{icon:name}}                    an Aura icon from resources/views/components/icon/{name}.blade.php
//   {{cr:#fg[,#bg]}}                 contrast ratio
const fs = require('fs');
const path = require('path');

const dir = __dirname;
const repo = path.resolve(dir, '../../..');
const read = (file) => fs.readFileSync(path.join(repo, file), 'utf8');
const warnings = [];

// ---- Logo: the wordmark from resources/views/components/application-logo.blade.php ----
const LOGO_PATHS = (() => {
    const blade = read('resources/views/components/application-logo.blade.php');
    const svg = blade.match(/<svg[\s\S]*?<\/svg>/)[0];
    return [...svg.matchAll(/d="([^"]+)"/g)].map((m) => m[1]);
})();
const logo = () => `<svg class="logo" viewBox="0 0 123 24" fill="currentColor" role="img" aria-label="Aura">${LOGO_PATHS.map((d) => `<path d="${d}"/>`).join('')}</svg>`;
// The first "A" of the wordmark (the last path), used as artwork.
const mark = () => `<svg class="mark" viewBox="0 0 26.78 24" preserveAspectRatio="xMidYMax meet" aria-hidden="true"><path d="${LOGO_PATHS[LOGO_PATHS.length - 1]}"/></svg>`;

// ---- Screenshots ----
const screens = path.join(dir, 'assets/screens');
const index = fs.existsSync(path.join(screens, 'index.json')) ? JSON.parse(fs.readFileSync(path.join(screens, 'index.json'), 'utf8')) : { modes: {} };
const ground = (mode) => (index.modes[mode] && index.modes[mode].background) || (mode === 'dark' ? 'rgb(15, 23, 42)' : '#fff');
function pngSize(file) {
    const buffer = fs.readFileSync(file);
    return { w: buffer.readUInt32BE(16), h: buffer.readUInt32BE(20) };
}
function pick(ids, mode) {
    return ids.split('|').find((c) => fs.existsSync(path.join(screens, `${c}-${mode}.png`)));
}
const pending = (ids, mode) => {
    warnings.push(`no screenshot for ${ids} (${mode})`);
    return `<div class="frame"><figure class="shot missing ${mode}"><div><b>Screenshot pending</b><span>#${ids.split('|')[0]} is not on the style guide page yet. Run capture.cjs after it lands.</span></div></figure></div>`;
};
const standIn = (ids, id) => (id !== ids.split('|')[0] ? ` data-fallback="Stand-in: #${ids.split('|')[0]} is not on the page yet"` : '');
// Part of a section: "rows-2" or "rows-1-3" keeps those rows of panels (measured by capture.cjs).
// Returns the crop in image pixels, or null for the whole picture.
function crop(id, extra, size) {
    const m = /\brows-(\d+)(?:-(\d+))?\b/.exec(extra || '');
    if (!m) return null;
    const section = (index.sections || []).find((s) => s.id === id);
    if (!section || !section.rows || !section.rows.length) {
        warnings.push(`${id}: no row data for ${m[0]}, showing the whole section`);
        return null;
    }
    const first = Math.max(1, +m[1]);
    const last = Math.min(section.rows.length, +(m[2] || m[1]));
    if (first > section.rows.length) {
        warnings.push(`${id}: ${m[0]} but the section has ${section.rows.length} rows, showing the whole section`);
        return null;
    }
    // Up to 16 px of air around the rows, but never more than half the gap to a neighbouring row.
    const rows = section.rows;
    const above = first > 1 ? Math.max(0, (rows[first - 1].top - rows[first - 2].bottom) / 2) : 16;
    const below = last < rows.length ? Math.max(0, (rows[last].top - rows[last - 1].bottom) / 2) : 16;
    const scale = size.w / section.width;
    const top = Math.max(0, rows[first - 1].top - Math.min(16, above)) * scale;
    const bottom = Math.min(section.height, rows[last - 1].bottom + Math.min(16, below)) * scale;
    return { top, h: bottom - top };
}
const imgStyle = (c, size) => (c ? ` style="top:${(-c.top / c.h * 100).toFixed(3)}%;height:${(size.h / c.h * 100).toFixed(3)}%"` : '');
const fitStyle = (w, h, maxScale) => `--ar:${w}/${Math.round(h)};--arn:${(w / h).toFixed(4)}${maxScale ? `;max-width:${Math.round(w * maxScale)}px` : ''}`;

// A screenshot sized to the space it gets, never letterboxed: the frame is a size container and
// the figure takes the largest box with the picture's aspect ratio that fits in it. Stacked frames
// grow in proportion to their picture heights, so stacked pictures share one scale.
function shot(ids, mode, extra = '') {
    const id = pick(ids, mode);
    if (!id) return pending(ids, mode);
    const size = pngSize(path.join(screens, `${id}-${mode}.png`));
    const c = crop(id, extra, size);
    return `<div class="frame ${extra}" style="flex-grow:${Math.round(c ? c.h : size.h)}"><div class="fit" style="${fitStyle(size.w, c ? c.h : size.h, 0.8)}"><figure class="shot ${mode}" style="background:${ground(mode)}"${standIn(ids, id)}><img src="assets/screens/${id}-${mode}.png" alt="${id}, ${mode} mode"${imgStyle(c, size)}></figure></div></div>`;
}
// Light on the left half, dark on the right half of the same section.
function split(ids, extra = '') {
    const id = pick(ids, 'light');
    if (!id || !fs.existsSync(path.join(screens, `${id}-dark.png`))) return pending(ids, 'light');
    const light = pngSize(path.join(screens, `${id}-light.png`));
    const dark = pngSize(path.join(screens, `${id}-dark.png`));
    if (light.w !== dark.w || Math.abs(light.h - dark.h) > 8) warnings.push(`${id}: light ${light.w}x${light.h} and dark ${dark.w}x${dark.h} differ; the split may not line up`);
    const c = crop(id, extra, light);
    return `<div class="frame captioned ${extra}" style="flex-grow:${Math.round((c ? c.h : light.h) + 80)}"><div class="fit" style="${fitStyle(light.w, c ? c.h : light.h, 0.8)}"><figure class="shot split" style="background:linear-gradient(90deg, ${ground('light')} 50%, ${ground('dark')} 50%)"${standIn(ids, id)}><img src="assets/screens/${id}-light.png" alt="${id}, light mode"${imgStyle(c, light)}><img class="half" src="assets/screens/${id}-dark.png" alt="${id}, dark mode"${imgStyle(c, light)}></figure><span class="mode-l">Light</span><span class="mode-d">Dark</span></div></div>`;
}

// ---- Colour scales, read from the theme sources ----
const hex = (rgb) => '#' + rgb.trim().split(/\s+/).map((n) => (+n).toString(16).padStart(2, '0')).join('');
function primaryScale() {
    const blade = read('resources/views/components/layout/colors.blade.php');
    const block = blade.slice(blade.indexOf("'aura' => ["), blade.indexOf("'red' => ["));
    return [...block.matchAll(/'primary-(\d+)' => TransformColor::hexToRgb\('(#[0-9a-fA-F]{6})'\)/g)].map((m) => [m[1], m[2].toLowerCase()]);
}
function grayScale() {
    const blade = read('resources/views/components/layout/colors.blade.php');
    const start = blade.indexOf("$grayColorPalettes = [");
    const block = blade.slice(blade.indexOf("'slate' => [", start), blade.indexOf("'dark-slate' => [", start));
    return [...block.matchAll(/'gray-(\d+)' => '([\d ]+)'/g)].map((m) => [m[1], hex(m[2])]);
}
function luminance(h) {
    const [r, g, b] = [1, 3, 5].map((i) => parseInt(h.slice(i, i + 2), 16) / 255).map((v) => (v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4));
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}
function contrast(fg, bg = '#ffffff') {
    const [a, b] = [luminance(fg), luminance(bg)];
    return ((Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05)).toFixed(1);
}
const ROLES = {
    primary: { 50: 'Tints, selected rows', 100: 'Badges', 500: 'Focus ring', 600: 'Primary action', 700: 'Sidebar', 400: 'Links in dark mode' },
    gray: { 50: 'Quiet fills', 200: 'Lines', 400: 'Placeholders', 500: 'Secondary text', 800: 'Dark panels', 900: 'Text, dark page' },
};
function scale(name) {
    const shades = name === 'primary' ? primaryScale() : grayScale();
    return `<div class="scale">${shades.filter(([s]) => s !== '25').map(([shade, color]) => {
        const light = contrast(color) < 4.5;
        return `<div class="sw" style="background:${color};color:${light ? '#0f172a' : '#fff'}">
            <span class="role">${ROLES[name][shade] || ''}</span>
            <span class="n">${shade}</span><span class="h">${color}</span><span class="h">${contrast(color)}:1</span></div>`;
    }).join('')}</div>`;
}

// ---- Named palettes, read from src/ThemeTokens.php ----
function palettes() {
    const php = read('src/ThemeTokens.php');
    const block = php.slice(php.indexOf('PALETTE_EXPERT_COLORS = ['), php.indexOf('];', php.indexOf('PALETTE_EXPERT_COLORS = [')));
    const rows = [...block.matchAll(/'([\w-]+)' => \['primary' => '([\d ]+)', 'background' => '([\d ]+)', 'hover' => '([\d ]+)', 'dropdown' => '([\d ]+)', 'text' => '([\d ]+)', 'icon' => '([\d ]+)'/g)];
    return `<div class="palettes">${rows.map(([, name, primary, background, , dropdown, text, icon]) => `
        <div class="pal${name === 'aura' ? ' on' : ''}">
            <div class="chips"><i style="background:${hex(dropdown)}"></i><i style="background:${hex(background)}"></i><i style="background:${hex(primary)}"></i><i style="background:${hex(text)}"></i><i style="background:${hex(icon)}"></i></div>
            <span>${name}</span>
        </div>`).join('')}</div>`;
}

// ---- Measured control heights ----
function controls(mode) {
    const file = path.join(screens, 'controls.json');
    if (!fs.existsSync(file)) {
        warnings.push('no controls.json: run capture.cjs');
        return '<span class="quiet">Run capture.cjs to measure.</span>';
    }
    const data = JSON.parse(fs.readFileSync(file, 'utf8'))[mode];
    const row = (label, item) => `<div class="m-row"><b>${label}</b><span>${item.heights.map((h) => `<i>${h}</i>`).join('')}</span><em>${item.count} on the page</em></div>`;
    return `<div class="measured">${row('Inputs', data.input)}${row('Selects', data.select)}${row('Buttons', data.button)}</div>`;
}

// ---- Icons: the real Aura icon files ----
function icon(name) {
    const file = `resources/views/components/icon/${name}.blade.php`;
    if (!fs.existsSync(path.join(repo, file))) {
        warnings.push(`no icon ${name}`);
        return '';
    }
    return read(file).match(/<svg[\s\S]*?<\/svg>/)[0].replace(/\{\{[^}]*\}\}/g, '').replace('<svg', '<svg class="ic"');
}

// ---- Assemble ----
const slides = fs.readdirSync(path.join(dir, 'slides')).filter((f) => f.endsWith('.html')).sort()
    .map((f) => fs.readFileSync(path.join(dir, 'slides', f), 'utf8')).join('\n');

const body = slides
    .replace(/\{\{top:([^}]+)\}\}/g, (_, c) => `<header class="top"><span class="chap">${c}</span><span class="num"></span></header>`)
    .replace(/\{\{logo\}\}/g, () => logo())
    .replace(/\{\{mark\}\}/g, () => mark())
    .replace(/\{\{shot:([\w|-]+),(light|dark)(?:,([\w -]+))?\}\}/g, (_, ids, mode, extra) => shot(ids, mode, extra))
    .replace(/\{\{split:([\w|-]+)(?:,([\w -]+))?\}\}/g, (_, ids, extra) => split(ids, extra))
    .replace(/\{\{scale:(primary|gray)\}\}/g, (_, name) => scale(name))
    .replace(/\{\{palettes\}\}/g, () => palettes())
    .replace(/\{\{controls:(light|dark)\}\}/g, (_, mode) => controls(mode))
    .replace(/\{\{icon:([\w-]+)\}\}/g, (_, name) => icon(name))
    .replace(/\{\{cr:(#[0-9a-fA-F]{6})(?:,(#[0-9a-fA-F]{6}))?\}\}/g, (_, fg, bg) => contrast(fg, bg));

const unknown = body.match(/\{\{[^}]+\}\}/g);
if (unknown) {
    console.error('Unresolved placeholders:', [...new Set(unknown)].join(', '));
    process.exit(1);
}

const html = `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Aura design guidelines, v1</title>
<link rel="stylesheet" href="deck.css">
</head>
<body>
<main class="deck">
${body}
</main>
<script>
// Slides are drawn at 1920 x 1080 and scaled to the window width.
const fit = () => document.documentElement.style.setProperty('--fit', Math.min(1, innerWidth / 1920));
fit(); addEventListener('resize', fit);
</script>
</body>
</html>
`;
fs.writeFileSync(path.join(dir, 'index.html'), html);
console.log(`index.html: ${(body.match(/<section class="slide/g) || []).length} slides, ${(html.length / 1024).toFixed(0)} KB`);
for (const warning of warnings) console.warn(`  warning: ${warning}`);
