@props([
'header' => null,
'footer' => null,
'slug'
])

<div class="mx-auto max-w-8xl" >

{{-- @dump($selected)
@dump($selectAll) --}}

<div wire:key="table-bulk-select">
    @include('aura::components.table.bulk-select-row')
</div>

@include($this->model->tableView())

</div>
