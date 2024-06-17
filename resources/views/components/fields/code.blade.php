<x-aura::fields.wrapper :field="$field">
    <div class="overflow-hidden w-full bg-white rounded-lg border appearance-none border-gray-500/30 shadow-xs focus:border-primary-300 focus:outline-none ring-gray-900/10 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:bg-gray-900">

    <div>
        {{-- @dump($field)
        @dump(optional($field)['theme'])
        @dump(optional($field)['line_numbers']) --}}
        {{-- @dump(optional($field)['min_height']) --}}
    <div
        x-data="{
            editor: null
        }"
        id="editor"
        x-ref="editor"
        x-init="
            console.log('hier');
                this.editor = monaco.editor.create($refs.editor, {
                    value: @js($form['fields'][$field['slug']]),
                    language: '{{ $field['language'] ?? 'html' }}',
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

                monaco.editor.setTheme('github-light');
                if (document.documentElement.classList.contains('dark')) {
                    monaco.editor.setTheme('github-dark');
                }

                // Listen for changes in the editor and update Livewire model
                this.editor.onDidChangeModelContent(() => {
                    content = editor.getValue();
                    console.log('content: ', content);
                    @this.set('form.fields.{{ $field['slug'] }}', content);
                });
        "
        wire:ignore
        class="w-full h-full"
        style="min-height: {{ optional($field)['min_height'] ? optional($field)['min_height'] . 'px' : '300px' }};"
    ></div>
</div>

    </div>

    @assets
        @once
            <script>console.log('Assets here once?');</script>
            @vite(['resources/js/monaco.js'], 'vendor/aura/libs')
        @endonce
    @endassets

</x-aura::fields.wrapper>
