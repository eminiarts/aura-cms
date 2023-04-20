
@props([
    'title' => __('Confirm Action'), 
    'content' => __('Are you sure you want to perform this action?'), 
    'button' => __('Confirm'), 
])

@php
    $confirmableId = md5($attributes->wire('then'));
@endphp

<div x-data="{ showModal: false }">
<span
    {{ $attributes->wire('then') }}
    x-ref="span"
    x-on:click="showModal = true"
    x-on:action-confirmed.window="console.log($event.detail.id); setTimeout(() => $event.detail.id === '{{ $confirmableId }}' && $refs.span.dispatchEvent(new CustomEvent('then', { bubbles: false })), 250); showModal = false;"
>
    {{ $slot }}
</span>

{{-- @once --}}
<x-aura::dialog-modal-js>
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

        <x-aura::button.primary class="ml-3" wire:click="confirmAction('{{ $confirmableId }}')" wire:loading.attr="disabled">
            {{ $button }}
        </x-aura::button.primary>
    </x-slot>
</x-aura::dialog-modal-js>
{{-- @endonce --}}
</div>