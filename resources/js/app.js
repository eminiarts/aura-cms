import './bootstrap';

import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import ui from '@alpinejs/ui';

import { Sortable } from '@shopify/draggable';
import tippy from 'tippy.js';
import flatpickr from "flatpickr";
// import { German } from "flatpickr/dist/l10n/de.js"

import Tagify from '@yaireo/tagify'


window.Tagify = Tagify;
window.Alpine = Alpine;
window.Sortable = Sortable;
window.tippy = tippy;

// window.german = German;
window.flatpickr = flatpickr;
// flatpickr.localize(German); // default locale is now German


Alpine.plugin(collapse);
Alpine.plugin(focus);
Alpine.plugin(ui);

Livewire.start();

Alpine.data('aura', () => ({
  loading: false,
  success: false,

  error: null,

  showSearch: false,

  rightSidebar: true,
  showFilters: false,

  init() {
    console.log('aura init from data');
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

  },
  toggleRightSidebar() {
    this.rightSidebar = !this.rightSidebar;
  },
  closeSearch() {
    this.showSearch = false;
  }
}))

Alpine.store('leftSidebar', {
    init() {
      // get value from localstorage
      let storedValue = localStorage.getItem('leftSidebar');

      // if storedValue is null (doesn't exist), set it to 'true'
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


Alpine.start()

function updateVisitedPages(title, url) {
  // console.log('updateVisitedPages');
  const key = 'visitedPages';
  const maxVisitedPages = 5;
  let visitedPages = JSON.parse(localStorage.getItem(key)) || [];

  // Remove the existing entry with the same URL, if any
  visitedPages = visitedPages.filter(page => page.url !== url);

  // Add the new entry
  visitedPages.unshift({ title, url });

  // Keep only the last 5 entries
  visitedPages = visitedPages.slice(0, maxVisitedPages);

  // Update local storage
  localStorage.setItem(key, JSON.stringify(visitedPages));
}

document.addEventListener('DOMContentLoaded', () => {
  // console.log('DOMContentLoaded');
  const pageTitle = document.title;
  const pageUrl = window.location.href;
  updateVisitedPages(pageTitle, pageUrl);
});
