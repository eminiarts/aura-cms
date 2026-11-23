<div class="mt-6 aura-card">
    <div class="flex flex-wrap gap-x-3 items-baseline pb-4 mb-4 border-b border-gray-100 dark:border-white/5" x-data="auraCssVar('--aura-font-sans')">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">--aura-font-sans</span>
        <code class="text-xs text-gray-600 dark:text-gray-300" x-text="value"></code>
    </div>
    <div class="divide-y divide-gray-100 dark:divide-white/5">
        @foreach ([
            ['text-3xl font-semibold tracking-tight', 'text-3xl · semibold', 'Aura CMS'],
            ['text-2xl font-semibold tracking-tight', 'text-2xl · semibold — page title', 'Page title'],
            ['text-xl font-semibold', 'text-xl · semibold — section title', 'Section title'],
            ['text-lg font-semibold', 'text-lg · semibold — modal title', 'Modal title'],
            ['text-base font-medium', 'text-base · medium', 'The quick brown fox jumps over the lazy dog'],
            ['text-sm', 'text-sm — body / inputs / table cells', 'The quick brown fox jumps over the lazy dog'],
            ['text-xs', 'text-xs — captions / badges', 'The quick brown fox jumps over the lazy dog'],
            ['text-2xs', 'text-2xs — compact sidebar headings', 'The quick brown fox jumps over the lazy dog'],
        ] as [$classes, $label, $sample])
            <div class="grid gap-1 py-4 first:pt-0 last:pb-0 sm:grid-cols-[16rem_1fr] sm:items-baseline sm:gap-6">
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ $label }}</span>
                <span class="text-gray-900 dark:text-white {{ $classes }}">{{ $sample }}</span>
            </div>
        @endforeach
    </div>
</div>

<div class="mt-4 aura-card">
    <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Recurring text styles') }}</span>
    <div class="mt-4 space-y-4">
        <div class="grid gap-1 sm:grid-cols-[16rem_1fr] sm:items-baseline sm:gap-6">
            <span class="text-xs text-gray-400 dark:text-gray-500">field label (fields/label)</span>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Title*</span>
        </div>
        <div class="grid gap-1 sm:grid-cols-[16rem_1fr] sm:items-baseline sm:gap-6">
            <span class="text-xs text-gray-400 dark:text-gray-500">table header (table/heading)</span>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Created at</span>
        </div>
        <div class="grid gap-1 sm:grid-cols-[16rem_1fr] sm:items-baseline sm:gap-6">
            <span class="text-xs text-gray-400 dark:text-gray-500">help text</span>
            <span class="text-sm text-gray-500 dark:text-gray-400">A short hint that helps the user fill this field.</span>
        </div>
        <div class="grid gap-1 sm:grid-cols-[16rem_1fr] sm:items-baseline sm:gap-6">
            <span class="text-xs text-gray-400 dark:text-gray-500">validation error (fields/wrapper)</span>
            <span class="text-sm aura-field-error">The title field is required.</span>
        </div>
        <div class="grid gap-1 sm:grid-cols-[16rem_1fr] sm:items-baseline sm:gap-6">
            <span class="text-xs text-gray-400 dark:text-gray-500">sidebar heading</span>
            <span class="text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Resources</span>
        </div>
        <div class="grid gap-1 sm:grid-cols-[16rem_1fr] sm:items-baseline sm:gap-6">
            <span class="text-xs text-gray-400 dark:text-gray-500">link</span>
            <a href="#typography" class="text-sm font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400">Forgot your password?</a>
        </div>
    </div>
</div>
