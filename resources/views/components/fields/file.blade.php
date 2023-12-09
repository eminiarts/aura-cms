@php

if($this->post) {
    $selected = optional($this->post['fields'])[$field['slug']];
} else {
    $selected = null;
}

$files = null;
@endphp

@php
if($selected) {
    $files = \Eminiarts\Aura\Resources\Attachment::find($selected)?->sortBy(function($item) use ($selected) {
        return array_search($item->id, $selected);
    });
}
@endphp

<div class="relative w-full" wire:key="edit-files-{{ $field['slug'] }}">
    <x-aura::fields.wrapper :field="$field">
        <!-- blade if files isset and count  -->
        @if(isset($files) && count($files) > 0)
        <div x-data="orderMedia" x-ref="container" data-slug="{{ $field['slug'] }}" class="flex flex-col px-0 mt-0 draggable-container">
            @foreach($files as $file)
            {{-- @dump($file) --}}
            <div class="w-full mb-2 draggable-item"  wire:key="{{ $field['slug'] }}_file_{{ $file->id }}" id="{{ $field['slug'] }}_file_{{ $file->id }}">

                <div class="relative flex items-start justify-between">

                    <div
                    class="flex items-start justify-between w-full p-3 overflow-hidden bg-gray-100 rounded-lg cursor-move draggable-handle group">

                        <div class="flex items-start w-full space-x-3">
                            <div class="flex items-center justify-center w-8 h-8 mt-1 rounded-full shrink-0 bg-primary-100 text-primary-400">
                                @include('aura::attachment.icon', ['class' => 'h-4 w-4', 'attachment' => $file])
                            </div>

                            <div class="flex-1 overflow-hidden text-sm truncate text-ellipsis whitespace-nowrap">
                                <div class="block mb-1">
                                    <span class="">{{ $file->title }}</span>
                                </div>
                                <div class="flex items-center space-x-1 text-xs opacity-50">
                                    <div>{{ $file->readable_filesize }}</div>
                                    <span class="opacity-20">•</span>
                                    <div>{{ $file->readable_mime_type }}</div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="absolute top-2 right-2" wire:click="removeMediaFromField('{{ $field['slug'] }}', '{{ $file->id }}')">
                        <x-aura::icon icon="close" size="xs" class="cursor-pointer text-primary-400 hover:text-red-500" />
                    </div>

            </div>
        </div>
        @endforeach
    </div>
    @endif

    <livewire:aura::media-uploader :table="false" :field="$field" :selected="$selected" :button="true" :model="app('Eminiarts\Aura\Resources\Attachment')" wire:key="media-uploader-{{ $field['slug'] }}" />
</x-aura::fields.wrapper>
</div>


@once
@push('scripts')

<script>
    // when alpine is ready
    document.addEventListener('alpine:init', () => {
        // define an alpinejs component named 'userDropdown'
        Alpine.data('orderMedia', () => ({
            init() {
                var container = this.$refs.container;
                // get data-slug attribute from container
                var slug = container.getAttribute('data-slug');
                const sortable = new window.Sortable(container, {
                    draggable: '.draggable-item',
                    handle: '.draggable-handle',
                    mirror: {
                        constrainDimensions: true,
                    },
                });
                sortable.on('sortable:stop', () => {
                    setTimeout(() => {
                        @this.reorderMedia(
                        slug,
                        Array.from(container.querySelectorAll('.draggable-item'))
                        .map(el => el.id)
                        )

                        // Livewire.emit('refreshComponent');
                        // $emit('refreshComponent')
                    }, 0)
                })
            }
        }));
    })

</script>

@endpush
@endonce
