@php

    if($this->form) {
        $selected = optional($this->form['fields'])[$field['slug']];
    } else {
        $selected = null;
    }

    // Temporary fix for single image fields
    if($selected && !is_array($selected)) {
        $selected = [$selected];
    }

    $files = null;

    // Resolve a gate-checked `disabled` closure here, where the form is in scope.
    $disabled = (bool) $field['field']->isDisabled($this->form, $field);
@endphp

@php
    if($selected) {
        $attachmentClass = config('aura.resources.attachment', \Aura\Base\Resources\Attachment::class);
        $files = $attachmentClass::find($selected)?->sortBy(function($item) use ($selected) {
            return array_search($item->id, $selected);
        });
    }
@endphp


    @php
        // One picked image (or none): a 40px preview tile, the "Choose image" button and Remove in one row.
        // Several: the sortable gallery above the row. The user's avatar field previews the record's own
        // avatar (circle, initials in its tint) while it has no image.
        $fileCount = isset($files) ? count($files) : 0;
        $singleFile = $fileCount === 1 ? $files->first() : null;
        $pickerRecord = $this->model ?? null;
        $isAvatar = ($field['slug'] ?? null) === 'avatar' && $pickerRecord instanceof \Aura\Base\Resource && $pickerRecord->avatarShape() === 'circle';
        $tileShape = $isAvatar ? 'rounded-full' : 'rounded-lg';
        $isImageFile = fn ($file) => in_array($file->mime_type, ['image/jpeg', 'image/png', 'image/jpg'], true);
    @endphp

    <x-aura::fields.wrapper :field="$field" class="relative">
        <div class="z-[2] relative">
            @if($fileCount > 1)
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
                }" x-ref="container" data-slug="{{ $field['slug'] }}"
                     class="flex flex-wrap px-0 mt-0 mb-2 draggable-container">
                    @foreach($files as $file)
                        <div class="mr-2 mb-1 w-32 draggable-item" wire:key="{{ $field['slug'] }}_file_{{ $file->id }}"
                             id="{{ $field['slug'] }}_file_{{ $file->id }}">

                            <div class="relative">

                                <div
                                    class="overflow-hidden w-full bg-gray-50 rounded-lg ring-1 ring-inset ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 {{ ! $disabled ? 'cursor-move' : '' }} draggable-handle group aspect-w-10 aspect-h-7 focus-within:ring-2 focus-within:ring-primary-500 focus-within:ring-offset-2 focus-within:ring-offset-gray-100"
                                >
                                    @if($isImageFile($file))
                                        <img src="{{ $file->thumbnail('xs') }}" alt=""
                                             class="object-cover pointer-events-none group-hover:opacity-75">
                                    @else

                                        <div class="flex justify-center items-center text-gray-300">
                                            @include('aura::attachment.icon', ['class' => 'h-8 w-8', 'attachment' => $file])
                                        </div>

                                    @endif
                                </div>
                                @if(! $disabled)
                                    <div class="absolute top-2 right-2">


                                        <div wire:click="removeMediaFromField('{{ $field['slug'] }}', '{{ $file->id }}')">
                                            <x-aura::icon icon="close" size="xs"
                                                          class="text-gray-400 bg-white rounded-full cursor-pointer hover:text-red-500"/>
                                        </div>

                                    </div>
                                @endif

                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="flex flex-wrap gap-3 items-center" data-image-picker="{{ $field['slug'] }}">
            @if($fileCount <= 1)
                @if($singleFile)
                    <span class="flex overflow-hidden justify-center items-center shrink-0 size-10 {{ $tileShape }} bg-gray-50 ring-1 ring-inset ring-gray-950/10 dark:bg-white/5 dark:ring-white/10"
                          wire:key="{{ $field['slug'] }}_file_{{ $singleFile->id }}" data-image-preview>
                        @if($isImageFile($singleFile))
                            <img src="{{ $singleFile->thumbnail('xs') }}" alt="" class="size-full {{ $isAvatar ? 'object-cover' : 'object-contain p-1' }}">
                        @else
                            @include('aura::attachment.icon', ['class' => 'h-5 w-5 text-gray-400', 'attachment' => $singleFile])
                        @endif
                    </span>
                @elseif($isAvatar && $pickerRecord->exists)
                    <x-aura::avatar :record="$pickerRecord" size="lg" data-image-preview />
                @else
                    <span class="flex justify-center items-center shrink-0 size-10 {{ $tileShape }} border border-dashed border-gray-300 text-gray-400 dark:border-white/15 dark:text-gray-500" data-image-preview aria-hidden="true">
                        <x-aura::icon icon="media" size="sm" />
                    </span>
                @endif
            @endif

            <livewire:aura::media-uploader
                :table="false"
                :field="$field"
                :selected="$selected"
                :button="true"
                :model="app('Aura\Base\Resources\Attachment')"
                :for="get_class($this->model)"
                :disabled="$disabled"
            />

            @if($singleFile && ! $disabled)
                <x-aura::button.transparent wire:click="removeMediaFromField('{{ $field['slug'] }}', '{{ $singleFile->id }}')">
                    {{ __('Remove') }}
                </x-aura::button.transparent>
            @endif
        </div>

    </x-aura::fields.wrapper>
