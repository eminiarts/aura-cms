@props(['field','model'])

@checkCondition($this->model, $field)
{{ $slot }}
@endcheckCondition