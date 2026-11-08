<x-aura::fields.wrapper :field="$field">
    <div class="truncate text-sm text-gray-900 dark:text-gray-100">
        @php
            $value = $this->model->display($field['slug']);
        @endphp
        @if(empty($value))
            <span class="text-gray-400 dark:text-gray-500">–</span>
        @else
            {!! $value !!}
        @endif
    </div>
</x-aura::fields.wrapper>
