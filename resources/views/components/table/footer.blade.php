<div class="mt-4 w-full">
    @if (method_exists($rows, 'links'))
        {{ $rows->links('aura::components.table.pagination') }}
    @endif
</div>
