{{-- 56x40 picture of a sidebar color option (Settings, beside its radio): the sidebar strip on the left in
     that option's color, the page on the right, light (white) or dark (gray-900) as the radio group decides.
     Colors follow the sidebar CSS (.aura-sidebar-type-*, .aura-sidebar-darkmode-type-*). --}}
@php
    $sidebar = match ($type) {
        'primary' => 'bg-sidebar-bg',
        'light' => 'bg-gray-50',
        default => $dark ? 'bg-gray-950' : 'bg-gray-900',
    };
    $line = $type === 'light' ? 'bg-gray-950/15' : 'bg-white/40';
    $page = $dark ? 'bg-gray-900' : 'bg-white';
    $bar = $dark ? 'bg-white/15' : 'bg-gray-950/10';
    $edge = $type === 'light' ? ($dark ? 'border-r border-white/10' : 'border-r border-gray-950/10') : '';
@endphp
<span class="flex overflow-hidden w-14 h-10 rounded-md ring-1 shrink-0 ring-gray-950/10 dark:ring-white/10 {{ $page }}" aria-hidden="true">
    <span class="flex flex-col gap-1 px-1 pt-1.5 w-4 {{ $sidebar }} {{ $edge }}">
        <span class="h-0.5 rounded-full {{ $line }}"></span>
        <span class="h-0.5 rounded-full {{ $line }}"></span>
        <span class="h-0.5 rounded-full {{ $line }}"></span>
    </span>
    <span class="flex flex-col flex-1 gap-1 p-1.5">
        <span class="w-3/4 h-1 rounded-full {{ $bar }}"></span>
        <span class="w-1/2 h-1 rounded-full {{ $bar }}"></span>
    </span>
</span>
