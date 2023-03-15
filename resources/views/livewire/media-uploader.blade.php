<div>
    <div>
        @if($media && count($media))

        {{-- @dump($media, json_encode($media)) --}}
    <div
    x-data="{ media: {{ json_encode($media) }}, loading: true }"
    class="pointer-events-none fixed inset-0 flex items-end px-4 py-6 sm:items-start sm:p-6 z-50"
>
  <div class="flex w-full flex-col items-end space-y-4 sm:items-end">

    @foreach($media as $file)
    <!--
      Notification panel, dynamically insert this into the live region when it needs to be displayed

      Entering: "transform ease-out duration-300 transition"
        From: "translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        To: "translate-y-0 opacity-100 sm:translate-x-0"
      Leaving: "transition ease-in duration-100"
        From: "opacity-100"
        To: "opacity-0"
    -->
    <div class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 relative" x-data="{loading: true}" x-show="loading"
            x-init="setTimeout(() => { loading = false }, 3000)" x-transition:leave="transition ease-linear duration-1000" x-transition:leave-end="opacity-0">
      <div class="p-4">
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="ml-3 w-0 flex-1 pt-0.5">
            <p class="text-sm font-medium text-gray-900">Successfully uploaded</p>
            <p class="mt-1 text-sm text-gray-500">{{ $file->getClientOriginalName() }}</p>
          </div>
          <div class="ml-4 flex flex-shrink-0">
            <button type="button" class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
              <span class="sr-only">Close</span>
              <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <div class="absolute bottom-0 left-0 w-full h-1">
                <div
                    class="h-1 bg-primary-200 animate-countdown origin-left"
                ></div> 
            </div>
    </div>

    @endforeach
  </div>

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
