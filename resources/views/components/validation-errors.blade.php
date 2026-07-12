@if (count($errors->all()))
    <div {{ $attributes->merge(['class' => 'form_errors flex gap-3 items-start p-4 bg-red-50 rounded-lg ring-1 ring-inset ring-red-600/10 dark:bg-red-500/10 dark:ring-red-500/20']) }}>
        <svg class="w-5 h-5 shrink-0 text-red-600 dark:text-red-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
        </svg>
        <div class="text-sm">
            <strong class="block font-semibold text-red-800 dark:text-red-300">{{ __('Unfortunately, there were still the following validation errors:') }}</strong>
            <ul class="mt-2 space-y-1 list-disc list-inside text-red-700 dark:text-red-300/90">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
