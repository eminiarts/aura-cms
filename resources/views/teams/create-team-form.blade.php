<div>
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Team Details') }}</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Create a new team to collaborate with others on projects.') }}
                </p>
            </div>
        </div>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <form wire:submit="createTeam">
                <div class="px-4 py-5 bg-white dark:bg-gray-800 shadow sm:rounded-lg sm:p-6">
                    <div class="grid grid-cols-6 gap-6">
                        <div class="col-span-6 sm:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Team Owner') }}</label>
                            <div class="flex items-center mt-2">
                                <div class="text-sm text-gray-900 dark:text-gray-100">{{ auth()->user()->name }}</div>
                            </div>
                        </div>

                        <div class="col-span-6 sm:col-span-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Team Name') }}</label>
                            <input id="name" type="text" wire:model="name" autofocus
                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            @error('name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end px-4 py-3 text-right shadow sm:px-6 sm:rounded-bl-md sm:rounded-br-md bg-gray-50 dark:bg-gray-900/50">
                    <x-aura::button.primary type="submit">
                        <div wire:loading wire:target="createTeam">
                            <x-aura::icon.loading />
                        </div>
                        {{ __('Create') }}
                    </x-aura::button.primary>
                </div>
            </form>
        </div>
    </div>
</div>
