<div>
        <div>
            @if($media && count($media))
            <div x-data="{ media: {{ json_encode($media) }}, loading: true }"
                class="fixed inset-0 z-50 flex items-end px-4 py-6 pointer-events-none sm:items-start sm:p-6">
                <div class="flex flex-col items-end w-full space-y-4 sm:items-end">

                    @foreach($media as $key => $file)

                    <div
                        class="relative w-full max-w-sm overflow-hidden bg-white rounded-lg shadow-lg pointer-events-auto ring-1 ring-black ring-opacity-5"
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
                                <div class="ml-3 w-0 flex-1 pt-0.5">
                                    <p class="text-sm font-medium text-gray-900">Successfully uploaded</p>
                                    <p class="mt-1 text-sm text-gray-500">{{ $file->getClientOriginalName() }}</p>
                                </div>
                                <div class="flex flex-shrink-0 ml-4">
                                    <button type="button"
                                        class="inline-flex text-gray-400 bg-white rounded-md hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
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
            @error('media.*') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div x-data="{
            isDropping: false,
            isUploading: false,
            progress: 0,
            init() {
                console.log('file upload');
            },
            handleFileSelect(event) {
                console.log('handleFileSelect', event.target.files);
                if (event.target.files.length) {
                    Array.from(event.target.files).forEach(file => {
                        @this.upload('media', file, (uploadedFilename) => {
                            console.log('File uploaded:', uploadedFilename);
                        }, () => {
                            console.error('Upload error');
                        }, (event) => {
                            this.progress = event.detail.progress;
                        });
                    });
                }
            },

            handleFileDrop(event) {
                console.log('handlefiledrop');
                if (event.dataTransfer.files.length > 0) {
                    this.uploadFiles(event.dataTransfer.files)
                }
            },
            uploadFiles(files) {
                console.log('upload files', files);
                const $this = this
                this.isUploading = true
                @this.uploadMultiple('media', files,
                function (success) { //upload was a success and was finished
                    $this.isUploading = false
                    $this.progress = 0
                },
                function (error) { //an error occured
                    console.log('error', error)
                },
                function (event) { //upload progress was made
                    $this.progress = event.detail.progress
                }
                )
            },
            dragover(event) {
                // check if is holding a file
                if (event.dataTransfer.types.includes('Files')) {
                    {{-- event.preventDefault() --}}
                } else {
                }
            },
            removeUpload(filename) {
                @this.removeUpload('files', filename)
            },
        }">

            <div class="mt-2">

                @if($button)
                <x-aura::button.light
                    wire:click="$emit('openModal', 'aura::media-manager', {{ json_encode(['field' => $field, 'slug' => $field['slug'], 'selected' => $selected]) }})">
                    <x-slot:icon>
                        <x-aura::icon icon="media" class="" />
                        </x-slot>

                        <span>Media Manager</span>
                </x-aura::button.light>
                @endif
            </div>

            <div class="" x-on:drop="isDropping = false" x-on:drop.prevent="handleFileDrop($event)"
                x-on:dragover.prevent="isDropping = true; console.log('dragover'); dragover($event);"
                x-on:dragleave.prevent="isDropping = false; console.log('dragleave');">

                <div class="flex items-center justify-center w-full mb-4" x-cloak>
                    <div class="absolute top-0 bottom-0 left-0 right-0 z-30 flex items-center justify-center bg-primary-400 opacity-90"
                        x-show="isDropping">
                        <span class="text-3xl text-white">Release file to upload!</span>
                    </div>


                </div>

                <div class="bg-white dark:bg-gray-900 h-[4px] w-full mt-0">
                    <div class="bg-primary-500 h-[4px]" style="transition: width 0.5s" :style="`width: ${progress}%;`"
                        x-show="isUploading">
                    </div>
                </div>

                <div>

                    @if($upload)

                      <div>
                            <label for="file-upload">
                                <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">Datei hierhin ziehen oder <span class="text-primary-400">hier klicken</span> um eine Datei hochzuladen.</p>

                                {{-- <input type="file" id="file-upload" multiple @change="handleFileSelect" class="hidden"
                                    wire:model.defer="media" /> --}}

                                    <input type="file" id="file-upload" multiple @change="handleFileSelect" class="hidden" />

                            </label>
                        </div>
                        
                    @endif

                    @if($table)

                    <div class="flex flex-col">
                        <div class="flex items-center justify-between mt-6">
                        <h1 class="text-3xl font-semibold">
                            {{ __('Attachments') }}
                        </h1>


                        <div>
                            <label for="file-upload">
                                <p class="mb-2 text-sm text-gray-500 dark:text-gray-400"><span
                                        class="font-semibold">Click to
                                        upload</span> or drag and drop</p>

                                <input type="file" id="file-upload" multiple @change="handleFileSelect" class="hidden"
                                    wire:model.defer="media" />
                            </label>
                        </div>
                    </div>

                        <livewire:aura::table :model="$post" :field="$field" />
                    </div>


                    @endif
                </div>

            </div>

        </div>



</div>
