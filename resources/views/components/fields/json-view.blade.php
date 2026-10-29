<x-aura::fields.wrapper :field="$field">
    @php
        $value = $this->model->display($field['slug']);
    @endphp

    @if($value === null || $value === '')
        <span class="text-gray-400">–</span>
    @else
        <pre style="max-height: 36rem; white-space: pre-wrap;" class="overflow-auto p-4 font-mono text-sm leading-6 text-gray-700 break-words bg-gray-50 rounded-lg border border-gray-500/30 dark:bg-gray-900 dark:text-gray-200">{!! \Aura\Base\Fields\Json::highlight($value) !!}</pre>
    @endif
</x-aura::fields.wrapper>
