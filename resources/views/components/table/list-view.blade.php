<div class="flex flex-col mt-2" wire:key="table-index">
    <div
        class="min-w-full overflow-x-auto align-middle border border-gray-400/30 sm:rounded-lg dark:border-gray-700">

        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            @include($this->settings['views']['table_header'])

            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">

                @forelse($rows as $row)
                    @include($this->settings['views']['row'])
                @empty

                <tr>
                    <td colspan="{{ count($this->headers) + 2 }}">
                        <div class="py-8 text-center bg-white dark:bg-gray-900">
                            <svg class="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636">
                                </path>
                            </svg>

                            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No entries available') }}</h3>
                        </div>
                    </td>
                </tr>

                @endforelse

            </tbody>
        </table>

    </div>

    @include($this->settings['views']['table_footer'])

</div>
