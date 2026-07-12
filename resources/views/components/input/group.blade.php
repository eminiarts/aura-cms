@props([
    'label',
    'for',
    'error' => false,
    'helpText' => false,
    'inline' => true,
    'paddingless' => false,
    'borderless' => true,
    'shadow' => true,
])

@if($inline)
    <div>
        <label for="{{ $for }}" class="block text-sm font-medium leading-5 text-gray-700 dark:text-gray-200">{{ $label }}</label>

        <div class="mt-1 relative rounded-md {{ $shadow ? 'shadow-none' : '' }}">
            {{ $slot }}

            @if ($error)
                <div class="mt-1 text-sm text-red-600 dark:text-red-400">{!! $error !!}</div>
            @endif

            @if ($helpText)
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $helpText }}</p>
            @endif
        </div>
    </div>
@else
    <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start {{ $borderless ? '' : ' sm:border-t ' }} sm:border-gray-400/30 {{ $paddingless ? '' : ' sm:py-5 ' }}">
        <label for="{{ $for }}" class="block text-sm font-medium leading-5 text-gray-700 dark:text-gray-200 sm:mt-px sm:pt-2">
            {{ $label }}
        </label>

        <div class="mt-1 sm:mt-0 sm:col-span-2">
            {{ $slot }}

            @if ($error)
                <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $error }}</div>
            @endif

            @if ($helpText)
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $helpText }}</p>
            @endif
        </div>
    </div>
@endif
