<div class="p-4">
    <div class="mb-4 text-sm">{{ __('Permanently delete your account.') }}</div>
    <div>
        <div class="max-w-xl text-sm text-gray-600">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </div>

        <x-aura::dialog wire:model="confirmingUserDeletion">
            <x-aura::dialog.open>
                <x-aura::button.danger class="mt-4">
                    {{ __('Delete Account') }}
                </x-aura::button.danger>
            </x-aura::dialog.open>

            <x-aura::dialog.panel>
                <x-aura::dialog.title>{{ __('Delete Account') }}</x-aura::dialog.title>

                <div>
                    {{ __('Are you sure you want to delete your account? Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                </div>

                <div class="mt-4" x-data="{}"
                    x-on:confirming-delete-user.window="setTimeout(() => $refs.password.focus(), 250)">
                    <x-aura::input type="password" class="mt-1 block w-3/4" placeholder="{{ __('Password') }}"
                        x-ref="password" wire:model="password" wire:keydown.enter="deleteUser" />

                    <x-aura::input-error for="password" class="mt-2" />
                </div>
                <x-aura::dialog.footer>
                    <x-aura::dialog.close>
                        <x-aura::button.border class="bg-white" type="button">
                            {{ __('Cancel') }}
                        </x-aura::button.border>
                    </x-aura::dialog.close>

                    <x-aura::dialog.close>
                        <x-aura::button.danger class="ml-3" wire:click="deleteUser" wire:loading.attr="disabled">
                            {{ __('Delete Account') }}
                        </x-aura::button.danger>
                    </x-aura::dialog.close>
                </x-aura::dialog.footer>

            </x-aura::dialog.panel>
        </x-aura::dialog>
    </div>
</div>
