@php
    $buttonVariants = [
        'button' => 'aura::button',
        'button.primary' => 'aura::button.primary',
        'button.light' => 'aura::button.light',
        'button.border' => 'aura::button.border',
        'button.transparent' => 'aura::button.transparent',
        'button.danger' => 'aura::button.danger',
        'button.error' => 'aura::button.error',
    ];

    // Hover and focus are forced with the style guide's .sg-force-* wrappers (resources/js/styleguide.js).
    $buttonStates = [
        'default' => '',
        'hover' => 'sg-force-hover',
        'focus' => 'sg-force-focus',
        'active' => 'sg-force-active',
        'disabled' => '',
        'loading' => '',
    ];
@endphp

<div class="mt-6 aura-card">
    <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Variants × states') }}</span>
    <div class="overflow-x-auto mt-4">
        <table class="min-w-full">
            <thead>
                <tr>
                    <th class="pr-6 pb-3 text-left"><span class="sr-only">{{ __('Variant') }}</span></th>
                    @foreach (array_keys($buttonStates) as $state)
                        <th class="px-3 pb-3 text-[11px] font-semibold uppercase tracking-wide text-left text-gray-500 dark:text-gray-400">{{ $state }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($buttonVariants as $label => $component)
                    <tr>
                        <td class="py-2 pr-6 whitespace-nowrap"><code class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</code></td>
                        @foreach ($buttonStates as $state => $wrapper)
                            <td class="px-3 py-2">
                                <span class="inline-flex {{ $wrapper }}" data-button-state="{{ $state }}">
                                    @if ($state === 'disabled')
                                        <x-dynamic-component :component="$component" disabled>{{ __('Save') }}</x-dynamic-component>
                                    @elseif ($state === 'loading')
                                        <x-dynamic-component :component="$component" disabled>
                                            <x-aura::icon.loading />
                                            {{ __('Save') }}
                                        </x-dynamic-component>
                                    @else
                                        <x-dynamic-component :component="$component">{{ __('Save') }}</x-dynamic-component>
                                    @endif
                                </span>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">
        {{ __('Loading uses the Settings page pattern: icon.loading inside a disabled button. The spinner is hard-coded white with a light track (mr-3 text-white), so it disappears on light, border and transparent buttons.') }}
    </p>
</div>

<div class="mt-4 aura-card">
    <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Sizes') }}</span>
    <div class="flex flex-wrap gap-4 items-end mt-4">
        @foreach (['xs', 'sm', 'base', 'lg', 'xl'] as $size)
            <div class="flex flex-col gap-2 items-center">
                <x-aura::button :size="$size">{{ __('Button') }}</x-aura::button>
                <span class="text-xs text-gray-400">{{ $size }}</span>
            </div>
        @endforeach
        <div class="flex flex-col gap-2 items-center">
            <x-aura::button.border size="icon-xs"><x-aura::icon icon="dots" size="xs" /></x-aura::button.border>
            <span class="text-xs text-gray-400">icon-xs</span>
        </div>
    </div>
    <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">{{ __('Size sets the height (--aura-control-height-*), padding, text size and gap. Icon-only buttons are square. Measured heights are in Control sizing.') }}</p>
</div>

<div class="mt-4 aura-card">
    <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Compositions') }}</span>
    <div class="flex flex-wrap gap-4 items-center mt-4">
        <div class="flex flex-col gap-2 items-center">
            <x-aura::button>
                <x-slot:icon><x-aura::icon icon="plus" size="sm" /></x-slot:icon>
                <span>{{ __('Create Post') }}</span>
            </x-aura::button>
            <span class="text-xs text-gray-400">{{ __('with icon slot') }}</span>
        </div>
        <div class="flex flex-col gap-2 items-center">
            <x-aura::button.border>
                <x-aura::icon icon="dots" size="xs" />
            </x-aura::button.border>
            <span class="text-xs text-gray-400">{{ __('icon only') }}</span>
        </div>
        <div class="flex flex-col gap-2 items-center">
            <x-aura::button href="#buttons" :navigate="false">{{ __('Link') }}</x-aura::button>
            <span class="text-xs text-gray-400">href (renders &lt;a&gt;)</span>
        </div>
        <div class="flex flex-col flex-1 gap-2 items-center min-w-48">
            <x-aura::button block>{{ __('Block button') }}</x-aura::button>
            <span class="text-xs text-gray-400">block</span>
        </div>
    </div>
</div>
