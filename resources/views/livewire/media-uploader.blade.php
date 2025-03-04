<div>

    <div>
        @if($media && count($media))
        <div x-data="{ media: {{ json_encode($media) }}, loading: true }"
            class="flex fixed inset-0 z-50 items-end px-4 py-6 pointer-events-none sm:items-start sm:p-6">
            <div class="flex flex-col items-end space-y-4 w-full sm:items-end">

                @foreach($media as $key => $file)

                <div
                    class="overflow-hidden relative w-full max-w-sm bg-white rounded-lg ring-1 ring-black ring-opacity-5 shadow-lg pointer-events-auto"
                    x-data="{loading: true}" x-show="loading" x-init="setTimeout(() => { loading = false }, 3000)"
                    x-transition:leave="transition ease-linear duration-1000" x-transition:leave-end="opacity-0">
                    <div class="p-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-green-400" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="flex-1 pt-0.5 ml-3 w-0">
                                <p class="text-sm font-medium text-gray-900">Successfully uploaded</p>
                                <p class="mt-1 text-sm text-gray-500">{{ $file->getClientOriginalName() }}</p>
                            </div>
                            <div class="flex flex-shrink-0 ml-4">
                                <button type="button"
                                    class="inline-flex text-gray-400 bg-white rounded-md hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                                    <span class="sr-only">Close</span>
                                    <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path
                                            d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="absolute bottom-0 left-0 w-full h-1">
                        <div class="h-1 origin-left bg-primary-200 animate-countdown"></div>
                    </div>
                </div>

                @endforeach
            </div>

        </div>
        @endif
    </div>

    <div>
        @error('media')
            <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
        @enderror
        @error('media.*')
            <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
        @enderror
    </div>

    <div
        x-data="{
        isDropping: false,
        isUploading: false,
        progress: 0,
        disabled: {{ $disabled ? 'true' : 'false' }},
        init() {
            {{-- var i = 0;
            setInterval(() => {
                console.log(i, new Date().toLocaleTimeString());
                i++;
            }, 1000); --}}
        },
        handleFileSelect(event) {
            if (this.disabled) return;

            const maxFiles = {{ 20 }};
            if (event.target.files.length > maxFiles) {
                alert(`{{ __('Maximum of :count files can be uploaded at once', ['count' => 20]) }}`);
                event.target.value = '';
                return;
            }

            if (event.target.files.length) {
                Array.from(event.target.files).forEach(file => {
                    @this.uploadMultiple('media', file, function (success) {
                        $this.isUploading = false
                        $this.progress = 0
                    }, () => {
                        console.error('Upload error');
                    }, (event) => {
                        this.progress = event.detail.progress;
                    });
                });
            }
        },

        handleFileDrop(event) {
            if (this.disabled) return;

            const maxFiles = 20;
            if (event.dataTransfer.files.length > maxFiles) {
                alert(`{{ __('Maximum of :count files can be uploaded at once', ['count' => 20]) }}`);
                return;
            }

            if (event.dataTransfer.files.length > 0) {
                this.uploadFiles(event.dataTransfer.files)
            }
        },
        uploadFiles(files) {
            const $this = this

            console.log('upload multiple', files);

            this.isUploading = true

            @this.uploadMultiple('media', files,
            function (success) { //upload was a success and was finished
                console.log('upload success', success);
                $this.isUploading = false
                $this.progress = 0
            },
            function (error) { //an error occured
                console.log('upload error', error);
            },
            function (event) { //upload progress was made
                console.log('upload progress', event);
                $this.progress = event.detail.progress
            }
            )
        },
        handleDragOver(event) {
            if (event.dataTransfer.types.includes('Files')) {
                this.isDropping = true;
            }
        },
        removeUpload(filename) {
            @this.removeUpload('files', filename)
        },
    }">

        <div class=""
            x-on:drop="!disabled && handleFileDrop($event)"
            x-on:drop.prevent="!disabled && (isDropping = false)"
            x-on:dragover.prevent="!disabled && handleDragOver($event)"
            x-on:dragleave.prevent="!disabled && (isDropping = false)">

            <div
                x-on:dragover.prevent="!disabled && handleDragOver($event)"
                class="z-[1] absolute top-0 right-0 bottom-0 left-0"></div>

            <div class="p-4 mt-2 bg-gray-50 rounded-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700" x-show="isUploading" x-cloak>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 loading-ellipsis">Uploading</span>
                </div>
                <div class="bg-transparent dark:bg-gray-900 h-[4px] w-full" x-show="isUploading">
                    <style>
                        .loading-ellipsis:after {
                            overflow: hidden;
                            display: inline-block;
                            vertical-align: bottom;
                            -webkit-animation: ellipsis steps(4,end) 900ms infinite;
                            animation: ellipsis steps(4,end) 900ms infinite;
                            content: "\2026"; /* ascii code for the ellipsis character */
                            width: 0px;
                        }

                        @keyframes ellipsis {
                            to {
                                width: 1.25em;
                            }
                        }

                        @-webkit-keyframes ellipsis {
                            to {
                                width: 1.25em;
                            }
                        }
                        .progress-bar::before {
                            content: '';
                            display: block;
                            height: 100%;
                            transition: width 0.5s;
                            width: 0%;
                        }
                    </style>
                    <div class="bg-primary-500 h-[4px] progress-bar" :style="`width: ${progress}%;`">
                    </div>
                </div>
            </div>

            <div class="z-[2] relative">
                @if($button && !$disabled)
                    <div class="mt-2">
                        <x-aura::button.light
                            wire:click="$dispatch('openModal', { component: 'aura::media-manager', arguments: { model: {{ json_encode($for) }}, slug: '{{ $field['slug'] }}', selected: {{ json_encode($selected) }} }})">
                            <x-slot:icon>
                                <x-aura::icon icon="media" class="" />
                            </x-slot>

                            <span>Media Manager</span>
                        </x-aura::button.light>
                    </div>
                @elseif($disabled)
                    <div class="mt-2">
                        <x-aura::button.light disabled>
                            <x-slot:icon>
                                <x-aura::icon icon="media" class="" />
                            </x-slot>

                            <span>Media Manager is disabled</span>
                        </x-aura::button.light>
                    </div>
                @endif
            </div>

            <div class="flex justify-center items-center w-full" x-cloak x-show="isDropping">
                <div class="flex absolute top-0 right-0 bottom-0 left-0 z-30 justify-center items-center bg-gray-800 opacity-50"
                    >
                    <div
                        x-show="isDropping"
                    >
                            <span class="text-3xl text-white">{{ __('Release file to upload!') }}</span>
                    </div>
                </div>
            </div>

            <div class="flex justify-end mb-4">
                @if($upload && !$disabled)
                    <div x-data="{ 
                        openFileUpload() {
                            document.getElementById('file-upload').click();
                        }
                    }">
                        <x-aura::button.border 
                            type="button" 
                            class="flex items-center space-x-2" 
                            x-on:click="openFileUpload()">
                            <x-aura::icon.upload class="w-5 h-5" />
                            <span>{{ __('Upload Files') }}</span>
                        </x-aura::button.border>
                        
                        <input type="file" 
                            id="file-upload" 
                            multiple 
                            @change="handleFileSelect" 
                            class="hidden" 
                            wire:model="media" />
                    </div>
                @endif
            </div>

            @if($table && !$disabled)

            <div class="z-[2] relative flex flex-col">

                <div class="flex justify-between items-start">

    <div class="mb-6">
        <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-6 h-6 mr-0" />
            <x-aura::breadcrumbs.li :title="__('Attachments')"  />
        </x-aura::breadcrumbs.li>
    </div>

   <div>
   
    <div class="flex justify-end w-full">
                        <div x-data="{ 
                            openFileUpload() {
                                document.getElementById('file-upload').click();
                            }
                        }">
                            <x-aura::button.border 
                                type="button" 
                                class="flex items-center space-x-2" 
                                x-on:click="openFileUpload()">
                                <x-aura::icon.upload class="w-5 h-5" />
                                <span>{{ __('Upload Files') }}</span>
                            </x-aura::button.border>
                            
                            <input type="file" 
                                id="file-upload" 
                                multiple 
                                @change="handleFileSelect" 
                                class="hidden" 
                                wire:model="media" />
                        </div>
                    </div>
   
   </div>

    </div>

                <div class="flex justify-between items-center mt-0">
                    <h1 class="text-2xl font-semibold">
                        {{ __('Attachments') }}
                    </h1>

                   
                </div>
                <livewire:aura::table :model="$model" :field="$field" />
            </div>

            @endif
        </div>

    </div>
</div>
