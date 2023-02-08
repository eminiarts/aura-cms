import './bootstrap';

import Alpine from 'alpinejs';


import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import ui from '@alpinejs/ui';

import { Sortable } from '@shopify/draggable';

import tippy from 'tippy.js';

import flatpickr from "flatpickr";
// import { German } from "flatpickr/dist/l10n/de.js"

window.Alpine = Alpine;

window.Sortable = Sortable;

window.tippy = tippy;

// window.german = German;
window.flatpickr = flatpickr;
// flatpickr.localize(German); // default locale is now German


Alpine.plugin(collapse);
Alpine.plugin(focus);
Alpine.plugin(ui);


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
      this.on = localStorage.getItem('leftSidebar') === 'true';
    },
    on: true,
    toggle() {
        this.on = ! this.on
        localStorage.setItem('leftSidebar', this.on);
    }
})


Alpine.start()

console.log('Alpine.js started');
