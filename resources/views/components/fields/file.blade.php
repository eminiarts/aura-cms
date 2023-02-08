@php

if($this->post) {
    $selected = optional($this->post['fields'])[$field['slug']];
} else {
    $selected = null;
}

$files = null;
@endphp

<!-- Inside existing Livewire component -->
{{-- @dump($selected) --}}

@php
// get the the attachement with the id
// dd($this->post, $selected);
if($selected) {
    $files = \App\Aura\Resources\Attachment::find($selected)->sortBy(function($item) use ($selected) {
        return array_search($item->id, $selected);
    });
}
// dump($files->pluck('id'));
@endphp

<div class="w-full">
    <x-aura::fields.wrapper :field="$field">
        <!-- blade if files isset and count  -->
        @if(isset($files) && count($files) > 0)
        <div x-aura::data="orderMedia" x-aura::ref="container" data-slug="{{ $field['slug'] }}" class="flex flex-aura::col px-aura::0 mt-0 draggable-container">
            @foreach($files as $file)
            {{-- @dump($file) --}}
            <div class="w-full mb-2 draggable-item"  wire:key="{{ $field['slug'] }}_file_{{ $file->id }}" id="{{ $field['slug'] }}_file_{{ $file->id }}">

                <div class="relative">

                    <div
                    class="flex justify-between w-full overflow-hidden rounded-lg cursor-move draggable-handle group bg-gray-100 p-3 items-start">

                    <div class="flex space-x-aura::3 items-start w-full">
                        <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center text-primary-400 mt-1">
                            @include('attachment.icon', ['class' => 'h-4 w-4', 'attachment' => $file])
                        </div>

                        <div>
                            <div class="block text-sm mb-1">
                                <span class="">{{ $file->title }}</span>
                            </div>
                            <div class="flex items-center space-x-aura::1 text-xs opacity-50">
                                <div>{{ $file->readable_filesize }}</div>
                                <span class="opacity-20">•</span>
                                <div>{{ $file->readable_mime_type }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="shrink-0" wire:click="removeMediaFromField('{{ $field['slug'] }}', '{{ $file->id }}')">
                        <x-aura::icon icon="close" size="xs" class="text-primary-400 cursor-pointer hover:text-red-500" />
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
    // when alpine is ready
    document.addEventListener('alpine:init', () => {
        // define an alpinejs component named 'userDropdown'
        Alpine.data('orderMedia', () => ({
            init() {
                // console.log('init orderMedia');
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
                        // console.log('reordered!', slug, Array.from(container.querySelectorAll('.draggable-item'))
                        // .map(el => el.id)
                        // );

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
