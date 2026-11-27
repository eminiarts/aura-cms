{{-- 40px file thumbnail for list rows: the image, or the grid's placeholder (one document icon for every
     non-image type) on gray-100. SVGs take the placeholder, as in attachment/thumbnail. --}}
<span class="flex overflow-hidden relative justify-center items-center rounded-md size-10 shrink-0 bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500" aria-hidden="true">
    @if (method_exists($row, 'isImage') && $row->isImage() && $row->mime_type !== 'image/svg+xml')
        <img src="{{ $row->thumbnail('xs') }}" alt="" loading="lazy" class="object-cover pointer-events-none size-full">
    @else
        <x-aura::icon.file-document class="size-5" />
    @endif
    <span class="absolute inset-0 rounded-md ring-1 ring-inset pointer-events-none ring-gray-950/[0.06] dark:ring-white/10"></span>
</span>
