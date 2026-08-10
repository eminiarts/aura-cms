<x-aura::fields.wrapper :field="$field">
    <div class="truncate">
        @php
            $value = $this->model->displayInContext($field['slug'], \Aura\Base\Contracts\FieldValueContext::View);
        @endphp
        @if($value === null || $value === '')
            <span class="text-gray-400">–</span>
        @else
            {!! $value !!}
        @endif
    </div>
</x-aura::fields.wrapper>
