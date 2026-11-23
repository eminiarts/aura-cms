// Alpine helpers for the style guide page (admin/styleguide).

// Pseudo-classes the style guide can force on a specimen. `.sg-force-hover` on an element
// renders it and its descendants as if hovered, using the real CSS rules of the page.
const FORCED_STATES = {
    hover: /(?<!\\):hover(?![-\w])/g,
    focus: /(?<!\\):focus(?:-visible)?(?![-\w])/g,
    active: /(?<!\\):active(?![-\w])/g,
};

function splitSelectors(text) {
    const selectors = [];
    let depth = 0;
    let current = '';

    for (const character of text) {
        if (character === '(') depth++;
        if (character === ')') depth--;

        if (character === ',' && depth === 0) {
            selectors.push(current.trim());
            current = '';
        } else {
            current += character;
        }
    }

    if (current.trim()) selectors.push(current.trim());

    return selectors;
}

function forcedRules(rules) {
    const css = [];

    for (const rule of rules) {
        if (rule instanceof CSSStyleRule) {
            for (const [state, pattern] of Object.entries(FORCED_STATES)) {
                for (const selector of splitSelectors(rule.selectorText)) {
                    if (selector.search(pattern) === -1 || selector.includes(':not(')) continue;

                    // The pseudo-class becomes "inside a .sg-force-* wrapper", with the same specificity.
                    // One rule per selector, so one unsupported selector cannot drop the others.
                    const forced = selector.replace(pattern, `:is(.sg-force-${state}, .sg-force-${state} *)`);
                    css.push(`${forced} { ${rule.style.cssText} }`);
                }
            }
        } else if (rule instanceof CSSMediaRule || rule instanceof CSSSupportsRule) {
            const inner = forcedRules(rule.cssRules);

            if (inner.length) {
                const keyword = rule instanceof CSSMediaRule ? '@media' : '@supports';
                css.push(`${keyword} ${rule.conditionText} { ${inner.join('\n')} }`);
            }
        }
    }

    return css;
}

function forcePseudoStates() {
    if (document.getElementById('aura-styleguide-states')) return;

    const css = [];

    for (const sheet of document.styleSheets) {
        try {
            css.push(...forcedRules(sheet.cssRules));
        } catch (error) {
            // Cross-origin stylesheets cannot be read; skip them.
        }
    }

    const style = document.createElement('style');
    style.id = 'aura-styleguide-states';
    style.textContent = css.join('\n');
    document.head.appendChild(style);
}

export default function (Alpine) {
    // Page root. `forceLight` turns the admin's dark class off while the page is open,
    // because Tailwind's dark selector cannot be undone for a single subtree.
    Alpine.data('auraStyleguide', ({ forceLight = false } = {}) => ({
        removedRootDark: false,

        init() {
            if (forceLight && document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                this.removedRootDark = true;
            }

            forcePseudoStates();

            this.restoreRoot = this.restoreRoot.bind(this);
            document.addEventListener('livewire:navigating', this.restoreRoot);
        },

        destroy() {
            this.restoreRoot();
            document.removeEventListener('livewire:navigating', this.restoreRoot);
        },

        restoreRoot() {
            if (this.removedRootDark) {
                document.documentElement.classList.add('dark');
                this.removedRootDark = false;
            }
        },
    }));

    // Shows the computed value of a CSS variable on the element.
    Alpine.data('auraCssVar', (name) => ({
        value: '',

        init() {
            const value = getComputedStyle(this.$el).getPropertyValue(name).trim();
            const rootSize = parseFloat(getComputedStyle(document.documentElement).fontSize) || 16;

            // Preset previews arrive as rem; show pixels like the saved column.
            this.value = value.endsWith('rem') ? `${parseFloat(value) * rootSize}px` : (value || '—');
        },
    }));

    // Measures the rendered height of each [data-sg-measure] item and flags the ones that
    // differ from the [data-sg-reference] item of the same row.
    Alpine.data('auraMeasure', () => ({
        init() {
            this.measure = this.measure.bind(this);
            (document.fonts?.ready ?? Promise.resolve()).then(() => requestAnimationFrame(this.measure));
            window.addEventListener('resize', this.measure);
        },

        destroy() {
            window.removeEventListener('resize', this.measure);
        },

        heightOf(item) {
            const target = item.querySelector('[data-sg-target]')
                ?? item.querySelector('input:not([type=hidden]), select, textarea, button, a');

            return target ? Math.round(target.getBoundingClientRect().height * 10) / 10 : 0;
        },

        measure() {
            const reference = this.$el.querySelector('[data-sg-reference]');
            const expected = reference ? this.heightOf(reference) : null;

            for (const item of this.$el.querySelectorAll('[data-sg-measure]')) {
                const height = this.heightOf(item);
                const label = item.querySelector('[data-sg-height]');
                const mismatch = expected !== null && Math.abs(height - expected) > 0.5 ? 'true' : 'false';

                item.dataset.sgMismatch = mismatch;

                if (label) {
                    label.textContent = `${height}px`;
                    label.dataset.sgMismatch = mismatch;
                }
            }
        },
    }));
}
