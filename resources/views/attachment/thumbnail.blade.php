{{-- The grid view can render non-Attachment resources; only Attachment-likes have isImage(). --}}
@if (method_exists($row, 'isImage') && $row->isImage())
    <img src="{{ $row->thumbnail('sm') }}" alt="{{ $row->alt_text ?: $row->name }}" loading="lazy"
        class="object-cover pointer-events-none transition duration-300 group-hover:scale-[1.03]">
@else
    <div class="flex justify-center items-center max-w-sm text-gray-300 bg-gray-100 rounded-lg transition dark:bg-gray-700 dark:text-gray-500 group-hover:text-gray-400">
        @include('aura::attachment.icon', ['class' => 'h-10 w-10', 'attachment' => $row])
    </div>
@endif
