<div class="">
    <div x-show="open" class="my-10">
            @error('photos') <span class="error">{{ $message }}</span> @enderror

        <div wire:loading wire:target="photo">Uploading...</div>
        <div wire:loading wire:target="save">Saving...</div>

            @if (session()->has('message'))
        <div class="p-2 text-green-400 my-10 rounded">
                {{ session('message') }}
        </div>
            @endif

        <div x-data="{ isUploading: false, progress: 0 }" x-on:livewire-upload-start="isUploading = true"
                x-on:livewire-upload-finish="isUploading = false" x-on:livewire-upload-error="isUploading = false"
                x-on:livewire-upload-progress="progress = $event.detail.progress" class="relative">
            <div class="flex items-center justify-center h-40 bg-white rounded-sm text-center text-gray-500 cursor-pointer border border-dashed border-gray-900">
                <div class="w-full flex items-center justify-center" style=""x-on:click="$refs.fileInput.click()"> Drag and drop to upload <br> or <br> Select Files </div>

                    <input x-ref="fileInput" type="file" multiple wire:model="photos" class="hidden" />

                    <div class="">
                        <button x-on:click="open = !open" type="button" class="absolute top-0 right-0 p-2 rounded-sm text-gray-400 hover:text-gray-500 focus:outline-none">
                        <span class="sr-only">Close</span>
                        <!-- Heroicon name: outline/x -->
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        </button>
                    </div>
                </div>

                <!-- PROGRESS BAR -->
                <div x-show="isUploading">
                    <progress max="100" x-bind:value="progress"></progress>
                </div>
            </div>
        </div>


        <div class="pt-1" >
            <ul role="list" class="relative flex">
                    @if ($photos)
                    @foreach($photos as $photo)
                <div class="my-3 rounded-sm transition-all duration-500" style="" wire:key="{{$loop->index}}">
            {{-- GRID VIEW --}}
                    <div x-show="selected === 'grid'">
                        <div class="flex flex-col justify-center h-40 pt-2 pb-2 border">
                            <img src="{{ $photo->temporaryUrl() }}" class="w-48">
                                    {{-- REMOVE BUTTON --}}
                            <button wire:click="remove({{$loop->index}})" type="button" class="flex items-top text-gray-400 hover:text-gray-500 focus:outline-none">
                            <span class="sr-only">Close</span>
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            </button>
                        </div>
                            <p class="mt-2 block text-sm font-medium text-gray-900 truncate pointer-events-none">IMG_4985.HEIC</p>
                            <p class="block text-sm font-medium text-gray-500 pointer-events-none">3.9 MB</p>
                    </div>
            {{-- END GRID VIEW --}}

            {{-- LIST VIEW --}}
                    <div x-show="selected === 'list'">
                        <div class="flex h-20 mt-2 mb-2 pt-2 pb-2 items-top justify-center border">
                            <div class="flex">
                                <img src="{{ $photo->temporaryUrl() }}" class="w-20">
                                <p class="w-64 ml-4 mr-4 text-sm font-medium text-gray-900 truncate pointer-events-none">Info</p>
                                <p class="w-48 ml-4 mr-4 text-sm font-medium text-gray-900 truncate pointer-events-none">Author</p>
                                <p class="w-48 text-sm font-medium text-gray-900 truncate pointer-events-none">Uploaded to</p>
                                <p class="w-48 text-sm font-medium text-gray-900 truncate pointer-events-none">Comment</p>
                                <p class="w-48 text-sm font-medium text-gray-900 truncate pointer-events-none">Date</p>
                                <p class="w-48 text-sm font-medium text-gray-900 truncate pointer-events-none">IMG_4985.HEIC</p>
                                <p class="w-48 text-sm font-medium text-gray-500 pointer-events-none">3.9 MB</p>

                                <button wire:click="remove({{$loop->index}})" type="button" class="flex items-top rounded-md text-gray-400 hover:text-gray-500 focus:outline-none">
                                <span class="sr-only">Close</span>
                                <svg class="h-6 w-6 items-top justify-end" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                </button>
                            </div>
                        </div>
            {{-- END LIST VIEW --}}
                    </div>
                    @endforeach
                    <div class="absolute w-full pb-8 pt-8">
                        <button wire:loading.remove wire:click.prevent="save" class="w-full pt-1 pb-2 pr-2 pl-2 text-gray-500 rounded-sm bg-white border" style="">Save</button>
                        <button wire:loading wire:target="save" class="w-full p-2 border text-gray-500 rounded-sm bg-white" style=""
                                disabled type="button" class="py-2.5 px-5 mr-2 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-400/30 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-4 focus:outline-none focus:ring-primary-700 focus:text-primary-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 inline-flex items-center">
                                <svg role="status" class="inline w-4 h-4 mr-2 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                </svg>
                                Saving...
                        </button>
                    </div>
                </div>
                    @endif
            </ul>
        </div>
    </div>
</div>
