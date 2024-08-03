<x-aura::fields.wrapper :field="$field">
    @if(isset($field['view_view']))
      <div class="flex flex-col space-y-3">
        @php
            $fieldItemIds = $this->model->fields[$field['slug']] ?? [];
            $fieldItems = [];

            if ($field['resource']) {
                $resourceClass = $field['resource'];
                $fieldItems = $resourceClass::whereIn('id', $fieldItemIds)->get();
            }
        @endphp
        @foreach($fieldItems as $item)
            @include($field['view_view'], ['item' => $item])
        @endforeach
      </div>

    @else
        <div class="truncate">
            {!! $this->model->display($field['slug']) !!}
        </div>
    @endif
</x-aura::fields.wrapper>
