<x-aura::fields.wrapper :field="$field">
    <div class="overflow-hidden w-full bg-white rounded-lg border appearance-none border-gray-500/30 shadow-xs focus:border-primary-300 focus:outline-none ring-gray-900/10 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:bg-gray-900">
        <div>
            <div
                x-data="{
                    editor: null,
                    editorId: '{{ $field['slug'] }}'
                }"
                x-ref="editor_{{ $field['slug'] }}"
                x-init="
                    console.log('Initializing editor for: ' + editorId);
                    this.editor = monaco.editor.create($refs['editor_' + editorId], {
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
                        content = this.editor.getValue();
                        console.log(this.editor);
                        console.log('content for ' + editorId + ': ', content);
                        $wire.$set('form.fields.' + editorId, content);
                    });
                "
                wire:ignore
                class="w-full h-full"
                style="min-height: {{ optional($field)['min_height'] ? optional($field)['min_height'] . 'px' : '300px' }};"
            ></div>
        </div>
    </div>

    @assets
        @vite(['resources/js/monaco.js'], 'vendor/aura/libs')
    @endassets
</x-aura::fields.wrapper>
