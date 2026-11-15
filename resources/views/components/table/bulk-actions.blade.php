{{-- Actions: one "more" icon button the size of the table controls, the same trigger every index page uses
     for its overflow menu. The resource editor link (desktop only) joins this menu, so a page never shows
     two overflow buttons (components/table/resource-editor-button stays away when this menu renders). --}}
@if($this->bulkActions)
@php
    $itemClass = 'flex items-center px-3 py-1.5 text-sm text-gray-700 rounded-md transition-colors duration-150 cursor-pointer group hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-white/5';
    $withEditorLink = ($this->settings['resource_editor'] ?? false) && config('aura.features.resource_editor') && $model->isAppResource();
@endphp
<div class="bulk-actions z-10 shrink-0">
    <x-aura::dropdown align="right" width="w-60">
        <x-slot name="trigger">
            <x-aura::button.border type="button" class="w-9 !px-0 [&>span:first-child]:ml-0" title="{{ __('Actions') }}" aria-label="{{ __('Actions') }}" aria-haspopup="menu">
                <x-slot:icon>
                    <x-aura::icon.dots />
                </x-slot>
            </x-aura::button.border>
        </x-slot>

        <x-slot name="content">
            <div role="none">
                @include('aura::components.table.actions-menu-items', ['itemClass' => $itemClass])
                {{-- Phones: the column chooser opens from here (components/table/settings, phoneMenu 'actions'). --}}
                @if ($columnsInActionsMenu ?? false)
                    <div class="sm:hidden">
                        <button type="button" x-on:click="setTimeout(() => window.dispatchEvent(new CustomEvent('aura-table-columns')))" class="w-full {{ $itemClass }}" role="menuitem" tabindex="-1">
                            {{ __('Columns') }}
                        </button>
                        <div class="-mx-1 my-1 h-px bg-gray-950/5 dark:bg-white/10" role="separator"></div>
                    </div>
                @endif
                @if($this->bulk_actions)
                    @foreach($this->bulk_actions as $action => $data)
                        @if(is_array($data) && isset($data['modal']))
                            <a wire:click="openBulkActionModal('{{ $action }}', {{json_encode($data)}})" class="{{ $itemClass }}" role="menuitem" tabindex="-1">
                                {{ $data['label'] }}
                            </a>
                        @elseif(is_array($data) && optional($data)['method'] == 'collection')
                            <a wire:click="bulkCollectionAction('{{ $action }}', {{json_encode($data)}})" class="{{ $itemClass }}" role="menuitem" tabindex="-1">
                                {{ $data['label'] }}
                            </a>
                        @else
                            <a wire:click="bulkAction('{{ $action }}')" class="{{ $itemClass }}" role="menuitem" tabindex="-1">
                                {{ is_array($data) ? $data['label'] : $data }}
                            </a>
                        @endif
                    @endforeach
                @endif

                @if ($withEditorLink)
                    <div class="hidden sm:block" data-resource-editor-button>
                        <div class="-mx-1 my-1 h-px bg-gray-950/5 dark:bg-white/10" role="separator"></div>
                        <a href="{{ route('aura.resource.editor', $model::getSlug()) }}" class="gap-2 {{ $itemClass }}" role="menuitem" tabindex="-1">
                            <x-aura::icon icon="cog" size="xs" class="text-gray-400" />
                            {{ __('Edit resource') }}
                        </a>
                    </div>
                @endif
            </div>
        </x-slot>
    </x-aura::dropdown>
</div>
@endif
