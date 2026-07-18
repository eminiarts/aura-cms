<div>
    <div
        x-data="{
            disabled: {{ $disabled ? 'true' : 'false' }},
            maxFiles: @js($uploadPolicy['max_files']),
            maxSize: @js($uploadPolicy['max_size_bytes']),
            blockedExtensions: @js($uploadPolicy['blocked_extensions']),
            queue: [],
            isDropping: false,
            activeUpload: false,
            nextId: 1,
            pending: [],

            triggerFileUpload() {
                if (this.disabled) return;
                this.$refs.fileInput && this.$refs.fileInput.click();
            },

            handleFileSelect(event) {
                if (this.disabled) return;
                this.enqueueFiles(Array.from(event.target.files || []));
                event.target.value = '';
            },

            handleFileDrop(event) {
                this.isDropping = false;
                if (this.disabled) return;
                this.enqueueFiles(Array.from(event.dataTransfer.files || []));
            },

            handleDragOver(event) {
                if (this.disabled) return;
                if (event.dataTransfer && Array.from(event.dataTransfer.types || []).includes('Files')) {
                    this.isDropping = true;
                }
            },

            enqueueFiles(files) {
                if (!files.length) return;

                if (files.length > this.maxFiles) {
                    alert(`{{ __('Maximum of :count files can be uploaded at once', ['count' => 20]) }}`);
                    return;
                }

                files.forEach((file) => {
                    const item = {
                        id: this.nextId++,
                        name: file.name,
                        size: this.humanSize(file.size),
                        progress: 0,
                        status: 'queued',
                        reason: '',
                    };

                    const rejection = this.precheck(file);
                    if (rejection) {
                        item.status = 'failed';
                        item.reason = rejection;
                        this.queue.push(item);
                        return;
                    }

                    this.queue.push(item);
                    this.pending.push({ item, file });
                });

                this.processNext();
            },

            precheck(file) {
                const ext = (file.name.split('.').pop() || '').toLowerCase();

                if (this.blockedExtensions.includes(ext)) {
                    return `{{ __('This file type is not allowed for security reasons.') }}`;
                }

                if (file.size > this.maxSize) {
                    return `{{ __('File exceeds the maximum size of 100MB.') }}`;
                }

                return null;
            },

            processNext() {
                if (this.activeUpload) return;

                const next = this.pending.shift();
                if (!next) return;

                this.activeUpload = true;
                const item = next.item;
                item.status = 'uploading';
                item.progress = 0;

                @this.uploadMultiple('media', [next.file],
                    () => {
                        const result = $wire.uploadResult || {};

                        if (result.successful) {
                            item.status = 'uploaded';
                            item.progress = 100;
                            this.scheduleDismiss(item);
                        } else {
                            item.status = 'failed';
                            item.reason = result.message || `{{ __('Upload failed. Please try again.') }}`;
                        }

                        this.activeUpload = false;
                        this.processNext();
                    },
                    (message) => {
                        item.status = 'failed';
                        item.reason = (typeof message === 'string' && message) ? message : `{{ __('Upload failed. Please try again.') }}`;
                        this.activeUpload = false;
                        this.processNext();
                    },
                    (event) => {
                        item.progress = event.detail.progress;
                    }
                );
            },

            scheduleDismiss(item) {
                setTimeout(() => {
                    this.queue = this.queue.filter((q) => q.id !== item.id);
                }, 4000);
            },

            dismiss(id) {
                this.queue = this.queue.filter((q) => q.id !== id);
            },

            hasFinished() {
                return this.queue.some((q) => q.status === 'uploaded' || q.status === 'failed');
            },

            clearFinished() {
                this.queue = this.queue.filter((q) => q.status === 'uploading' || q.status === 'queued');
            },

            humanSize(bytes) {
                if (!bytes) return '0 B';
                const units = ['B', 'KB', 'MB', 'GB', 'TB'];
                const i = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
                return (bytes / Math.pow(1024, i)).toFixed(i === 0 ? 0 : 1) + ' ' + units[i];
            },
        }"
        class="relative"
        x-on:drop.prevent="handleFileDrop($event)"
        x-on:dragover.prevent="handleDragOver($event)"
        x-on:dragleave.prevent="isDropping = false"
    >
        {{-- Dragover overlay --}}
        <div
            x-show="isDropping"
            x-cloak
            x-transition.opacity
            class="flex absolute inset-0 z-40 justify-center items-center rounded-lg border-2 border-primary-500 border-dashed backdrop-blur-sm bg-primary-500/10 dark:bg-primary-500/20"
        >
            <div class="flex flex-col items-center text-primary-700 dark:text-primary-200">
                <x-aura::icon.upload class="mb-2 w-8 h-8" />
                <span class="text-lg font-semibold">{{ __('Release file to upload!') }}</span>
            </div>
        </div>

        {{-- Upload queue panel --}}
        <div
            x-show="queue.length || {{ ($errors->has('media') || $errors->has('media.*')) ? 'true' : 'false' }}"
            x-cloak
            class="overflow-hidden mt-2 mb-4 bg-white rounded-lg border border-gray-200 shadow-xs dark:bg-gray-800 dark:border-gray-700"
        >
            <div class="flex justify-between items-center px-4 py-2.5 border-b border-gray-100 dark:border-gray-700/60">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                    {{ __('Uploads') }}
                </span>
                <button
                    type="button"
                    x-show="hasFinished()"
                    x-on:click="clearFinished()"
                    class="text-xs font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    {{ __('Clear finished') }}
                </button>
            </div>

            <ul class="divide-y divide-gray-100 dark:divide-gray-700/60">
                {{-- Server-side validation errors surfaced as failed rows --}}
                @foreach ($errors->get('media') as $message)
                    <li class="flex gap-3 items-start px-4 py-3">
                        <span class="flex flex-shrink-0 justify-center items-center mt-0.5 w-5 h-5 text-red-500">
                            <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate dark:text-gray-100">{{ __('Upload rejected') }}</p>
                            <p class="mt-0.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        </div>
                    </li>
                @endforeach
                @foreach ($errors->get('media.*') as $messages)
                    @foreach ($messages as $message)
                        <li class="flex gap-3 items-start px-4 py-3">
                            <span class="flex flex-shrink-0 justify-center items-center mt-0.5 w-5 h-5 text-red-500">
                                <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate dark:text-gray-100">{{ __('Upload rejected') }}</p>
                                <p class="mt-0.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            </div>
                        </li>
                    @endforeach
                @endforeach

                <template x-for="item in queue" :key="item.id">
                    <li class="flex gap-3 items-start px-4 py-3">
                        {{-- Status icon --}}
                        <span class="flex flex-shrink-0 justify-center items-center mt-0.5 w-5 h-5">
                            {{-- queued / uploading --}}
                            <svg
                                x-show="item.status === 'uploading' || item.status === 'queued'"
                                class="w-5 h-5 text-primary-500 animate-spin"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{-- uploaded --}}
                            <svg
                                x-show="item.status === 'uploaded'"
                                x-cloak
                                class="w-5 h-5 text-green-500"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                            </svg>
                            {{-- failed --}}
                            <svg
                                x-show="item.status === 'failed'"
                                x-cloak
                                class="w-5 h-5 text-red-500"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                            </svg>
                        </span>

                        {{-- File info + progress --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex gap-2 justify-between items-baseline">
                                <p class="text-sm font-medium text-gray-900 truncate dark:text-gray-100" x-text="item.name"></p>
                                <span class="flex-shrink-0 text-xs text-gray-400 dark:text-gray-500" x-text="item.size"></span>
                            </div>

                            {{-- Per-file progress bar --}}
                            <div
                                x-show="item.status === 'uploading' || item.status === 'queued'"
                                class="overflow-hidden mt-1.5 w-full h-1.5 bg-gray-100 rounded-full dark:bg-gray-700"
                            >
                                <div
                                    class="h-full rounded-full transition-all duration-300 ease-out bg-primary-500"
                                    :style="`width: ${item.progress}%`"
                                ></div>
                            </div>

                            <p
                                x-show="item.status === 'failed'"
                                x-cloak
                                class="mt-0.5 text-xs text-red-600 dark:text-red-400"
                                x-text="item.reason"
                            ></p>
                            <p
                                x-show="item.status === 'uploaded'"
                                x-cloak
                                class="mt-0.5 text-xs text-green-600 dark:text-green-400"
                            >{{ __('Uploaded') }}</p>
                        </div>

                        {{-- Dismiss --}}
                        <button
                            type="button"
                            x-on:click="dismiss(item.id)"
                            class="flex flex-shrink-0 justify-center items-center mt-0.5 w-5 h-5 text-gray-400 rounded hover:text-gray-600 dark:hover:text-gray-200"
                        >
                            <span class="sr-only">{{ __('Dismiss') }}</span>
                            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                            </svg>
                        </button>
                    </li>
                </template>
            </ul>
        </div>

        {{-- Single hidden file input. Only rendered where an upload trigger exists
             ($upload standalone button, or the $table view). The field-edit view
             ($button only) uploads through the Media Manager modal instead, so it
             must NOT render a second #file-upload. --}}
        @if (($upload || $table) && ! $disabled)
            <input
                type="file"
                id="file-upload"
                x-ref="fileInput"
                multiple
                class="hidden"
                x-on:change="handleFileSelect($event)"
            />
        @endif

        {{-- Media Manager button (field edit views) --}}
        @if ($button && ! $disabled)
            <div class="z-[2] relative mt-2">
                <x-aura::button.light
                    data-media-picker-button="{{ $field['slug'] }}"
                    wire:click="$dispatch('openModal', { component: 'aura::media-manager', arguments: { model: {{ json_encode($for) }}, slug: '{{ $field['slug'] }}', selected: {{ json_encode($selected) }} }})">
                    <x-slot:icon>
                        <x-aura::icon icon="media" class="" />
                    </x-slot>
                    <span>{{ __('Media Library') }}</span>
                </x-aura::button.light>
            </div>
        @elseif ($disabled)
            <div class="mt-2">
                <x-aura::button.light disabled>
                    <x-slot:icon>
                        <x-aura::icon icon="media" class="" />
                    </x-slot>
                    <span>{{ __('Media Library is disabled') }}</span>
                </x-aura::button.light>
            </div>
        @endif

        {{-- Standalone Upload button (non-table upload context) --}}
        @if ($upload && ! $table && ! $disabled)
            <div class="z-[2] relative flex justify-end mb-4">
                <x-aura::button.border
                    type="button"
                    class="flex items-center space-x-2"
                    x-on:click="triggerFileUpload()">
                    <x-aura::icon.upload class="w-5 h-5" />
                    <span>{{ __('Upload Files') }}</span>
                </x-aura::button.border>
            </div>
        @endif

        {{-- Attachment index / media manager modal --}}
        @if ($table && ! $disabled)
            <div class="z-[2] relative flex flex-col">
                <div class="flex flex-wrap gap-4 justify-between items-start">
                    <div class="mb-6">
                        <x-aura::breadcrumbs>
                            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-6 h-6 mr-0" />
                            <x-aura::breadcrumbs.li :title="__('Media Library')" />
                        </x-aura::breadcrumbs>
                    </div>

                    <div class="flex justify-end">
                        <x-aura::button.border
                            type="button"
                            class="flex items-center space-x-2"
                            x-on:click="triggerFileUpload()">
                            <x-aura::icon.upload class="w-5 h-5" />
                            <span>{{ __('Upload Files') }}</span>
                        </x-aura::button.border>
                    </div>
                </div>

                <div class="flex justify-between items-center mt-0">
                    <h1 class="text-2xl font-semibold">
                        {{ __('Media Library') }}
                    </h1>
                </div>

                <livewire:aura::table :model="$model" :field="$field" />
            </div>
        @endif
    </div>
</div>
