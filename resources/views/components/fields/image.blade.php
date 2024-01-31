@php

if($this->post) {
    $selected = optional($this->post['fields'])[$field['slug']];
} else {
    $selected = null;
}

// Temporary fix for single image fields
if($selected && !is_array($selected)) {
    $selected = [$selected];
}

$files = null;
@endphp

@php
if($selected) {
    $files = \Eminiarts\Aura\Resources\Attachment::find($selected)->sortBy(function($item) use ($selected) {
        return array_search($item->id, $selected);
    });
}
@endphp


<div class="relative w-full">

    <x-aura::fields.wrapper :field="$field">
        <div>
            @if(isset($files) && count($files) > 0)
                <div x-data="{
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
                }" x-ref="container" data-slug="{{ $field['slug'] }}" class="flex flex-wrap px-0 mt-0 draggable-container" wire:key="edit-image-{{ $field['slug'] }}">
                    @foreach($files as $file)
                    <div class="w-32 mb-1 mr-2 draggable-item"  wire:key="{{ $field['slug'] }}_file_{{ $file->id }}" id="{{ $field['slug'] }}_file_{{ $file->id }}">

                        <div class="relative">

                            <div
                            class="w-full overflow-hidden rounded-lg cursor-move draggable-handle group aspect-w-10 aspect-h-7 bg-gray-50 focus-within:ring-2 focus-within:ring-primary-500 focus-within:ring-offset-2 focus-within:ring-offset-gray-100">
                                @if(in_array($file->mime_type, ['image/jpeg', 'image/png', 'image/jpg']))
                                <img src="/storage/{{ $file->url }}" alt="" class="object-cover pointer-events-none group-hover:opacity-75">
                                @else

                                <div class="flex items-center justify-center text-gray-300">
                                    @include('aura::attachment.icon', ['class' => 'h-8 w-8', 'attachment' => $file])
                                </div>

                                @endif
                            </div>
                            <div class="absolute top-2 right-2">


                                <div wire:click="removeMediaFromField('{{ $field['slug'] }}', '{{ $file->id }}')">
                                    <x-aura::icon icon="close" size="xs" class="text-gray-400 bg-white rounded-full cursor-pointer hover:text-red-500" />
                                </div>

                            </div>

                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        <livewire:aura::media-uploader :table="false" :field="$field" :selected="$selected" :button="true" :model="app('Eminiarts\Aura\Resources\Attachment')" wire:key="media-uploader-{{ $field['slug'] }}"/>

    </x-aura::fields.wrapper>

   
</div>
