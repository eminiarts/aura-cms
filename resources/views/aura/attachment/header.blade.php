@if(optional(optional($this)->field)['name'])

<div class="flex items-center justify-between mt-6">
            <div>
                @if(optional(optional($this)->field)['name'])
                <h1 class="text-2xl font-semibold">{{ $this->field['name'] }}</h1>
                @else
                <h1 class="text-2xl font-semibold">{{ $model->pluralName() }}</h1>
                @endif

                @if(optional(optional($this)->field)['description'])
                <span class="text-primary-500">{{ $this->field['description'] }}</span>
                @endif
                </h3>
            </div>


        </div>
@elseif ($this->settings['media_page'] ?? false)
    {{-- The Media library page (livewire/media-uploader passes media_page): the header of the media
         library grid page. Breadcrumb (phones hide it), the title with the file count after search and
         filters, "Upload files" as the primary action, a 36px icon button on phones. The uploader around
         this table owns the hidden #file-upload input. With a media library plugin installed (its own
         "Media library" page), this page is the plain list of every uploaded file and says so: "Files". --}}
    @php
        $attachmentsTitle = Route::has('aura.media-library.index') ? __('Files') : __('Media library');
    @endphp
    <div class="hidden sm:block">
        <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" />
            <x-aura::breadcrumbs.li :title="$attachmentsTitle" />
        </x-aura::breadcrumbs>
    </div>

    <div class="flex gap-4 justify-between items-center mt-1 sm:mt-8 sm:mb-8 sm:min-h-[3.375rem] sm:items-end">
        <h1 class="aura-page-title">
            {{ $attachmentsTitle }}
            @if (isset($rows) && $rows->total() > 0)
                <span class="aura-page-count">{{ number_format($rows->total()) }}</span>
            @endif
        </h1>

        <x-aura::button.primary
            type="button"
            class="shrink-0 max-sm:w-9 max-sm:px-0 max-sm:[&>span:first-child]:ml-0"
            title="{{ __('Upload files') }}"
            x-on:click="document.getElementById('file-upload')?.click()"
            data-media-upload>
            <x-slot:icon>
                <x-aura::icon.upload />
            </x-slot>
            <span class="sr-only sm:not-sr-only">{{ __('Upload files') }}</span>
        </x-aura::button.primary>
    </div>
@endif
