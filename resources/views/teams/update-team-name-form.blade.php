<div class="mb-10 sm:mb-0">
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Team Name') }}</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ __("The team's name and owner information.") }}
                </p>
            </div>
        </div>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <form wire:submit="updateTeamName">
                <div class="px-4 py-5 bg-white dark:bg-gray-800 shadow sm:rounded-lg sm:p-6">
                    <div class="grid grid-cols-6 gap-6">
                        {{-- Team Owner --}}
                        <div class="col-span-6 sm:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Team Owner') }}</label>
                            <div class="flex items-center mt-2">
                                <div class="text-sm text-gray-900 dark:text-gray-100">{{ optional(AuraBaseResourcesUser::find($team->user_id))->name ?? __('N/A') }}</div>
                            </div>
                        </div>

                        {{-- Team Name --}}
                        <div class="col-span-6 sm:col-span-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Team Name') }}</label>
                            <input id="name" type="text" wire:model="name"
                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                @cannot('update', $team) disabled @endcannot
                            />
                            @error('name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                @can('update', $team)
                    <div class="flex items-center justify-end px-4 py-3 text-right shadow sm:px-6 sm:rounded-bl-md sm:rounded-br-md bg-gray-50 dark:bg-gray-900/50">
                        <x-aura::button.primary type="submit">
                            <div wire:loading wire:target="updateTeamName">
                                <x-aura::icon.loading />
                            </div>
                            {{ __('Save') }}
                        </x-aura::button.primary>
                    </div>
                @endcan
            </form>
        </div>
    </div>
</div>
