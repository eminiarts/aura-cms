<x-aura::fields.wrapper :field="$field">
    <?php $uniqueId = uniqid($field['slug'] . '_'); ?>
    <div class="aura-field overflow-hidden w-full appearance-none">
        <div>
            <div
                x-data="{
                    editor: null,
                    editorId: '{{ $uniqueId }}',
                    fieldSlug: '{{ $field['slug'] }}'
                }"
                x-ref="editor_{{ $uniqueId }}"
                x-init="
                    console.log('Initializing editor for: ' + editorId);
                    const editor = monaco.editor.create($refs['editor_' + editorId], {
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
                        scrollBeyondLastLine: false,
                        revealHorizontalRightPadding: 0,
                        fontSize: 14,
                        lineHeight: 24,
                        lineNumbers: '{{ optional($field)['line_numbers'] ? 'on' : 'off' }}',
                        renderWhitespace: 'off',
                        wordWrap: 'on',
                        folding: true,
                        matchBrackets: 'always',
                        autoIndent: 'full',
                        smoothScrolling: true,
                        scrollbar: {
                            vertical: 'visible',
                            horizontal: 'visible',
                        },
                        suggestOnTriggerCharacters: true,
                        quickSuggestions: {
                            other: true,
                            comments: true,
                            strings: true
                        },
                        renderIndentGuides: false,
                        guides: {
                            indentation: false
                        }
                    });

                    monaco.editor.setTheme('github-light');
                    if (document.documentElement.classList.contains('dark')) {
                        monaco.editor.setTheme('github-dark');
                    }

                    // Listen for changes in the editor and update Livewire model
                    editor.onDidChangeModelContent(() => {
                        const content = editor.getValue();
                        console.log('content for ' + editorId + ': ', content);
                        $wire.$set('form.fields.' + fieldSlug, content);
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
