<x-aura::fields.wrapper :field="$field">
    <div class="w-full overflow-hidden bg-white border border-gray-500/30 rounded-lg shadow-xs appearance-none focus:border-primary-300 focus:outline-none ring-gray-900/10 focus:ring focus:ring-primary-300  focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:bg-gray-900">
    <div class="relative h-96 ">
        <div x-data
            x-ref="aceEditor"
            id="editor"
            wire:ignore
            x-init="
            ace.config.set('basePath', '{{ asset('js/ace/') }}');
                editor = ace.edit($refs.aceEditor);
                editor.setTheme('ace/theme/github');
                editor.setOptions({
                    fontSize: '14px',
                    showPrintMargin: false,
                    showLineNumbers: true,
                    wrap: true,
                    behavioursEnabled: false,
                    enableEmmet: true,
                    enableBasicAutocompletion: true,
                    enableLiveAutocompletion: true,
                    enableSnippets: true,
                });
                editor.session.setMode('ace/mode/{{ $field['language'] ?? 'html' }}');
                editor.on('change', function () {
                    $dispatch('input', editor.getValue());
                });
            "
            wire:model="post.fields.{{ optional($field)['slug'] }}"
        >
        
    
        @php
            $value = optional($this->post['fields'])[$field['slug']];
            if (is_array($value)) {
                $value = json_encode($value);
            }
            echo $value;
        @endphp

        
    </div>
    </div>
    </div>
</x-aura::fields.wrapper>


@push('styles')
    @once
        <style type="text/css" media="screen">
    #editor {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
    }
</style>
    @endonce
@endpush

    @once

@push('scripts')
        <!-- import the ace ext-emmet.js -->
        <script src="/js/ace/ace.min.js" integrity="sha512-s57ywpCtz+4PU992Bg1rDtr6+1z38gO2mS92agz2nqQcuMQ6IvgLWoQ2SFpImvg1rbgqBKeSEq0d9bo9NtBY0w==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="/js/ace/ext-emmet.min.js" integrity="sha512-xbrBbnLPHPCwK4PZpXL4GN9UHCHAvJGroy3WyfltNhPKqyqw/EFgBrLhMkTIsGuqfBsIQY/VdnxfNe/SFQzJyQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="/js/ace/ext-language_tools.min.js" integrity="sha512-o/VD0e6Ld6RjhcgZJWVv/1MfV03mjhk3zWBA41/6iYShAb/3ruD8wlSU+HyqBYlLr+IAwdBKx4Kl4w08ROJuTw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

@endpush
    @endonce
