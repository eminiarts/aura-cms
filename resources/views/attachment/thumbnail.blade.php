{{-- The grid view can render non-Attachment resources; only Attachment-likes have isImage().
     SVGs are excluded: the thumbnail pipeline rasterizes via GD, which cannot
     read SVG, so an <img> would 404 — render the file icon instead. --}}
@if (method_exists($row, 'isImage') && $row->isImage() && $row->mime_type !== 'image/svg+xml')
    <img src="{{ $row->thumbnail('sm') }}" alt="{{ $row->alt_text ?: $row->name }}" loading="lazy"
        class="object-cover pointer-events-none transition duration-300 group-row-hover:scale-[1.03]">
@else
    {{-- Same tile as the dashboard's recent uploads: a neutral document icon with the type as a small label. --}}
    <div class="flex flex-col gap-1.5 justify-center items-center w-full h-full text-gray-400 bg-gray-50 rounded-lg transition dark:bg-gray-700/50 dark:text-gray-500 group-hover:text-gray-500 dark:group-hover:text-gray-400">
        @include('aura::attachment.icon', ['class' => 'h-7 w-7', 'attachment' => $row])
        <span class="px-1 text-[11px] font-medium text-gray-500 truncate max-w-full dark:text-gray-400">{{ $row->readable_mime_type }}</span>
    </div>
@endif
