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
    $files = \App\Aura\Resources\Attachment::find($selected)->sortBy(function($item) use ($selected) {
        return array_search($item->id, $selected);
    });
}
@endphp

<div class="w-full">
    <x-aura::fields.wrapper :field="$field">
        @if(isset($files) && count($files) > 0)
        <div x-aura::data="orderMedia" x-aura::ref="container" data-slug="{{ $field['slug'] }}" class="flex flex-aura::wrap px-aura::0 mt-0 draggable-container">
            @foreach($files as $file)
            <div class="w-32 mb-1 mr-2 draggable-item"  wire:key="{{ $field['slug'] }}_file_{{ $file->id }}" id="{{ $field['slug'] }}_file_{{ $file->id }}">

                <div class="relative">

                    <div
                    class=" w-full overflow-hidden rounded-lg cursor-move draggable-handle group aspect-w-10 aspect-h-7 bg-gray-50 focus-within:ring-2 focus-within:ring-primary-500 focus-within:ring-offset-2 focus-within:ring-offset-gray-100">
                    @if(in_array($file->mime_type, ['image/jpeg', 'image/png', 'image/jpg']))
                    <img src="/storage/{{ $file->url }}" alt="" class="object-cover pointer-events-none group-hover:opacity-75">
                    @else
                    
                    <div class="text-gray-300 flex items-center justify-center">
                        @include('attachment.icon', ['class' => 'h-8 w-8', 'attachment' => $file])
                    </div>

                    @endif
                </div>
                <div class="absolute top-2 right-2">


                    <div wire:click="removeMediaFromField('{{ $field['slug'] }}', '{{ $file->id }}')">
                        <x-aura::icon icon="close" size="xs" class="rounded-full bg-white text-gray-400 cursor-pointer hover:text-red-500" />
                    </div>

                </div>

            </div>
        </div>
        @endforeach
    </div>
    @endif

    <x-aura::button.light wire:click="$emit('openModal', 'media-manager', {{ json_encode(['slug' => $field['slug'], 'selected' => $selected]) }})">
        <x-aura::slot:icon>
            <x-aura::icon icon="media" class="" />
        </x-aura::slot>

        <span>Media Manager</span>
    </x-aura::button.light>
</x-aura::fields.wrapper>
</div>


@once
@push('scripts')

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('orderMedia', () => ({
            init() {
                var container = this.$refs.container;
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
                    }, 0)
                })
            }
        }));
    })

</script>

@endpush
@endonce
