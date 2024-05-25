@php

    if($this->form) {
        $selected = optional($this->form['fields'])[$field['slug']];
    } else {
        $selected = null;
    }

    $files = null;
@endphp

@php
    if($selected) {
        $files = \Aura\Base\Resources\Attachment::find($selected)?->sortBy(function($item) use ($selected) {
            return array_search($item->id, $selected);
        });
    }
@endphp

<div class="relative w-full z-[2]" wire:key="edit-files-{{ $field['slug'] }}">
    <x-aura::fields.wrapper :field="$field">
        <div class="z-[2] relative">
        <!-- blade if files isset and count  -->
        @if(isset($files) && count($files) > 0)
            <div x-data="{
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
                    }, 0)
                })
            }
        }" x-ref="container" data-slug="{{ $field['slug'] }}" class="flex flex-col px-0 mt-0 draggable-container">
                @foreach($files as $file)
                    {{-- @dump($file) --}}
                    <div class="mb-2 w-full draggable-item" wire:key="{{ $field['slug'] }}_file_{{ $file->id }}"
                         id="{{ $field['slug'] }}_file_{{ $file->id }}">

                        <div class="flex relative justify-between items-start">

                            <div
                                    class="flex overflow-hidden justify-between items-start p-3 w-full bg-gray-100 rounded-lg cursor-move dark:bg-gray-800 draggable-handle group">

                                <div class="flex items-start space-x-3 w-full">
                                    <div class="flex justify-center items-center mt-1 w-8 h-8 rounded-full shrink-0 bg-primary-100 text-primary-400">
                                        @include('aura::attachment.icon', ['class' => 'h-4 w-4', 'attachment' => $file])
                                    </div>

                                    <div class="overflow-hidden flex-1 text-sm truncate whitespace-nowrap text-ellipsis">
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

                            <div class="absolute top-2 right-2"
                                 wire:click="removeMediaFromField('{{ $field['slug'] }}', '{{ $file->id }}')">
                                <x-aura::icon icon="close" size="xs"
                                              class="cursor-pointer text-primary-400 hover:text-red-500"/>
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>
        @endif
        </div>

        @php
            unset($field['field']);

            // ray($field, $selected);
        @endphp

        <livewire:aura::media-uploader
            :table="false"
            :field="$field"
            :selected="$selected"
            :button="true"
            :model="app('Aura\Base\Resources\Attachment')"
            :for="get_class($this->model)"
            wire:key="media-uploader-{{ $field['slug'] }}"
        />
    </x-aura::fields.wrapper>
</div>
