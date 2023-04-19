@aware(['confirmingAction'])

@props([
    'title' => __('Confirm Action'), 
    'content' => __('Are you sure you want to perform this action?'), 
    'button' => __('Confirm'), 'confirmingAction'
])

@php
    $confirmableId = md5($attributes->wire('then'));
@endphp

<span
    {{ $attributes->wire('then') }}
    x-data
    x-ref="span"
    x-on:click="$wire.startConfirmingAction('{{ $confirmableId }}')"
    x-on:action-confirmed.window="setTimeout(() => $event.detail.id === '{{ $confirmableId }}' && $refs.span.dispatchEvent(new CustomEvent('then', { bubbles: false })), 250);"
>
    {{ $slot }}
</span>

{{-- @once --}}
<x-aura::dialog-modal wire:model="confirmingAction">
    <x-slot name="title">
        {{ $title }}
    </x-slot>

    <x-slot name="content">

        {{ $content }}

    </x-slot>

    <x-slot name="footer">
        <x-aura::button.transparent wire:click="stopConfirmingAction" wire:loading.attr="disabled">
            {{ __('Cancel') }}
        </x-aura::button.transparent>

        <x-aura::button.primary class="ml-3" dusk="confirm-action-button" wire:click="confirmAction" wire:loading.attr="disabled">
            {{ $button }}
        </x-aura::button.primary>
    </x-slot>
</x-aura::dialog-modal>
{{-- @endonce --}}
