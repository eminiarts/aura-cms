<div id="table-columns-button" class="relative">
    <x-aura::dropdown align="right" width="60">
        <x-aura::slot name="trigger">
            <x-aura::button.border>
                <x-aura::slot:icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2">
                        </path>
                    </svg>
        </x-aura::slot>
        Settings
        </x-aura::button.border>
        </x-aura::slot>

        <x-aura::slot name="content">
            <div class="w-60">
                <div class="p-4 sortable-wrapper" drag-root="reorder" role="none">

                    @if($this->modelColumns)
                    @foreach($this->modelColumns as $key => $label)
                    <label class="flex items-center py-2 cursor-pointer space-1-2 hover:bg-gray-100 dark:hover:bg-gray-900 sortable"
                        for="colum_{{$key}}" id="{{ $key }}">

                        <x-aura::input.checkbox wire:model="columns.{{ $key }}" value="true" id="colum_{{$key}}" />

                        <span class="flex items-center flex-aura::1 px-aura::4 text-sm text-gray-700 dark:text-gray-200 group" role="menuitem"
                            tabindex="-1" id="menu-item-6">
                            {{ $label }}
                        </span>

                        <div class="cursor-move drag-handle">
                            <svg class="w-4 h-4 mr-2 text-gray-400" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 8.5H21M3 15.5H21" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>

                    </label>
                    @endforeach
                    @endif

                    <div class="flex items-center">
                        <x-aura::label value="Pro Seite" class="hidden mt-1 mr-2 sm:block" />
                        <x-aura::input.group borderless inline paddingless for="perPage" label="">
                            <x-aura::input.select wire:model="perPage" id="perPage" class="rounded-md bg-gray-50 ">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </x-aura::input.select>
                        </x-aura::input.group>
                    </div>

                </div>
            </div>
        </x-aura::slot>
    </x-aura::dropdown>
</div>
