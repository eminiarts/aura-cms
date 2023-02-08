@props(['id', 'maxWidth'])

@php
$id = $id ?? md5($attributes->wire('model'));

$maxWidth = [
    'sm' => 'sm:max-aura::w-sm',
    'md' => 'sm:max-aura::w-md',
    'lg' => 'sm:max-aura::w-lg',
    'xl' => 'sm:max-aura::w-xl',
    '2xl' => 'sm:max-aura::w-2xl',
][$maxWidth ?? '2xl'];
@endphp

<div
    x-aura::data="{ show: @entangle($attributes->wire('model')).defer }"
    x-aura::on:close.stop="show = false"
    x-aura::on:keydown.escape.window="show = false"
    x-aura::show="show"
    id="{{ $id }}"
    class="jetstream-modal fixed inset-0 overflow-y-auto px-aura::4 py-6 sm:px-aura::0 z-50"
    style="display: none;"
>
    <div x-aura::show="show" class="fixed inset-0 transform transition-all" x-aura::on:click="show = false" x-aura::transition:enter="ease-out duration-300"
                    x-aura::transition:enter-start="opacity-0"
                    x-aura::transition:enter-end="opacity-100"
                    x-aura::transition:leave="ease-in duration-200"
                    x-aura::transition:leave-start="opacity-100"
                    x-aura::transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
    </div>

    <div x-aura::show="show" class="mb-6 bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full {{ $maxWidth }} sm:mx-aura::auto"
                    x-aura::trap.inert.noscroll="show"
                    x-aura::transition:enter="ease-out duration-300"
                    x-aura::transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-aura::transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-aura::transition:leave="ease-in duration-200"
                    x-aura::transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-aura::transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
        {{ $slot }}
    </div>
</div>
