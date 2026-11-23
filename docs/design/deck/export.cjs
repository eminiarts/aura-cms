// Exports the deck to export/aura-design-guidelines.pdf and export/slide-NN.png (1920 x 1080).
//   node docs/design/deck/export.cjs [outDir] [--png-only]
// Needs index.html (run build.cjs first) and Playwright with Chromium.
const path = require('path');
const fs = require('fs');

function loadPlaywright() {
    const candidates = [
        path.join(__dirname, '../../../node_modules/playwright'),
        path.join(__dirname, '../../../node_modules/playwright-core'),
        '/Users/ivan/projects/aura-cms/node_modules/playwright',
        '/Users/ivan/projects/aura-cms/node_modules/playwright-core',
    ];
    for (const candidate of candidates) {
        if (fs.existsSync(candidate)) return require(candidate);
    }
    throw new Error('playwright not found. Run npm install in the repo or adjust the paths in export.cjs.');
}

(async () => {
    const { chromium } = loadPlaywright();
    const out = process.argv[2] && !process.argv[2].startsWith('--') ? path.resolve(process.argv[2]) : path.join(__dirname, 'export');
    fs.mkdirSync(out, { recursive: true });
    for (const old of fs.readdirSync(out).filter((f) => /^slide-\d+\.png$/.test(f))) fs.unlinkSync(path.join(out, old));

    const browser = await chromium.launch();
    const context = await browser.newContext({ viewport: { width: 1920, height: 1080 }, reducedMotion: 'reduce' });
    const page = await context.newPage();
    await page.goto('file://' + path.join(__dirname, 'index.html'));
    await page.evaluate(() => document.fonts.ready);
    await page.evaluate(() => Promise.all([...document.images].map((img) => (img.complete ? null : new Promise((r) => { img.onload = img.onerror = r; })))));
    await page.waitForTimeout(400);

    // Catch layout problems before anyone opens the PDF: text that leaves its slide, images that did not load.
    const problems = await page.evaluate(() => {
        const found = [];
        document.querySelectorAll('.slide').forEach((slide, i) => {
            const frame = slide.getBoundingClientRect();
            slide.querySelectorAll('h1, h2, h3, h4, p, li, td, th, span, b, figure, .body > *').forEach((el) => {
                const r = el.getBoundingClientRect();
                if (r.width && (r.right > frame.right + 1 || r.bottom > frame.bottom + 1)) found.push(`slide ${i + 1}: <${el.tagName.toLowerCase()} class="${el.className}"> runs past the slide edge`);
            });
            slide.querySelectorAll('img').forEach((img) => { if (!img.naturalWidth) found.push(`slide ${i + 1}: image ${img.getAttribute('src')} did not load`); });
            slide.querySelectorAll('.clip-check').forEach((el) => { if (el.scrollHeight > el.clientHeight + 1) found.push(`slide ${i + 1}: content clipped in .clip-check`); });
        });
        return [...new Set(found)];
    });
    for (const problem of problems) console.warn(`  check: ${problem}`);

    const slides = await page.$$('.slide');
    for (let i = 0; i < slides.length; i++) {
        await slides[i].screenshot({ path: path.join(out, `slide-${String(i + 1).padStart(2, '0')}.png`) });
    }
    if (!process.argv.includes('--png-only')) {
        await page.pdf({ path: path.join(out, 'aura-design-guidelines.pdf'), width: '1920px', height: '1080px', printBackground: true });
    }
    console.log(`${slides.length} slides -> ${path.relative(process.cwd(), out) || out}`);
    await browser.close();
})().catch((error) => {
    console.error(error.message || error);
    process.exit(1);
});
