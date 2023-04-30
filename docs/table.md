# Table

## Customize Query

## Filters

## List Layout

You can customize the Row view

```php
    // If you want to use /resources/views/attachment/row.blade.php
    public function tableRowView()
    {
        return 'attachment.row';
    }
```

This is the default row view:

```php
<tr class="bg-white dark:bg-gray-900" wire:key="{{ $row->id }}" data-id="{{ $row->id }}">
    <x-aura::table.cell class="pr-0">
        <x-aura::input.checkbox x-model="selected" :value="$row->id" x-on:click="toggleRow($event, {{ $row->id }})" />
    </x-aura::table.cell>

    @foreach($this->headers as $key => $column)
        @if(optional($this->columns)[$key])
            <td class="px-6 py-4">
                {!! $row->display($key) !!}
            </td>
        @endif
    @endforeach

    <td>
        @include('aura::components.table.row-action')
    </td>
</tr>

```

## Grid Layout
If you want to add a grid Layout, add `tableGridView()`to your resource:
```php
class Attachment extends Post
{
    public function tableGridView()
    {
        return 'attachment.grid';
    }
}
```

To change the default Table view:

```php
    public function defaultTableView()
    {
        return 'grid';
    }
```

Create the view `/resources/views/attachment/grid.blade.php`. You will have access to `$this->rows` of the Livewire component to access the rows. 

```php
<div>

    <ul role="list" class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 sm:gap-x-6 lg:grid-cols-4 xl:gap-x-8">

        @forelse($this->rows as $row)
            <li>
                <img src="/storage/{{ $row->url }}"
            </li>
        @empty
            <li>No Rows found</li>
        @endforelse

    </ul>

    {{ $this->rows->links() }}

</div>

```
