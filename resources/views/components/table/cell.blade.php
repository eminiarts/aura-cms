@props(['key' => null])

@if(optional($this->columns)[$key])
    <td {{ $attributes->merge(['class' => 'px-4 text-sm aura-table-cell text-gray-600 dark:text-gray-300']) }}>
        {{ $slot }}
    </td>
@endif
