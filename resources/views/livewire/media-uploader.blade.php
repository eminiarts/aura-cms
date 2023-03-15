<div>
    <div>
        @if($media && count($media))
    <div
    x-data="{ media: {{ json_encode($media) }}, loading: true }"
    class="fixed top-0 right-0 p-4 space-y-4 z-50"
>
    <template x-for="(file, index) in media" :key="index">
        <div
            x-show="loading"
            x-init="setTimeout(() => { loading = false }, 5000); console.log(file);"
            class="flex items-center w-full max-w-xs p-4 mb-4 text-gray-500 bg-white rounded-lg shadow dark:text-gray-400 dark:bg-gray-800"
            role="alert"
        >
            <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-green-500 bg-green-100 rounded-lg dark:bg-green-800 dark:text-green-200">
                <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
            </div>
            <div class="ml-3 text-sm font-normal">
                @verbatim
                    {{ file.original_name }} uploaded
                @endverbatim
            </div>
            <div class="w-full h-1.5 mt-2">
                <div
                    x-show="loading"
                    x-transition:leave="transition ease-linear duration-1000"
                    x-transition:leave-start="w-full bg-primary-500"
                    x-transition:leave-end="w-0 bg-primary-500"
                    class="h-1.5 bg-primary-500"
                ></div>
            </div>
        </div>
    </template>
</div>

    @endif

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
                console.log('handleFileSelect');
                if (event.target.files.length) {
                    this.uploadFiles(event.target.files)
                }
            },
            handleFileDrop(event) {
                console.log('handlefiledrop');
                if (event.dataTransfer.files.length > 0) {
                    this.uploadFiles(event.dataTransfer.files)
                }
            },
            uploadFiles(files) {
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
                console.log('dragover', event);
                // check if is holding a file
                if (event.dataTransfer.types.includes('Files')) {
                    {{-- event.preventDefault() --}}
                    console.log('is holding a file');
                } else {
                    console.log('is not holding a file');
                }
            },
            removeUpload(filename) {
                @this.removeUpload('files', filename)
            },
        }">

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

                <div class="flex items-center justify-between mt-6">
                    <div>
                        <h1 class="text-3xl font-semibold">Attachments</h1>


                        </h3>
                    </div>

                    <div>
                        <div>

                            <div>
                                <label for="file-upload">
                                    <p class="mb-2 text-sm text-gray-500 dark:text-gray-400"><span
                                            class="font-semibold">Click to
                                            upload</span> or drag and drop</p>

                                    <input type="file" id="file-upload" multiple @change="handleFileSelect"
                                        class="hidden" wire:model.defer="media" />
                                </label>
                            </div>


                        </div>
                    </div>
                </div>


                <livewire:aura::table :model="$post" />

            </div>

        </div>
    </div>



</div>
