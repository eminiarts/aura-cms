<div class="flex flex-col mt-2" wire:key="table-index">
    <div class="overflow-hidden bg-white rounded-xl ring-1 shadow-sm dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
        <div class="overflow-x-auto min-w-full align-middle">

            <table class="min-w-full divide-y divide-gray-100 dark:divide-white/5">
                @include($this->settings['views']['table_header'])

                <tbody class="divide-y divide-gray-100 dark:divide-white/5">

                    @forelse($rows as $row)
                        @include($this->settings['views']['row'])
                    @empty

                    <tr>
                        <td colspan="{{ count($this->headers) + 2 }}">
                            <div class="flex flex-col items-center px-6 py-16 text-center">
                                <div class="p-3 bg-gray-50 rounded-full dark:bg-gray-700/50">
                                    <svg class="w-6 h-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                    </svg>
                                </div>

                                <h3 class="mt-3 text-sm font-medium text-gray-900 dark:text-white">{{ __('No entries available') }}</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Entries you create will show up here.') }}</p>
                            </div>
                        </td>
                    </tr>

                    @endforelse

                </tbody>
            </table>

        </div>
    </div>

    @include($this->settings['views']['table_footer'])

</div>
