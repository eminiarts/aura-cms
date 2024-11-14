<div class="max-w-24">
    @if (in_array($row->mime_type, ['image/jpeg', 'image/png', 'image/jpg']))
        <img src="{{ $row->thumbnail('sm') }}" alt=""
            class="object-cover pointer-events-none group-hover:opacity-75">
    @else
        <div class="flex justify-center items-center max-w-sm text-gray-300 bg-gray-100 rounded-lg dark:bg-gray-700">
            @include('aura::attachment.icon', ['class' => 'h-10 w-10', 'attachment' => $row])
        </div>
    @endif
</div>
