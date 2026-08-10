{{-- Actions --}}
@if($this->bulkActions)
<div class="bulk-actions z-10">
    <div class="relative ml-1">
        <x-aura::dropdown align="right" width="60">
        <x-slot name="trigger">
            <span class="inline-flex rounded-lg">
                <button type="button"
                class="inline-flex gap-1.5 items-center px-3 py-2 text-sm font-medium leading-4 text-gray-700 bg-white rounded-lg ring-1 shadow-xs transition-colors duration-150 ring-gray-950/10 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:ring-white/10 dark:hover:bg-gray-700/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                {{ __('Actions') }}


                <svg class="-mr-0.5 w-4 h-4 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd"
                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                clip-rule="evenodd" />
            </svg>
        </button>
    </span>
</x-slot>

<x-slot name="content">
    <div class="p-1 w-60">
        <div role="none">
            @if($this->bulk_actions)
                @foreach($this->bulk_actions as $action => $data)
                    @if(is_array($data) && isset($data['modal']))
                    <!-- if it's an array and has a modal, then open the modal -->
                    <a wire:click="openBulkActionModal('{{ $action }}')"
                    class="flex items-center px-3 py-1.5 text-sm text-gray-700 rounded-md transition-colors duration-150 cursor-pointer group hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-white/5"
                    role="menuitem" tabindex="-1" id="menu-item-6">
                        {{ $data['label'] }}
                    </a>
                    @else
                        @php
                            $parameters = is_array($data) ? ($data['parameters'] ?? []) : [];
                            $method = is_array($data) && ($data['method'] ?? null) === 'collection'
                                ? 'bulkCollectionAction'
                                : 'bulkAction';
                        @endphp

                        @if($parameters)
                            <div class="px-3 py-2 space-y-2" x-data="{ parameters: {} }">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                    {{ $data['label'] }}
                                </p>

                                @foreach($parameters as $parameter => $declaration)
                                    <label class="block space-y-1 text-xs text-gray-600 dark:text-gray-300">
                                        <span>{{ $declaration['label'] }}</span>

                                        @if(($declaration['type'] ?? null) === 'boolean')
                                            <input type="checkbox" x-model="parameters.{{ $parameter }}"
                                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        @elseif(isset($declaration['options']) && is_array($declaration['options']))
                                            <select x-model="parameters.{{ $parameter }}"
                                                class="block w-full rounded-md border-0 py-1.5 text-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:ring-white/10">
                                                <option value="">{{ __('Select an option') }}</option>
                                                @foreach($declaration['options'] as $value => $label)
                                                    <option value="{{ is_int($value) ? $label : $value }}">
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input
                                                type="{{ in_array($declaration['type'] ?? null, ['integer', 'float'], true) ? 'number' : 'text' }}"
                                                @if(($declaration['type'] ?? null) === 'float') step="any" @endif
                                                x-model="parameters.{{ $parameter }}"
                                                class="block w-full rounded-md border-0 py-1.5 text-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:ring-white/10">
                                        @endif
                                    </label>
                                @endforeach

                                <button type="button"
                                    x-on:click="$wire.{{ $method }}(@js($action), parameters)"
                                    class="inline-flex items-center px-2.5 py-1.5 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-500">
                                    {{ __('Apply') }}
                                </button>
                            </div>
                        @else
                            <button type="button" wire:click="{{ $method }}(@js($action))"
                                class="flex items-center px-3 py-1.5 w-full text-sm text-left text-gray-700 rounded-md transition-colors duration-150 cursor-pointer group hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-white/5"
                                role="menuitem" tabindex="-1">
                                {{ is_array($data) ? $data['label'] : $data }}
                            </button>
                        @endif
                    @endif
                @endforeach
            @endif
        </div>
    </div>
</x-slot>
</x-aura::dropdown>
</div>
</div>
@endif
