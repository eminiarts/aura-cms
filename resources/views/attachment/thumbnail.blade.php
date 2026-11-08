{{-- The grid view can render non-Attachment resources; only Attachment-likes have isImage().
     SVGs are excluded: the thumbnail pipeline rasterizes via GD, which cannot
     read SVG, so an <img> would 404 — render the file icon instead. --}}
@if (method_exists($row, 'isImage') && $row->isImage() && $row->mime_type !== 'image/svg+xml')
    <img src="{{ $row->thumbnail('sm') }}" alt="{{ $row->alt_text ?: $row->name }}" loading="lazy"
        class="object-cover pointer-events-none">
@else
    {{-- Placeholder tile, the same in the media library plugin: gray-100 under the tile's shared hairline
         (.aura-tile-ring in the grid), one neutral document icon (the dashboard's recent uploads use it too)
         for every non-image type, the type as a small label. --}}
    <div class="flex flex-col gap-1.5 justify-center items-center w-full h-full text-gray-400 bg-gray-100 rounded-lg transition dark:bg-gray-800 dark:text-gray-500 group-hover:text-gray-500 dark:group-hover:text-gray-400">
        <x-aura::icon.file-document class="w-7 h-7" />
        <span class="px-1 text-[11px] font-medium text-gray-500 truncate max-w-full dark:text-gray-400">{{ $row->readable_mime_type }}</span>
    </div>
@endif
