<x-aura::fields.wrapper :field="$field">
    <div
        x-data="{
            init() {
                let quill = new Quill(this.$refs.quill, { theme: 'snow' })

                quill.on('text-change', function () {
                    console.log(quill.root.innerHTML);
                    $dispatch('input', quill.root.innerHTML);
                });
            },
        }"
       x-ref="quill"
       wire:ignore
       wire:model="post.fields.{{ optional($field)['slug'] }}"
    >
        {!! $this->post['fields'][$field['slug']] !!}
    </div>
</x-aura::fields.wrapper>


{{-- border-gray-500/30 focus:border-primary-300 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 rounded-md shadow-sm --}}


@push('styles')
    @once
        <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    @endonce
@endpush

@push('scripts')
    @once
        <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    @endonce
@endpush
