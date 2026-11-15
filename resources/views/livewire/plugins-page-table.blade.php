{{-- Package rows for the plugins page: a fixed-width table from sm, stacked rows on phones. The version
     is plain mono text; only a dev-only package carries a pill ("Dev"). --}}
<div class="hidden mt-2 sm:block">
    <table class="min-w-full table-fixed">
        <colgroup>
            <col class="w-[36%]">
            <col class="w-32">
            <col>
        </colgroup>
        <thead>
            <tr class="border-b border-gray-200/80 dark:border-white/10">
                <th class="px-4 py-2.5 text-left whitespace-nowrap aura-table-header">{{ __('Package') }}</th>
                <th class="px-4 py-2.5 text-left whitespace-nowrap aura-table-header">{{ __('Version') }}</th>
                <th class="px-4 py-2.5 text-left whitespace-nowrap aura-table-header">{{ __('Description') }}</th>
            </tr>
        </thead>
        <tbody class="border-b divide-y divide-gray-200/60 border-gray-200/80 dark:divide-white/[0.06] dark:border-white/10">
            @foreach ($packages as $name => $package)
                <tr wire:key="{{ $keyPrefix }}-{{ $name }}">
                    <td class="px-4 py-3 text-sm align-top">
                        <div class="flex gap-2 items-center min-w-0 leading-6">
                            <span class="font-medium text-gray-900 truncate dark:text-white" title="{{ $name }}">{{ $name }}</span>
                            @if ($package['dev'] ?? false)
                                <span class="aura-tag shrink-0">{{ __('Dev') }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm align-top whitespace-nowrap">
                        <span class="font-mono text-[13px] leading-6 tabular-nums text-gray-600 dark:text-gray-300">{{ $package['version'] }}</span>
                    </td>
                    <td class="px-4 py-3 text-sm leading-6 align-top text-gray-600 dark:text-gray-300">
                        <p class="line-clamp-2" title="{{ $package['description'] }}">{{ $package['description'] ?: __('No description available.') }}</p>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Phones: the stacked rows of the other index pages, package and version on one line (a long name
     wraps at its slash or hyphen, not cut mid-word), the description clamped to two. --}}
<ul class="mt-2 border-t border-b divide-y divide-gray-200/60 border-gray-200/80 sm:hidden dark:divide-white/[0.06] dark:border-white/10">
    @foreach ($packages as $name => $package)
        <li class="py-3" wire:key="{{ $keyPrefix }}-mobile-{{ $name }}">
            <div class="flex gap-3 justify-between items-start min-w-0">
                <span class="min-w-0 text-sm font-medium text-gray-900 [overflow-wrap:anywhere] dark:text-white">{{ $name }}</span>
                <span class="flex gap-1.5 items-center shrink-0">
                    @if ($package['dev'] ?? false)
                        <span class="aura-tag">{{ __('Dev') }}</span>
                    @endif
                    <span class="font-mono text-[13px] leading-5 tabular-nums text-gray-600 dark:text-gray-300">{{ $package['version'] }}</span>
                </span>
            </div>
            <p class="mt-0.5 text-sm text-gray-500 line-clamp-2 dark:text-gray-400">{{ $package['description'] ?: __('No description available.') }}</p>
        </li>
    @endforeach
</ul>
