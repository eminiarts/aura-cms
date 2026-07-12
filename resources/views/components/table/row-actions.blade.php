<div class="flex justify-end items-center pr-2 space-x-1">
    @can('view', $row)
        <x-aura::tippy text="{{ __('View') }}" position="top" class="text-sm text-gray-400 bg-white">
            @if(isset($this->settings['view_in_modal']) && $this->settings['view_in_modal'])
                <button type="button" wire:click.prevent="$dispatch('openModal', { component: 'aura::resource-view-modal', arguments: { 'resource': {{ $row->id }}, 'type': '{{ $row->getType() }}' }, modalAttributes: {'slideOver': true }})"
                    class="inline-flex items-center p-1.5 text-gray-400 rounded-lg transition-colors duration-150 hover:text-gray-700 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    <x-aura::icon icon="view" size="xs" />
                    <span class="sr-only">{{ __('View') }}</span>
                </button>
            @else
                <a href="{{ $row->viewUrl() }}"
                    class="inline-flex items-center p-1.5 text-gray-400 rounded-lg transition-colors duration-150 hover:text-gray-700 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    <x-aura::icon icon="view" size="xs" />
                    <span class="sr-only">{{ __('View') }}</span>
                </a>
            @endif
        </x-aura::tippy>
    @endcan

    @can('update', $row)
        <x-aura::tippy text="{{ __('Edit') }}" position="top" class="text-sm text-gray-400 bg-white">
            @if(isset($this->settings['edit_in_modal']) && $this->settings['edit_in_modal'])
                <button type="button" wire:click.prevent="$dispatch('openModal', { component: 'aura::resource-edit-modal', arguments: { 'resource': {{ $row->id }}, 'type': '{{ $row->getType() }}' }, modalAttributes: {'slideOver': true }})"
                    class="inline-flex items-center p-1.5 text-gray-400 rounded-lg transition-colors duration-150 hover:text-gray-700 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    <x-aura::icon icon="edit" size="xs" />
                    <span class="sr-only">{{ __('Edit') }}</span>
                </button>
            @else
                <a href="{{ $row->editUrl() }}"
                    class="inline-flex items-center p-1.5 text-gray-400 rounded-lg transition-colors duration-150 hover:text-gray-700 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    <x-aura::icon icon="edit" size="xs" />
                    <span class="sr-only">{{ __('Edit') }}</span>
                </a>
            @endif
        </x-aura::tippy>
    @endcan
</div>
