# Table

## Customize Query

## Filters

## List Layout

You can customize the Row view

```php
    public function tableRowView()
    {
        return 'attachment.row';
    }
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
