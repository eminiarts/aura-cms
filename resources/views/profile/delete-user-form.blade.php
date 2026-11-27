{{-- The last section of the profile's Details tab: one line on what deletion means, then a red outline
     button that opens the password confirmation. --}}
<div class="flex flex-col gap-3 px-4 pt-1 w-full sm:flex-row sm:items-center sm:justify-between sm:gap-6">
    <p class="text-sm text-gray-600 dark:text-gray-400">
        {{ __('Permanently deletes your account and all of its data. This cannot be undone.') }}
    </p>

    <x-aura::dialog wire:model="confirmingUserDeletion">
        <x-aura::dialog.open>
            <x-aura::button class="shrink-0 text-red-700 bg-white ring-1 ring-red-600/30 shadow-xs hover:bg-red-50 active:bg-red-100 dark:text-red-400 dark:bg-transparent dark:ring-red-500/40 dark:hover:bg-red-500/10 dark:active:bg-red-500/15 focus-visible:ring-red-500">
                {{ __('Delete account') }}
            </x-aura::button>
        </x-aura::dialog.open>

        <x-aura::dialog.panel>
            <x-aura::dialog.title>{{ __('Delete account') }}</x-aura::dialog.title>

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
                        {{ __('Delete account') }}
                    </x-aura::button.danger>
                </x-aura::dialog.close>
            </x-aura::dialog.footer>

        </x-aura::dialog.panel>
    </x-aura::dialog>
</div>
