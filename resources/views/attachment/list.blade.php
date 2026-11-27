@php
    // The list look of the other index pages (components/table/list-view): no card, a header hairline,
    // row dividers, the footer. The file's thumbnail leads the Name cell. Phones get the stacked list:
    // thumbnail, name, "JPEG · 47 KB". Index rows open the details panel (as the grid's cards do);
    // in a picker a row toggles its selection.
    $visibleColumnKeys = collect($this->headers ?: [])->keys()->filter(fn ($key) => optional($this->columns)[$key])->values();
    $columnTypes = $visibleColumnKeys->mapWithKeys(fn ($key) => [$key => optional($this->model->fieldBySlug($key))['type']])->all();
    $isPicker = (bool) $field;
    $rowClick = fn ($row) => $isPicker
        ? "toggleRow(\$event, {$row->id})"
        : "Livewire.dispatch('open-attachment-details', { id: {$row->id}, ids: rows.map(Number) })";
@endphp

<div class="flex flex-col mt-2" wire:key="table-index">
    @if ($rows->isEmpty())
        <div class="flex flex-col items-center px-6 py-14 text-center">
            <div class="flex justify-center items-center mb-3 w-10 h-10 rounded-lg bg-gray-950/[0.04] text-gray-400 dark:bg-white/5 dark:text-gray-500" aria-hidden="true">
                <x-aura::icon icon="media" class="size-5" />
            </div>
            <h3 class="aura-section-title">{{ __('No media yet') }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Drag and drop your first file anywhere in this window, or upload one directly.') }}
            </p>
        </div>
    @else
        <div class="hidden overflow-x-auto min-w-full align-middle sm:block">
            <table class="min-w-full">
                @include($this->settings['views']['table_header'])

                <tbody class="border-b divide-y divide-gray-200/60 border-gray-200/80 dark:divide-white/[0.06] dark:border-white/10">
                    @foreach ($rows as $row)
                        <tr class="transition-colors duration-150 ease-in-out cursor-pointer row-hover:bg-gray-50/80 dark:row-hover:bg-white/[0.04]"
                            wire:key="attachment-row-{{ $row->id }}"
                            data-id="{{ $row->id }}"
                            :class="{ 'bg-primary-50/60 dark:bg-primary-500/10': selected.includes('{{ $row->id }}') }"
                            x-on:click="if (! $event.target.closest('a, button, input, label, select, [data-row-click-ignore]') && ! window.getSelection().toString()) { {{ $rowClick($row) }} }">

                            @if ($this->settings['selectable'])
                                <x-aura::table.cell class="relative pr-0">
                                    <span x-show="selected.includes('{{ $row->id }}')" x-cloak
                                        class="absolute inset-y-0 left-0 w-0.5 bg-primary-600" aria-hidden="true"></span>
                                    <x-aura::input.checkbox
                                        id="checkbox_{{ $row->id }}"
                                        x-bind:checked="selected.includes('{{ $row->id }}')"
                                        hideLabel
                                        :label="$row->id"
                                        :value="$row->id"
                                        x-on:click.stop.prevent="toggleRow($event, {{ $row->id }})"
                                    />
                                </x-aura::table.cell>
                            @endif

                            @foreach ($visibleColumnKeys as $key)
                                @if ($key === 'name')
                                    <td class="px-4 py-2.5 text-sm font-medium text-gray-900 dark:text-white">
                                        <div class="flex gap-3 items-center min-w-0">
                                            @include('aura::attachment.list-thumbnail')
                                            <p x-data x-file-name class="min-w-0 truncate" title="{{ $row->name }}">{{ $row->name }}</p>
                                        </div>
                                    </td>
                                @elseif ($key === 'mime_type')
                                    <td class="px-4 py-2.5 text-sm text-gray-600 whitespace-nowrap dark:text-gray-300">{{ $row->readable_mime_type }}</td>
                                @elseif ($key === 'size')
                                    <td class="px-4 py-2.5 text-sm text-right text-gray-600 whitespace-nowrap tabular-nums dark:text-gray-300">{{ $row->readable_filesize }}</td>
                                @else
                                    @php($display = $row->display($key))
                                    <td class="px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300">
                                        @if ($display === null || (is_string($display) && trim($display) === ''))
                                            <span class="text-gray-400 dark:text-gray-500">–</span>
                                        @else
                                            {!! $display !!}
                                        @endif
                                    </td>
                                @endif
                            @endforeach

                            @if ($this->settings['actions'])
                                <td class="px-3 py-2">
                                    @include('aura::components.table.row-actions')
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Phones: the stacked rows of the other index pages. --}}
        <ul class="border-t border-b divide-y divide-gray-200/60 border-gray-200/80 sm:hidden dark:divide-white/[0.06] dark:border-white/10">
            @foreach ($rows as $row)
                <li wire:key="attachment-mobile-row-{{ $row->id }}">
                    <button type="button"
                        x-on:click="{{ $rowClick($row) }}"
                        class="flex gap-3 items-center py-3 w-full text-left transition-colors duration-150 active:bg-gray-50 dark:active:bg-white/[0.04] focus:outline-none focus-visible:bg-gray-50 dark:focus-visible:bg-white/[0.04]"
                        :class="{ 'bg-primary-50/60 dark:bg-primary-500/10': selected.includes('{{ $row->id }}') }"
                        @if ($isPicker) :aria-pressed="selected.includes('{{ $row->id }}').toString()" @endif>
                        @include('aura::attachment.list-thumbnail')
                        <span class="flex-1 min-w-0">
                            <span x-data x-file-name class="block text-sm font-medium text-gray-900 truncate dark:text-white" title="{{ $row->name }}">{{ $row->name }}</span>
                            <span class="flex gap-1.5 items-center mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                <span>{{ $row->readable_mime_type }}</span>
                                <span class="text-gray-300 dark:text-gray-600" aria-hidden="true">&middot;</span>
                                <span class="tabular-nums">{{ $row->readable_filesize }}</span>
                            </span>
                        </span>
                        @unless ($isPicker)
                            <x-aura::icon.caret direction="right" class="w-4 h-4 text-gray-400 shrink-0" />
                        @endunless
                    </button>
                </li>
            @endforeach
        </ul>
    @endif

    @include($this->settings['views']['table_footer'])
</div>
