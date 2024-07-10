<x-aura::fields.wrapper :field="$field">
    <div x-data="{
        init() {
            let quill = new window.Quill(this.$refs.quill, { theme: 'snow' })
    
            quill.on('text-change', function() {
                $dispatch('input', quill.root.innerHTML);
            });
        },
    }" x-ref="quill" wire:ignore wire:model="form.fields.{{ optional($field)['slug'] }}">
        {!! $this->form['fields'][$field['slug']] ?? '' !!}
    </div>








</x-aura::fields.wrapper>

@once
    @push('scripts')
        @vite(['resources/js/quill.js'], 'vendor/aura/libs')
    @endpush
@endonce

{{-- this is for compilation (don't delete)
     border-gray-500/30 focus:border-primary-300 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 rounded-md shadow-sm --}}
