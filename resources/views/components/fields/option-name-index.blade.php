{{-- Options list: the key in words with its scope (Option::displayLabel(), displayScope()), the stored key under it. --}}
@php($scope = $row->displayScope())
<span class="flex flex-col min-w-0">
    <span class="flex gap-2 items-center min-w-0">
        <span class="truncate">{{ $row->displayLabel() }}</span>
        @if ($scope)
            <span class="aura-tag shrink-0">{{ __($scope) }}</span>
        @endif
    </span>
    <span class="font-mono text-xs font-normal text-gray-500 truncate dark:text-gray-400">{{ $value }}</span>
</span>
