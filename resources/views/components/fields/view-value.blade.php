<x-aura::fields.wrapper :field="$field">
    <div class="break-words">
        @php
            $value = $this->model->display($field['slug']);
        @endphp
        @if(empty($value))
            <span class="text-gray-400">–</span>
        @else
            {!! $value !!}
        @endif
    </div>
</x-aura::fields.wrapper>
