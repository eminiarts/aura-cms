<x-aura::fields.wrapper :field="$field">
    <div
    wire:ignore
        x-data="{
            value: $wire.entangle('post.fields.{{ $field['slug'] }}').defer,
            init() {
                let quill{{ $field['slug'] }} = new Quill(this.$refs['quill-{{ $field['slug'] }}'], { theme: 'snow' })

                vm = this;

                quill{{ $field['slug'] }}.on('text-change', function () {
                    vm.value = quill{{ $field['slug'] }}.root.innerHTML;
                });
            },
        }"
      x-ref="quill-{{ $field['slug'] }}"
    >
            {!! $this->post['fields'][$field['slug']] !!}
    </div>
</x-aura::fields.wrapper>


{{-- border-gray-500/30 focus:border-primary-300 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 rounded-md shadow-sm --}}


@push('styles')
    @once
        <link href="/js/quill/quill.css" rel="stylesheet">
    @endonce
@endpush

@push('scripts')
    @once
        <script src="/js/quill/quill.min.js"></script>
    @endonce
@endpush
