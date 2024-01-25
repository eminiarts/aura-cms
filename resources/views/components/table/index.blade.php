<div class="mx-auto max-w-8xl" >

    @if($this->settings['selectable'])
    <div wire:key="table-bulk-select">
        @include('aura::components.table.bulk-select-row')
    </div>
    @endif

    @if($this->settings['header_before'])
    {{ app('aura')::injectView('table_before') }}
    @endif

    @include($this->model->tableView())

    @if($this->settings['header_after'])
    {{ app('aura')::injectView('table_after') }}
    @endif

</div>
