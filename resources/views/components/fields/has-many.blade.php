@checkCondition($this->model, $field)
    <div class="w-full px-2 {{ $field['style']['class'] ?? '' }}">
       @dump('has many')
    </div>
@endcheckCondition
