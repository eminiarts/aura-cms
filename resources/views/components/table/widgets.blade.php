{{-- Resource index page: the widget strip sits under the page header and above the toolbar.
     The widgets_before/widgets_after hooks travel with it. --}}
{{ app('aura')::injectView('widgets_before') }}

@if ($widgets = $model->widgets())
    @livewire('aura::widgets', ['widgets' => $widgets, 'model' => $model, 'compact' => true], key('table-widgets-'.$model->getType()))
@endif

{{ app('aura')::injectView('widgets_after') }}
