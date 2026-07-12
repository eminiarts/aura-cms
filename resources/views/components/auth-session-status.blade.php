@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'flex gap-3 items-start p-4 text-sm text-green-800 bg-green-50 rounded-lg ring-1 ring-inset ring-green-600/10 dark:bg-green-500/10 dark:text-green-300 dark:ring-green-500/20']) }}>
        <svg class="w-5 h-5 shrink-0 text-green-600 dark:text-green-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
        </svg>
        <p class="font-medium">{{ $status }}</p>
    </div>
@endif
