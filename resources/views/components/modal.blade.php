@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl'
])

@php
$maxWidth = [
    'sm' => 'sm:max-aura::w-sm',
    'md' => 'sm:max-aura::w-md',
    'lg' => 'sm:max-aura::w-lg',
    'xl' => 'sm:max-aura::w-xl',
    '2xl' => 'sm:max-aura::w-2xl',
][$maxWidth];
@endphp

<div
    x-aura::data="{
        show: @js($show),
        focusables() {
            // All focusable element types...
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'
            return [...$el.querySelectorAll(selector)]
                // All non-disabled elements...
                .filter(el => ! el.hasAttribute('disabled'))
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) -1 },
    }"
    x-aura::init="$watch('show', value => {
        if (value) {
            document.body.classList.add('overflow-y-hidden');
            {{ $attributes->has('focusable') ? 'setTimeout(() => firstFocusable().focus(), 100)' : '' }}
        } else {
            document.body.classList.remove('overflow-y-hidden');
        }
    })"
    x-aura::on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-aura::on:close.stop="show = false"
    x-aura::on:keydown.escape.window="show = false"
    x-aura::on:keydown.tab.prevent="$event.shiftKey || nextFocusable().focus()"
    x-aura::on:keydown.shift.tab.prevent="prevFocusable().focus()"
    x-aura::show="show"
    class="fixed inset-0 overflow-y-auto px-aura::4 py-6 sm:px-aura::0 z-50"
    style="display: {{ $show ? 'block' : 'none' }};"
>
    <div
        x-aura::show="show"
        class="fixed inset-0 transform transition-all"
        x-aura::on:click="show = false"
        x-aura::transition:enter="ease-out duration-300"
        x-aura::transition:enter-start="opacity-0"
        x-aura::transition:enter-end="opacity-100"
        x-aura::transition:leave="ease-in duration-200"
        x-aura::transition:leave-start="opacity-100"
        x-aura::transition:leave-end="opacity-0"
    >
        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
    </div>

    <div
        x-aura::show="show"
        class="mb-6 bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full {{ $maxWidth }} sm:mx-aura::auto"
        x-aura::transition:enter="ease-out duration-300"
        x-aura::transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-aura::transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-aura::transition:leave="ease-in duration-200"
        x-aura::transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-aura::transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        {{ $slot }}
    </div>
</div>
