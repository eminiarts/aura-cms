<div class="mx-auto max-w-8xl" >

    @if($this->settings['selectable'])
    <div wire:key="table-bulk-select">
        @include('aura::components.table.bulk-select-row')
    </div>
    @endif

    @if($this->settings['table_before'])
        {{ app('aura')::injectView('table_before') }}
        {{ app('aura')::injectView('table_before_' . $this->model->getType()) }}
    @endif

    @include($this->settings['views']['list'])

    @if($this->settings['table_after'])
        {{ app('aura')::injectView('table_after') }}
        {{ app('aura')::injectView('table_after_' . $this->model->getType()) }}
    @endif

</div>
