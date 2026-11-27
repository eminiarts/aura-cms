import './bootstrap';

import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import ui from '@alpinejs/ui';

import modal from './modal.js';

import { Sortable } from '@shopify/draggable';
import tippy from 'tippy.js';

window.Sortable = Sortable;
window.tippy = tippy;

// Register Alpine plugins via livewire:init event
// Livewire loads its own Alpine instance via @livewireScripts
document.addEventListener('livewire:init', () => {
  const Alpine = window.Alpine;

  Alpine.plugin(collapse);
  Alpine.plugin(focus);
  Alpine.plugin(ui);
  Alpine.plugin(modal);

  Alpine.data('aura', () => ({
    loading: false,
    success: false,

    error: null,

    showSearch: false,

    rightSidebar: true,
    showFilters: false,

    init() {
    },

    search() {
      $dispatch('search');
    },
    insetSidebar(event) {
      var sidebar = event.detail.element;
      let body = document.querySelector('body');
      setTimeout(function() {
        body.style.paddingRight = sidebar.offsetWidth + 'px';
      }, 10);
      setTimeout(function() {
        body.style.paddingRight = sidebar.offsetWidth + 'px';
      }, 50);
      setTimeout(function() {
        body.style.paddingRight = sidebar.offsetWidth + 'px';
      }, 100);
      setTimeout(function() {
        body.style.paddingRight = sidebar.offsetWidth + 'px';
      }, 250);

    },
    toggleRightSidebar() {
      this.rightSidebar = !this.rightSidebar;
    },
    closeSearch() {
      this.showSearch = false;
    }
  }))

  // x-file-name: a file name that does not fit loses characters from the end of its base name and
  // keeps its extension, "proposal-northwi….pdf". CSS ellipsis cannot keep a suffix, and a flex pair
  // (truncated base + extension) leaves a gap of up to one glyph after the ellipsis. The element holds
  // the full name (with CSS truncate as the no-JS fallback); a Livewire morph that puts the full name
  // back is fitted again.
  Alpine.directive('file-name', (el, {}, { cleanup }) => {
    let full = el.textContent.trim();
    let shown = full;

    // Sub-pixel widths: scrollWidth rounds, so a name 0.4px too wide would pass and get a second, CSS ellipsis.
    const range = document.createRange();
    const fits = () => {
      range.selectNodeContents(el);
      return range.getBoundingClientRect().width <= el.getBoundingClientRect().width;
    };
    const show = (text) => { shown = text; el.textContent = text; };

    const fit = () => {
      show(full);
      if (el.clientWidth === 0 || fits()) return;

      const dot = full.lastIndexOf('.');
      const base = dot > 0 ? full.slice(0, dot) : full;
      const ext = dot > 0 ? full.slice(dot) : '';
      let low = 1;
      let high = base.length - 1;

      while (low < high) {
        const mid = Math.ceil((low + high) / 2);
        show(base.slice(0, mid) + '…' + ext);
        if (fits()) low = mid; else high = mid - 1;
      }

      show(base.slice(0, low).replace(/[\s._-]+$/, '') + '…' + ext);
    };

    const resize = new ResizeObserver(() => fit());
    const morph = new MutationObserver(() => {
      const text = el.textContent.trim();
      if (text === shown) return;
      full = text;
      fit();
    });

    resize.observe(el);
    morph.observe(el, { childList: true, characterData: true, subtree: true });
    document.fonts?.ready.then(fit);
    fit();

    cleanup(() => {
      resize.disconnect();
      morph.disconnect();
    });
  });

  Alpine.store('leftSidebar', {
      init() {
        let storedValue = localStorage.getItem('leftSidebar');

        if(storedValue === null) {
            this.on = true;
            localStorage.setItem('leftSidebar', this.on);
        } else {
            this.on = storedValue === 'true';
        }
      },
      on: true,
      toggle() {
          this.on = ! this.on
          localStorage.setItem('leftSidebar', this.on);
      }
  })
});


// Until the pointer moves, list rows keep their hover state off (the row-hover Tailwind variant and
// .aura-row-actions): a page rendered under a resting cursor, after a click or wire:navigate, would
// otherwise open with one row highlighted and its actions showing.
const markPointerStill = () => document.documentElement.classList.add('aura-pointer-still');
markPointerStill();
document.addEventListener('livewire:navigated', markPointerStill);
document.addEventListener('pointermove', () => document.documentElement.classList.remove('aura-pointer-still'), { passive: true });


function updateVisitedPages(title, url) {
  const key = 'visitedPages';
  const maxVisitedPages = 5;
  let visitedPages = JSON.parse(localStorage.getItem(key)) || [];

  visitedPages = visitedPages.filter(page => page.url !== url);
  visitedPages.unshift({ title, url });
  visitedPages = visitedPages.slice(0, maxVisitedPages);

  localStorage.setItem(key, JSON.stringify(visitedPages));
}

document.addEventListener('DOMContentLoaded', () => {
  const pageTitle = document.title;
  const pageUrl = window.location.href;
  updateVisitedPages(pageTitle, pageUrl);
});


// after 100ms trigger a window resize event to force the chart to redraw
setTimeout(function() {
    window.dispatchEvent(new Event('resize'));
}, 0);
setTimeout(function() {
    window.dispatchEvent(new Event('resize'));
}, 100);
