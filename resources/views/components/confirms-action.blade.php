@props([
    'title' => __('Confirm Action'), 
    'content' => __('Are you sure you want to perform this action?'), 
    'button' => __('Confirm'), 
    'button_class' => 'ml-3', 
])

@php
    $confirmableId = md5($attributes->wire('then'));
@endphp

<div {{ $attributes->wire('then') }} x-data="{ showModal: false }" x-on:action-confirmed.window="setTimeout(() => $event.detail.id === '{{ $confirmableId }}' && $dispatch('then'), 250); showModal = false;">

    <x-aura::dialog>

    <x-aura::dialog.open>
    <div>
        {{ $slot }}
    </div>
    </x-aura::dialog.open>
    
        <x-aura::dialog.panel>
            <x-aura::dialog.title>{{ $title }}</x-aura::dialog.title>

            <div class="mt-5 text-gray-600">
                {!! $content !!}
            </div>

            <x-aura::dialog.footer>
                <x-aura::dialog.close>
                    <x-aura::button.transparent>
                        {{ __('Cancel') }}
                    </x-aura::button.transparent>
                </x-aura::dialog.close>

                 <x-aura::button.primary 
        class="{{ $button_class }}" 
        x-on:click="$wire.call('confirmAction', '{{ $confirmableId }}').then(() => { dialogOpen = false; })"
        wire:loading.attr="disabled"
    >
                <div wire:loading wire:target="confirmAction">
                    <x-aura::icon.loading />
                </div>
        {{ $button }}
    </x-aura::button.primary>
            </x-aura::dialog.footer>
        </x-aura::dialog.panel>
    </x-aura::dialog>
</div>