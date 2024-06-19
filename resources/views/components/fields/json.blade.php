<x-aura::fields.wrapper :field="$field">
    <div class="overflow-hidden w-full bg-white rounded-lg border appearance-none border-gray-500/30 shadow-xs focus:border-primary-300 focus:outline-none ring-gray-900/10 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:bg-gray-900">


    <div x-data="{ content: '' }">
    <div
        id="editor"
        x-init="
            let editor = monaco.editor.create($el, {
                value: content,
                language: 'javascript',
                automaticLayout: true,
                {{-- lineNumbers: 'off', --}}
                {{-- minimap: {
                    enabled: false
                }, --}}
                padding: {
                    top: 16
                }
            });
        "
        wire:ignore
        class="w-full h-full"
    ></div>
</div>

    </div>
    @push('styles')
        @once
            <style type="text/css" media="screen">
                #editor {
                    /* position: absolute;
                    top: 0;
                    right: 0;
                    bottom: 0;
                    left: 0; */
                    min-height: 300px;
                }
            </style>
        @endonce
    @endpush

    @once
        @assets
            @vite(['resources/js/monaco.js'], 'vendor/aura/libs')
        @endassets
    @endonce

</x-aura::fields.wrapper>
