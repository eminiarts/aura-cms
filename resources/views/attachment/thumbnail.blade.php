@if(in_array($row->mime_type, ['image/jpeg', 'image/png', 'image/jpg']))
<img src="/storage/{{ $row->url }}" alt="" class="pointer-events-none object-cover group-hover:opacity-75">

@else

<div class="flex justify-center items-center max-w-sm bg-gray-100 rounded-lg dark:bg-gray-700 text-gray-300">
    @include('attachment.icon', ['class' => 'h-10 w-10', 'attachment' => $row])
</div>

@endif
