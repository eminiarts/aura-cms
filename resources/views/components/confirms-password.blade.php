@aware(['confirmingPassword'])

@props([
    'title' => __('Confirm Password'),
    'content' => __('For your security, please confirm your password to continue.'),
    'button' => __('Confirm'),
    'confirmingPassword'
])

@php
    $confirmableId = md5($attributes->wire('then'));
@endphp

    <x-aura::dialog wire:model="confirmingPassword">

        <span {{ $attributes->wire('then') }} x-data x-ref="span"
            x-on:click="$wire.startConfirmingPassword('{{ $confirmableId }}')"
            @password-confirmed.window="setTimeout(() => $event.detail.id === '{{ $confirmableId }}' && $refs.span.dispatchEvent(new CustomEvent('then', { bubbles: false })), 250);">
            {{ $slot }}
        </span>

        <x-aura::dialog.panel>
            <x-aura::dialog.title>{{ $title }}</x-aura::dialog.title>

            <div>
                {{ $content }}
            </div>

            <div class="mt-4" x-data="{}"
                x-on:confirming-password.window="console.log('confirming password event fired....'); setTimeout(() => $refs.confirmable_password.focus(), 250)">
                <x-aura::input type="password" class="mt-1 block w-3/4" placeholder="{{ __('Password') }}"
                    x-ref="confirmable_password" wire:model="confirmablePassword" wire:keydown.enter="confirmPassword" />

                <x-aura::input-error for="confirmable_password" class="mt-2" />
            </div>

            <x-aura::dialog.footer>

                <x-aura::dialog.close>
                    <x-aura::button.transparent>
                        {{ __('Cancel') }}
                    </x-aura::button.transparent>
                </x-aura::dialog.close>

                <x-aura::button.primary class="ml-3" dusk="confirm-password-button" wire:click="confirmPassword"
                    wire:loading.attr="disabled">
                    {{ $button }}
                </x-aura::button.primary>

            </x-aura::dialog.footer>
        </x-aura::dialog.panel>
    </x-aura::dialog>
