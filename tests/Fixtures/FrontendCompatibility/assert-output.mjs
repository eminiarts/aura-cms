import fs from 'node:fs';

const outputPath = process.argv[2];

if (!outputPath) {
    throw new Error('Pass the compiled CSS path as the first argument.');
}

const css = fs.readFileSync(outputPath, 'utf8');
const expectedFragments = [
    '.font-sans',
    '.bg-aura-primary',
    '.bg-aura-panel\\/80',
    '.border-aura-border',
    '.text-aura-muted',
    '.text-aura-success',
    '.text-aura-warning',
    '.text-aura-danger',
    '.hover\\:bg-primary-500',
    '.bg-sidebar-bg',
    '.text-sidebar-text',
    '.dark\\:ring-white\\/10',
    ':where(.dark,.dark *)',
    '.aura-token-probe',
    '--aura-color-background',
];
const missingFragments = expectedFragments.filter((fragment) => ! css.includes(fragment));

if (missingFragments.length > 0) {
    throw new Error(`Compiled CSS is missing: ${missingFragments.join(', ')}`);
}

if (/(?:url\(\s*['"]?https?:|@import\s+(?:url\()?['"]?https?:)/i.test(css)) {
    throw new Error('Compiled CSS contains a remote asset URL.');
}

console.log(`PASS ${expectedFragments.length} contract fragments (${Buffer.byteLength(css)} bytes)`);
