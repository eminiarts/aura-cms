<div class="flex flex-col mt-2" wire:key="table-index">
    <div class="overflow-hidden overflow-x-auto min-w-full align-middle border border-gray-400/30 sm:rounded-lg dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            @include($this->settings['views']['table_header'])

            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                @forelse($rows as $row)
                    <tr class="transition-colors duration-200 ease-in-out hover:bg-gray-100 dark:hover:bg-gray-800"
                        :class="{ 'bg-primary-50 dark:bg-primary-900/50': selected.includes('{{ $row->id }}') }"
                        x-on:click="toggleRow($event, {{ $row->id }})">
                        @include($this->settings['views']['row'])
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($this->headers) + 2 }}">
                            <div class="py-12 text-center bg-white dark:bg-gray-900">
                                <div class="flex justify-center items-center w-12 h-12 mx-auto bg-gray-100 rounded-full dark:bg-gray-800">
                                    <x-aura::icon icon="media" class="w-6 h-6 text-gray-400 dark:text-gray-500" />
                                </div>
                                <h3 class="mt-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('No media yet') }}</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('Drag and drop your first file anywhere in this window, or upload one directly.') }}
                                </p>
                                <div class="mt-4" x-data="{ canUpload: !!document.getElementById('file-upload') }">
                                    <x-aura::button.primary
                                        x-cloak
                                        x-show="canUpload"
                                        type="button"
                                        x-on:click="document.getElementById('file-upload').click()">
                                        {{ __('Upload Files') }}
                                    </x-aura::button.primary>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include($this->settings['views']['table_footer'])
</div>
