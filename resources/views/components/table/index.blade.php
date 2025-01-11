<div class="mx-auto max-w-8xl" >
    @if($this->settings['table_before'])
        {{ app('aura')::injectView('table_before') }}
        {{ app('aura')::injectView('table_before_' . $this->model->getType()) }}
    @endif

    @include($this->settings['views']['filter_tabs'])

    @include($this->settings['views']['list'])

    @if($this->settings['table_after'])
        {{ app('aura')::injectView('table_after') }}
        {{ app('aura')::injectView('table_after_' . $this->model->getType()) }}
    @endif
</div>
