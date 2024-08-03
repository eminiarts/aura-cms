# Table

## Customize Query

## Filters

## Customize Row View

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

## Customize Table View

Sometimes you want to modify the table view. You can do this by adding a `tableView()` method to your resource:

Default Table view:
```php
    public function tableView()
    {
        return 'aura::components.table.table';
    }
```

Custom Table view:

```php
    public function tableView()
    {
        return 'admin.resource.table';
    }
```

Create the view `/resources/views/admin/resource/table.blade.php`. You will have access to `$this->rows` of the Livewire component to access the rows. 

This is an example of the default table view with ul/li:

```php
<div class="mt-2">
    <div class="min-w-full overflow-hidden overflow-x-auto align-middle border border-gray-400/30 sm:rounded-lg dark:border-gray-700 px-4">
        <ul role="list" class="divide-y divide-gray-100">
            @forelse($this->rows as $row)
            @include($row->rowView())
            @empty

            <li>
                <div class="py-8 text-center bg-white dark:bg-gray-900">
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No entries available</h3>
                </div>
            </li>

            @endforelse
        </ul>
    </div>

    @include('aura::components.table.footer')
</div>
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

## Customize Top Bar

You can customize the top bar by adding a `tableTopBarView()` method to your resource:

```php
    public function tableTopBarView()
    {
        return 'admin.resource.top-bar';
    }
```


## Customize Table Settings on Resource

```php
// In your Resource
 public function indexTableSettings()
    {
        return [
            'default_view' => 'grid',
            'views' => [
                'grid' => 'custom.table.grid',
            ]
        ];
    }
```

All available Settings:

```php
public function indexTableSettings()
    {
        return [
            'per_page' => 10,
            'columns' => $this->getTableHeaders(),
            'filters' => [],
            'search' => '',
            'sort' => [
                'column' => 'id',
                'direction' => 'desc',
            ],
            'settings' => true,
            'sort_columns' => true,
            'columns_global_key' => false,
            'columns_user_key' => 'columns.'.$this->getType(),
            'search' => true,
            'filters' => true,
            'global_filters' => true,
            'title' => true,
            'selectable' => true,
            'default_view' => $this->defaultTableView(),
            // 'current_view' => $this->defaultTableView(),
            'header_before' => true,
            'header_after' => true,
            'table_before' => true,
            'table_after' => true,
            'create' => true,
            'actions' => true,
            'bulk_actions' => true,
            'header' => true,
            'edit_in_modal' => false, // true, false, 'sidebar', 'modal'
            'create_in_modal' => false, // true, false, 'sidebar', 'modal'
            'views' => [
                'table' => 'aura::components.table.index',
                'list' => $this->tableView(),
                'grid' => $this->tableGridView(),
                'filter' => 'aura::components.table.filter',
                'header' => 'aura::components.table.header',
                'row' => $this->rowView(),
                'bulkActions' => 'aura::components.table.bulkActions',
                'table-header' => 'aura::components.table.table-header',
                'table_footer' => 'aura::components.table.footer',
            ],
        ];
    }
```