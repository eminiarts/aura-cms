@if($this->settings['edit_in_modal'])
<a href="#" wire:click.prevent="$dispatch('openModal', { component: 'aura::resource-edit-modal', arguments: { 'resource': {{ $row->id }}, 'type': '{{ $row->getType() }}' }, modalAttributes: {'slideOver': true }})">
    <x-aura::icon icon="edit" />
</a>
@else
<div class="flex justify-end pr-4 space-x-2">
    @can('view', $row)
    <x-aura::tippy text="{{ __('View') }}" position="top" class="text-sm text-gray-400 bg-white">
        <x-aura::button.transparent :href="$row->viewUrl()" size="xs">
            <x-aura::icon icon="view" size="xs" />
            <span class="sr-only">{{ __('View') }}</span>
        </x-aura::button.transparent>
    </x-aura::tippy>
    @endcan

    @can('update', $row)
    <x-aura::tippy text="{{ __('Edit') }}" position="top" class="text-sm text-gray-400 bg-white">
        <x-aura::button.transparent :href="$row->editUrl()" size="xs">
            <x-aura::icon icon="edit" size="xs" />
            <span class="sr-only">{{ __('Edit') }}</span>
        </x-aura::button.transparent>
    </x-aura::tippy>
    @endcan
</div>
@endif
