<div>
    <div>
        @if($media && count($media))
    <ul class="mt-5 list-none space-y-2">
        @foreach($media as $file)
        <li class="flex items-center space-x-2">
            <div id="toast-success" class="flex items-center w-full max-w-xs p-4 mb-4 text-gray-500 bg-white rounded-lg shadow dark:text-gray-400 dark:bg-gray-800" role="alert">
    <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-green-500 bg-green-100 rounded-lg dark:bg-green-800 dark:text-green-200">
        <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
        <span class="sr-only">Check icon</span>
    </div>
    <div class="ml-3 text-sm font-normal">{{$file->getClientOriginalName()}} uploaded</div>
    <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex h-8 w-8 dark:text-gray-500 dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700" data-dismiss-target="#toast-success" aria-label="Close">
        <span class="sr-only">Close</span>
        <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
    </button>
</div>
        </li>
        @endforeach
    </ul>
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
