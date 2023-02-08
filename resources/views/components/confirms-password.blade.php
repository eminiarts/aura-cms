@aware(['confirmingPassword'])

@props(['title' => __('Confirm Password'), 'content' => __('For your security, please confirm your password to continue.'), 'button' => __('Confirm'), 'confirmingPassword'])

@php
    $confirmableId = md5($attributes->wire('then'));
@endphp

<span
    {{ $attributes->wire('then') }}
    x-aura::data
    x-aura::ref="span"
    x-aura::on:click="$wire.startConfirmingPassword('{{ $confirmableId }}')"
    x-aura::on:password-confirmed.window="setTimeout(() => $event.detail.id === '{{ $confirmableId }}' && $refs.span.dispatchEvent(new CustomEvent('then', { bubbles: false })), 250);"
>
    {{ $slot }}
</span>

@once
<x-aura::dialog-modal wire:model="confirmingPassword">
    <x-aura::slot name="title">
        {{ $title }}
    </x-aura::slot>

    <x-aura::slot name="content">

        {{ $content }}

        <div class="mt-4" x-aura::data="{}" x-aura::on:confirming-password.window="setTimeout(() => $refs.confirmable_password.focus(), 250)">
            <x-aura::input type="password" class="mt-1 block w-3/4" placeholder="{{ __('Password') }}"
                        x-aura::ref="confirmable_password"
                        wire:model.defer="confirmablePassword"
                        wire:keydown.enter="confirmPassword" />

            <x-aura::simple-input-error for="confirmable_password" class="mt-2" />
        </div>
    </x-aura::slot>

    <x-aura::slot name="footer">
        <x-aura::button.transparent wire:click="stopConfirmingPassword" wire:loading.attr="disabled">
            {{ __('Cancel') }}
        </x-aura::button.transparent>

        <x-aura::button.primary class="ml-3" dusk="confirm-password-button" wire:click="confirmPassword" wire:loading.attr="disabled">
            {{ $button }}
        </x-aura::button.primary>
    </x-aura::slot>
</x-aura::dialog-modal>
@endonce
