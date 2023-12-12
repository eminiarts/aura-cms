<div class="px-2 mt-2">
    <h3 class="max-w-xl text-sm">
        {{ __('Add additional security to your account using two factor authentication.') }}
    </h3>

    <div>
        <h3 class="text-lg font-medium text-gray-900">
            @if ($this->enabled)

                <div class="p-4 my-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400"
                    role="alert">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <!-- Heroicon name: outline/check-circle -->
                            <svg class="h-6 w-6 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3 w-0 flex-1 pt-0.5">
                            <p class="text-sm font-medium text-gray-900">
                                @if ($showingConfirmation)
                                    {{ __('Please scan the QR-Code with your Authenticator App and confirm the Code to enable 2FA.') }}
                                @else
                                    {{ __('You have enabled two factor authentication.') }}
                                @endif
                            </p>
                        </div>

                    </div>
                </div>
            @else
                <div class="p-4 my-4 text-sm text-yellow-800 rounded-lg bg-yellow-50 dark:bg-gray-800 dark:text-yellow-300"
                    role="alert">
                    {{ __('You have not enabled two factor authentication.') }}
                </div>

            @endif
        </h3>

        <div class="mt-3 max-w-xl text-sm">
            <p>
                {{ __('When two factor authentication is enabled, you will be prompted for a secure, random token during authentication. You may retrieve this token from your phone\'s Google Authenticator application.') }}
            </p>
        </div>

        @if ($this->enabled)
            @if ($showingQrCode)
                <div class="mt-4 max-w-xl text-sm">
                    <p class="font-semibold">
                        {{ __('Two factor authentication is now enabled. Scan the following QR code using your phone\'s authenticator application.') }}
                    </p>
                </div>

                <div class="mt-4">
                    {!! $this->user->twoFactorQrCodeSvg() !!}
                </div>

                <div class="mt-4 max-w-xl text-sm">
                    <p class="font-semibold">
                        {{ __('Setup Key') }}: {{ decrypt($this->user->two_factor_secret) }}
                    </p>
                </div>

                @if ($showingConfirmation)
                    <div class="mt-4">
                        <x-aura::fields.label :label="__('Code')" />

                        <x-aura::input.text id="code" type="text" name="code" class="block mt-1 w-1/2"
                            inputmode="numeric" autofocus autocomplete="one-time-code" wire:model.defer="code"
                            wire:keydown.enter="confirmTwoFactorAuthentication" />

                        <x-aura::jet-input-error for="code" class="mt-2" />
                    </div>
                @endif

            @endif

            @if ($showingRecoveryCodes)
                <div class="mt-4 max-w-xl text-sm">
                    <p class="font-semibold">
                        {{ __('Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two factor authentication device is lost.') }}
                    </p>
                </div>

                <div class="grid gap-1 max-w-xl mt-4 px-4 py-4 font-mono text-sm bg-gray-100 rounded-lg">
                    @foreach (json_decode(decrypt($this->user->two_factor_recovery_codes), true) as $code)
                        <div>{{ $code }}</div>
                    @endforeach
                </div>
            @endif
        @endif

        <div class="mt-5">
            @if (!$this->enabled)

                <x-aura::confirms-password wire:then="enableTwoFactorAuthentication" :confirmingPassword="$confirmingPassword">
                    <x-aura::button.primary type="button" wire:loading.attr="disabled">
                        {{ __('Enable') }}
                    </x-aura::button.primary>
                </x-aura::confirms-password>
            @elseif ($showingConfirmation)
                <x-aura::confirms-password wire:then="confirmTwoFactorAuthentication">
                    <x-aura::button.primary type="button" class="mr-3" wire:loading.attr="disabled">
                        {{ __('Confirm') }}
                    </x-aura::button.primary>
                </x-aura::confirms-password>
            @else
                @if ($showingRecoveryCodes)
                    <x-aura::confirms-password wire:then="regenerateRecoveryCodes" :confirmingPassword="$confirmingPassword">
                        <x-aura::button.light class="mr-3">
                            {{ __('Regenerate Recovery Codes') }}
                        </x-aura::button.light>
                    </x-aura::confirms-password>
                @else
                    <x-aura::confirms-password wire:then="showRecoveryCodes" :confirmingPassword="$confirmingPassword">
                        <x-aura::button.light class="mr-3">
                            {{ __('Show Recovery Codes') }}
                        </x-aura::button.light>
                    </x-aura::confirms-password>
                @endif

                <x-aura::confirms-password wire:then="disableTwoFactorAuthentication" :confirmingPassword="$confirmingPassword">
                    <x-aura::button.danger wire:loading.attr="disabled">
                        {{ __('Disable') }}
                    </x-aura::button.danger>
                </x-aura::confirms-password>
            @endif
        </div>
    </div>
</div>
