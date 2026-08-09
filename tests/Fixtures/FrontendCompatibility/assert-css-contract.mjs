import assert from 'node:assert/strict';
import crypto from 'node:crypto';
import fs from 'node:fs';
import postcss from 'postcss';

function normalize(value) {
    return value.replace(/\s+/g, '');
}

function declarations(root, selector, property) {
    const values = [];
    const normalizedSelector = normalize(selector);

    root.walkRules((rule) => {
        const selectors = postcss.list.comma(rule.selector).map(normalize);

        if (!selectors.includes(normalizedSelector)) {
            return;
        }

        rule.walkDecls(property, (declaration) => values.push(declaration.value));
    });

    return values;
}

export function assertCssContract(outputPath, major) {
    assert.ok([3, 4].includes(major), `Unsupported fixture major: ${major}`);

    const output = fs.readFileSync(outputPath);
    const css = output.toString('utf8');
    const root = postcss.parse(css, { from: outputPath });
    let assertionCount = 0;

    const expectDeclaration = (selector, property, expected, label = `${selector} ${property}`) => {
        const values = declarations(root, selector, property).map(normalize);
        const expectedValues = (Array.isArray(expected) ? expected : [expected]).map(normalize);

        assert.ok(
            expectedValues.some((value) => values.includes(value)),
            `${label}: expected one of ${expectedValues.join(', ')}, received ${values.join(', ') || 'none'}`,
        );
        assertionCount += 1;
    };

    const expectMatchingDeclaration = (selector, property, predicate, label) => {
        const values = declarations(root, selector, property);

        assert.ok(values.some(predicate), `${label}: received ${values.join(', ') || 'none'}`);
        assertionCount += 1;
    };

    const tokenExpectations = {
        '--aura-font-sans': 'ui-sans-serif,system-ui,sans-serif,"AppleColorEmoji","SegoeUIEmoji"',
        '--primary-500': '60115242',
        '--primary-600': '3185233',
        '--aura-color-primary': 'var(--primary-600)',
        '--aura-color-background': '255255255',
        '--aura-color-panel': '250250250',
        '--aura-color-border': '228228231',
        '--aura-color-text': '242427',
        '--aura-color-muted': '828291',
        '--aura-color-success': '2216374',
        '--aura-color-warning': '2171196',
        '--aura-color-danger': '2203838',
    };

    for (const [property, value] of Object.entries(tokenExpectations)) {
        expectDeclaration(':root', property, value, `light token ${property}`);
    }

    const darkTokenExpectations = {
        '--aura-color-background': '9911',
        '--aura-color-panel': '242427',
        '--aura-color-border': '636370',
        '--aura-color-text': '244244245',
        '--aura-color-muted': '161161170',
    };

    for (const [property, value] of Object.entries(darkTokenExpectations)) {
        expectDeclaration('.dark', property, value, `dark token ${property}`);
        assert.notEqual(normalize(tokenExpectations[property]), normalize(value), `${property} must differ in dark mode`);
        assertionCount += 1;
    }

    const semanticMappings = major === 3
        ? {
            '.bg-aura-primary': ['background-color', 'rgb(var(--aura-color-primary)/var(--tw-bg-opacity,1))'],
            '.bg-aura-background': ['background-color', 'rgb(var(--aura-color-background)/var(--tw-bg-opacity,1))'],
            '.bg-aura-panel': ['background-color', 'rgb(var(--aura-color-panel)/var(--tw-bg-opacity,1))'],
            '.border-aura-border': ['border-color', 'rgb(var(--aura-color-border)/var(--tw-border-opacity,1))'],
            '.text-aura-text': ['color', 'rgb(var(--aura-color-text)/var(--tw-text-opacity,1))'],
            '.text-aura-muted': ['color', 'rgb(var(--aura-color-muted)/var(--tw-text-opacity,1))'],
            '.text-aura-success': ['color', 'rgb(var(--aura-color-success)/var(--tw-text-opacity,1))'],
            '.text-aura-warning': ['color', 'rgb(var(--aura-color-warning)/var(--tw-text-opacity,1))'],
            '.text-aura-danger': ['color', 'rgb(var(--aura-color-danger)/var(--tw-text-opacity,1))'],
        }
        : {
            '.bg-aura-primary': ['background-color', 'rgb(var(--aura-color-primary))'],
            '.bg-aura-background': ['background-color', 'rgb(var(--aura-color-background))'],
            '.bg-aura-panel': ['background-color', 'rgb(var(--aura-color-panel))'],
            '.border-aura-border': ['border-color', 'rgb(var(--aura-color-border))'],
            '.text-aura-text': ['color', 'rgb(var(--aura-color-text))'],
            '.text-aura-muted': ['color', 'rgb(var(--aura-color-muted))'],
            '.text-aura-success': ['color', 'rgb(var(--aura-color-success))'],
            '.text-aura-warning': ['color', 'rgb(var(--aura-color-warning))'],
            '.text-aura-danger': ['color', 'rgb(var(--aura-color-danger))'],
        };

    for (const [selector, [property, value]] of Object.entries(semanticMappings)) {
        expectDeclaration(selector, property, value, `semantic mapping ${selector}`);
    }

    expectDeclaration(
        '.font-sans',
        'font-family',
        major === 3
            ? 'var(--aura-font-sans),ui-sans-serif,system-ui,sans-serif,"AppleColorEmoji","SegoeUIEmoji","SegoeUISymbol","NotoColorEmoji"'
            : 'var(--aura-font-sans)',
        'font token mapping',
    );

    expectDeclaration(
        '.bg-primary-600',
        'background-color',
        major === 3
            ? 'rgb(var(--primary-600)/var(--tw-bg-opacity,1))'
            : 'rgb(var(--primary-600))',
        'primary compatibility alias',
    );

    expectDeclaration(
        '.hover\\:bg-primary-500:hover',
        'background-color',
        major === 3
            ? 'rgb(var(--primary-500)/var(--tw-bg-opacity,1))'
            : 'rgb(var(--primary-500))',
        'primary hover compatibility alias',
    );

    expectDeclaration(
        '.bg-sidebar-bg',
        'background-color',
        major === 3
            ? 'rgb(var(--sidebar-bg)/var(--tw-bg-opacity,1))'
            : 'rgb(var(--sidebar-bg))',
        'sidebar background compatibility alias',
    );
    expectDeclaration(
        '.text-sidebar-text',
        'color',
        major === 3
            ? 'rgb(var(--sidebar-text)/var(--tw-text-opacity,1))'
            : 'rgb(var(--sidebar-text))',
        'sidebar text compatibility alias',
    );

    if (major === 3) {
        expectDeclaration('.bg-aura-panel\\/80', 'background-color', 'rgb(var(--aura-color-panel)/.8)', 'semantic opacity');
        expectDeclaration('.bg-primary-600\\/10', 'background-color', 'rgb(var(--primary-600)/.1)', 'legacy opacity');
    } else {
        expectMatchingDeclaration(
            '.bg-aura-panel\\/80',
            'background-color',
            (value) => normalize(value) === 'color-mix(inoklab,rgb(var(--aura-color-panel))80%,transparent)',
            'semantic opacity',
        );
        expectMatchingDeclaration(
            '.bg-primary-600\\/10',
            'background-color',
            (value) => normalize(value) === 'color-mix(inoklab,rgb(var(--primary-600))10%,transparent)',
            'legacy opacity',
        );
    }

    expectDeclaration('.overflow-hidden', 'overflow', 'hidden', 'layout source utility');
    expectDeclaration(
        '.bg-green-100',
        'background-color',
        major === 3
            ? 'rgb(220252231/var(--tw-bg-opacity,1))'
            : 'var(--color-green-100)',
        'PHP source utility',
    );

    const darkGraySelector = '.dark\\:bg-gray-900:where(.dark,.dark *)';
    expectDeclaration(
        darkGraySelector,
        'background-color',
        major === 3
            ? 'rgb(var(--gray-900)/var(--tw-bg-opacity,1))'
            : 'rgb(var(--gray-900))',
        'selector dark-mode utility',
    );

    root.walkDecls((declaration) => {
        assert.doesNotMatch(declaration.value, /url\(\s*['"]?https?:/i, `Remote asset URL in ${declaration.prop}`);
        assertionCount += 1;
    });
    root.walkAtRules('import', (rule) => {
        assert.doesNotMatch(rule.params, /https?:/i, 'Remote stylesheet import');
        assertionCount += 1;
    });

    return {
        assertionCount,
        bytes: output.length,
        sha256: crypto.createHash('sha256').update(output).digest('hex'),
    };
}
