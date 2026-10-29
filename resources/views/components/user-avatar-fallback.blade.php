<div class="flex items-center space-x-2">
    <div
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold tracking-wide text-primary-800 shadow-sm ring-1 ring-primary-600/20 dark:bg-primary-400/10 dark:text-primary-300 dark:ring-primary-400/30"
        role="img"
        aria-label="{{ __('Avatar for :name', ['name' => $name]) }}"
    >
        {{ $initials }}
    </div>
</div>
