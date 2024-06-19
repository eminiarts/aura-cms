<x-aura::fields.wrapper :field="$field">
    <div
        class="overflow-hidden w-full bg-white rounded-lg border appearance-none border-gray-500/30 shadow-xs focus:border-primary-300 focus:outline-none ring-gray-900/10 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:bg-gray-900">

        @php
            $content = json_encode($form['fields'][$field['slug']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            // dump($content);

        @endphp

        <div>
            <div id="" x-data="{
                editor: null,
            
                init() {
            
                    this.editor = monaco.editor.create($refs.editor, {
                        value: @js($content),
                        language: 'json',
                        automaticLayout: true,
                        minimap: {
                            enabled: false
                        },
                        padding: {
                            top: 16,
                            bottom: 16,
                        },
                        scrollBeyondLastLine: false, // Disable scrolling beyond the last line
                        revealHorizontalRightPadding: 0,
            
                        fontSize: 14, // Set the font size
                        // fontFamily: 'Consolas, Courier New, monospace', // Set the font family
                        lineHeight: 24, // Set the line height
                        lineNumbers: '{{ optional($field)['line_numbers'] ? 'on' : 'off' }}', // Enable line numbers
                        renderWhitespace: 'off', // Render whitespace characters
                        wordWrap: 'on', // Enable word wrapping
                        folding: true, // Enable code folding
            
                        matchBrackets: 'always', // Highlight matching brackets
            
                        autoIndent: 'full', // Enable auto indentation
                        smoothScrolling: true, // Enable smooth scrolling
                        scrollbar: {
                            vertical: 'visible', // Always show vertical scrollbar
                            horizontal: 'visible', // Always show horizontal scrollbar
                        },
                        suggestOnTriggerCharacters: true, // Enable IntelliSense suggestions on trigger characters
                        quickSuggestions: {
                            other: true,
                            comments: true,
                            strings: true
                        },
                        renderIndentGuides: false, // Disable rendering of indent guides
                        guides: {
                            indentation: false // Disable indentation guides
                        }
            
                    });
            
                    // Listen for changes in the editor and update Livewire model
                    this.editor.onDidChangeModelContent(() => {
                        //content = this.editor.getValue();
                        console.log('content changed ');
            
                    });
                },
            
            }" x-ref="editor" wire:ignore class="w-full h-full editor"></div>
        </div>

    </div>
    @assets
        @once
            @push('styles')
                <style type="text/css" media="screen">
                    .editor {
                        /* position: absolute;
                                            top: 0;
                                            right: 0;
                                            bottom: 0;
                                            left: 0; */
                        min-height: 300px;
                    }
                </style>
                <script>
                    console.log('json here once?');
                </script>
                @vite(['resources/js/monaco.js'], 'vendor/aura/libs')
            @endpush
        @endonce
    @endassets

</x-aura::fields.wrapper>
