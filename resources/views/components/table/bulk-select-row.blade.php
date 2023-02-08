@php
$paginationEnabled = true;
$bulkActionsEnabled = true;

$rows = $this->rows;

@endphp


@if (
    $bulkActionsEnabled &&
    count($this->bulkActions) &&
    (
        (
            $paginationEnabled && (
                ($selectPage && $rows->total() > $rows->count()) ||
                count($selected)
            )
        ) ||
        count($selected)
    )
)
<div class="p-2 bg-indigo-50 dark:bg-gray-900 dark:text-white">
  @if ((!$paginationEnabled && $selectPage) || (count($selected) && $paginationEnabled && !$selectAll && !$selectPage))
    <div>
      <span>
        @lang('You have selected')
        <strong>{{ count($selected) }}</strong>
        @lang(count($selected) === 1 ? 'row' : 'rows').
      </span>

      <button
        wire:click="resetBulk"
        wire:loading.attr="disabled"
        type="button"
        class="ml-1 text-sm font-medium leading-5 text-gray-700 text-blue-600 underline transition duration-150 ease-in-out focus:outline-none focus:text-gray-800 focus:underline dark:text-white dark:hover:text-gray-400"
        >
        @lang('Unselect All')
      </button>
    </div>
  @elseif ($selectAll)
    <div>
      <span>
        @lang('You are currently selecting all')
        <strong>{{ $rows->total() }}</strong>
        @lang('rows').
      </span>

      <button
        wire:click="resetBulk"
        wire:loading.attr="disabled"
        type="button"
        class="ml-1 text-sm font-medium leading-5 text-gray-700 text-blue-600 underline transition duration-150 ease-in-out focus:outline-none focus:text-gray-800 focus:underline dark:text-white dark:hover:text-gray-400"
        >
        @lang('Unselect All')
      </button>
    </div>
  @else
    @if ($rows->total() === count($selected))
      <div>
        <span>
          @lang('You have selected')
          <strong>{{ count($selected) }}</strong>
          @lang(count($selected) === 1 ? 'row' : 'rows').
        </span>

        <button
          wire:click="resetBulk"
          wire:loading.attr="disabled"
          type="button"
          class="ml-1 text-sm font-medium leading-5 text-gray-700 text-blue-600 underline transition duration-150 ease-in-out focus:outline-none focus:text-gray-800 focus:underline dark:text-white dark:hover:text-gray-400"
          >
          @lang('Unselect All')
        </button>
      </div>
    @else
      <div>
        <span>
          @lang('You have selected')
          <strong>{{ count($selected) }}</strong>
          @lang('rows, do you want to select all')
          <strong>{{ $rows->total() }}</strong>?
        </span>

        <button
          wire:click="selectAll"
          wire:loading.attr="disabled"
          type="button"
          class="ml-1 text-sm font-medium leading-5 text-gray-700 text-blue-600 underline transition duration-150 ease-in-out focus:outline-none focus:text-gray-800 focus:underline dark:text-white dark:hover:text-gray-400"
          >
          @lang('Select All')
        </button>

        <button
          wire:click="resetBulk"
          wire:loading.attr="disabled"
          type="button"
          class="ml-1 text-sm font-medium leading-5 text-gray-700 text-blue-600 underline transition duration-150 ease-in-out focus:outline-none focus:text-gray-800 focus:underline dark:text-white dark:hover:text-gray-400"
          >
          @lang('Unselect All')
        </button>
      </div>
    @endif
  @endif
</div>
@endif
