{{-- The pager under the list. A list with one page has no footer and no 16px gap, so the next related
     section on a record page follows at the section gap alone. --}}
<div class="w-full {{ method_exists($rows, 'hasPages') && ! $rows->hasPages() ? '' : 'mt-4' }}">
    @if (method_exists($rows, 'links'))
        {{ $rows->links('aura::components.table.pagination') }}
    @endif
</div>
