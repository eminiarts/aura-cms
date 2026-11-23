@php
    $controlStates = [
        'default' => '',
        'hover' => 'sg-force-hover',
        'focus' => 'sg-force-focus',
        'disabled' => '',
        'error' => '',
    ];
    $selectOptions = ['draft' => 'Draft', 'review' => 'In Review', 'published' => 'Published'];
@endphp

{{-- States matrix --}}
<div class="mt-6 aura-card">
    <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('States') }}</span>
    <div class="overflow-x-auto mt-4">
        <table class="min-w-full">
            <thead>
                <tr>
                    <th class="pr-6 pb-1 text-left"><span class="sr-only">{{ __('Control') }}</span></th>
                    @foreach (array_keys($controlStates) as $state)
                        <th class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wide text-left text-gray-500 dark:text-gray-400">{{ $state }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach (['input.text', 'input.select', 'input.textarea'] as $control)
                    <tr class="align-top">
                        <td class="pt-4 pr-6 whitespace-nowrap"><code class="text-xs text-gray-500 dark:text-gray-400">{{ $control }}</code></td>
                        @foreach ($controlStates as $state => $wrapper)
                            <td class="px-3 py-2 min-w-48 {{ $wrapper }}" data-control-state="{{ $state }}">
                                @if ($state === 'error')
                                    {{-- The real field wrapper; the error comes from the component's error bag. --}}
                                    <x-aura::fields.wrapper :field="['name' => 'Slug', 'slug' => 'styleguide-slug', 'validation' => 'required']" wrapperClass="" :showLabel="false">
                                        @if ($control === 'input.select')
                                            <x-aura::input.select name="styleguide-error-select" :options="$selectOptions" selected="review" />
                                        @elseif ($control === 'input.textarea')
                                            <x-aura::input.textarea rows="2" placeholder="Write something…"></x-aura::input.textarea>
                                        @else
                                            <x-aura::input.text value="my first pöst!" />
                                        @endif
                                    </x-aura::fields.wrapper>
                                @elseif ($control === 'input.select')
                                    <x-aura::input.select :name="'styleguide-select-'.$state" :options="$selectOptions" selected="published" :disabled="$state === 'disabled'" />
                                @elseif ($control === 'input.textarea')
                                    <x-aura::input.textarea rows="2" placeholder="Write something…" :disabled="$state === 'disabled'"></x-aura::input.textarea>
                                @else
                                    <x-aura::input.text placeholder="My first post" :disabled="$state === 'disabled'" />
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                <tr class="align-top">
                    <td class="pt-3 pr-6 whitespace-nowrap"><code class="text-xs text-gray-500 dark:text-gray-400">input.checkbox</code></td>
                    @foreach ($controlStates as $state => $wrapper)
                        <td class="px-3 py-3 {{ $wrapper }}">
                            @if ($state === 'error')
                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('no error style') }}</span>
                            @else
                                <div class="flex gap-4">
                                    <x-aura::input.checkbox :label="__('Off')" :disabled="$state === 'disabled'" />
                                    <x-aura::input.checkbox :label="__('On')" checked :disabled="$state === 'disabled'" />
                                </div>
                            @endif
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
    <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">
        {{ __('One field style for every field (.aura-field): white with a gray-950/10 ring in light; in dark a gray-900 well with a gray-500 ring, 3:1 against the card. Disabled is a fill, not a fade (gray-50 / white/5 with gray-500 / gray-400 text). Error is the real fields/wrapper with a validation message: the wrapper marks the field (.aura-field-invalid), so the ring turns red (red-600, red-400 in dark) and stays red on hover and focus. fields/wrapper and input/wrapper print the same message (.aura-field-error: text-sm, red-600 / red-400).') }}
    </p>
</div>

<div class="grid gap-4 mt-4 lg:grid-cols-2">
    <div class="aura-card">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Text inputs') }}</span>
        <div class="mt-1 space-y-1">
            <x-aura::input.text label="Title" placeholder="My first post" />
            <x-aura::input.text label="With prefix" prefix="https://" placeholder="example.com" />
            <x-aura::input.text label="With suffix" suffix=".test" placeholder="aura-demo" />
            <x-aura::input.text label="Size xs" size="xs" placeholder="Compact input" />
        </div>
    </div>

    <div class="aura-card">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Input types') }}</span>
        <div class="mt-1 space-y-1">
            <x-aura::input.email label="Email" placeholder="you@example.com" />
            <x-aura::input.number label="Number" placeholder="42" />
            <div class="grid grid-cols-2 gap-4">
                <x-aura::input.text label="Date" type="date" />
                <x-aura::input.text label="Time" type="time" />
            </div>
            <div>
                <x-aura::fields.label label="Select" />
                <x-aura::input.select name="styleguide-select" :options="$selectOptions" selected="published" />
            </div>
            <div>
                <x-aura::fields.label label="Select xs" />
                <x-aura::input.select name="styleguide-select-xs" :options="$selectOptions" selected="published" size="xs" />
            </div>
        </div>
    </div>

    <div class="aura-card">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Checkboxes & radios') }}</span>
        <div class="mt-4 space-y-3">
            <x-aura::input.checkbox label="Unchecked" />
            <x-aura::input.checkbox label="Checked" checked />
            <x-aura::input.checkbox label="Disabled" disabled />
            <div class="pt-3 space-y-3 border-t border-gray-100 dark:border-white/5">
                <x-aura::input.radio name="styleguide-radio" label="Option A" checked />
                <x-aura::input.radio name="styleguide-radio" label="Option B" />
                <x-aura::input.radio name="styleguide-radio" label="Disabled" disabled />
            </div>
        </div>
    </div>

    <div class="aura-card">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Toggles') }}</span>
        <div class="mt-4 space-y-4">
            <x-aura::input.toggle wire:model="toggleOn" labelAfter="Enabled" />
            <x-aura::input.toggle wire:model="toggleOff" labelAfter="Disabled" />
            <div class="sg-force-focus">
                <x-aura::input.toggle wire:model="toggleOn" labelAfter="Focus" />
            </div>
        </div>

        <div class="pt-4 mt-6 border-t border-gray-100 dark:border-white/5">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Textarea') }}</span>
            <div class="mt-4">
                <x-aura::input.textarea placeholder="Write something…" rows="3"></x-aura::input.textarea>
            </div>
        </div>
    </div>
</div>
