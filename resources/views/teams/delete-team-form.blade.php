<div class="mt-10 sm:mt-0">
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg font-medium text-red-600 dark:text-red-400">{{ __('Delete Team') }}</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Permanently delete this team.') }}
                </p>
            </div>
        </div>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="px-4 py-5 bg-white dark:bg-gray-800 shadow sm:rounded-lg sm:p-6">
                <div class="max-w-xl text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Once a team is deleted, all of its resources and data will be permanently deleted. Before deleting this team, please download any data or information regarding this team that you wish to retain.') }}
                </div>

                <div class="mt-5">
                    <form wire:submit="deleteTeam">
                        <div class="mb-4">
                            <label for="confirmTeamName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Please type ":name" to confirm', ['name' => $team->name]) }}
                            </label>
                            <input id="confirmTeamName" type="text" wire:model="confirmTeamName"
                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                autocomplete="off"
                            />
                            @error('confirmTeamName') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <x-aura::button.danger type="submit">
                            <div wire:loading wire:target="deleteTeam">
                                <x-aura::icon.loading />
                            </div>
                            {{ __('Delete Team') }}
                        </x-aura::button.danger>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
