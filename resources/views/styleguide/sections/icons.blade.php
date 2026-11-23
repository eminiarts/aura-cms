@php
    $icons = [
        'adjustments', 'arrow-left', 'arrow-right', 'attachment', 'bookmark', 'bookmarked', 'brush', 'check',
        'chevron-down', 'chevron-up', 'circle', 'close', 'cog', 'collection', 'color-swatch', 'config',
        'dashboard', 'dots', 'edit', 'empty', 'exclamation', 'file', 'filter', 'grid', 'impersonate', 'info',
        'kanban', 'list', 'loading', 'media', 'minus', 'move', 'notifications', 'notifications-unread',
        'option', 'permission', 'plus', 'role', 'search', 'table', 'team', 'team-invitation', 'template-plain',
        'template-tabs', 'template-tabs-panels', 'trash', 'upload', 'user', 'user-impersonate', 'view',
    ];
@endphp

<div class="flex flex-wrap gap-6 items-end mt-6 aura-card">
    @foreach (['xs' => '16px', 'sm' => '20px', 'base' => '24px', 'lg' => '32px'] as $size => $pixels)
        <div class="flex flex-col gap-2 items-center text-gray-700 dark:text-gray-200">
            <x-aura::icon icon="dashboard" :size="$size" />
            <span class="text-xs text-gray-400">{{ $size }} · {{ $pixels }}</span>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-[repeat(auto-fill,minmax(8rem,1fr))] gap-2 mt-4">
    @foreach ($icons as $icon)
        <div class="flex flex-col gap-2 items-center px-2 py-4 text-gray-600 bg-white rounded-lg ring-1 shadow-sm dark:text-gray-300 dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <x-aura::icon :icon="$icon" size="sm" />
            <span class="max-w-full text-xs text-gray-400 truncate dark:text-gray-500">{{ $icon }}</span>
        </div>
    @endforeach
</div>
