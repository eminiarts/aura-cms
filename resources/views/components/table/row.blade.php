@foreach($this->headers as $key => $column)
    @if(optional($this->columns)[$key])
    <td class="px-6 py-4">
        {!! $row->display($key) !!}
    </td>
    @endif
@endforeach
