{{--
    Related-record card: a BelongsTo value on the record page, shown as a small
    card with the related record's avatar, resource name, title and up to three
    of its own values. The title links to the record; with a pointer the whole
    card opens it (clicks on links inside the facts are left alone).
--}}
@props(['record', 'label' => null, 'facts' => []])

@php
    $url = (auth()->user()?->can('view', $record) ?? false) ? $record->viewUrl() : null;
    $hasAvatar = method_exists($record, 'avatarShape') && $record->avatarShape();
@endphp

<div {{ $attributes->merge(['class' => 'aura-record-card group'.($url ? ' cursor-pointer' : '')]) }}
    @if ($url)
    x-on:click="if (! $event.target.closest('a, button') && ! window.getSelection().toString()) { ($event.metaKey || $event.ctrlKey) ? window.open(@js($url), '_blank') : Livewire.navigate(@js($url)) }"
    @endif
    data-record-card>
    <div class="flex gap-3 items-center">
        @if ($hasAvatar)
            <x-aura::avatar :record="$record" size="lg" />
        @elseif (method_exists($record, 'getIcon') && (string) $record->getIcon() !== '')
            <span class="aura-avatar aura-avatar-lg aura-avatar-square aura-avatar-tone-gray [&>svg]:size-5" aria-hidden="true">{!! $record->getIcon() !!}</span>
        @endif
        <div class="flex-1 min-w-0">
            <p class="aura-eyebrow">{{ $label ?? __($record->singularName()) }}</p>
            @if ($url)
                <a href="{{ $url }}" wire:navigate class="block mt-0.5 text-sm font-semibold text-gray-900 truncate rounded-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-white">{{ $record->title() }}</a>
            @else
                <p class="mt-0.5 text-sm font-semibold text-gray-900 truncate dark:text-white">{{ $record->title() }}</p>
            @endif
        </div>
        @if ($url)
            <x-aura::icon.caret direction="right" class="w-4 h-4 text-gray-400 transition-colors duration-150 shrink-0 group-hover:text-gray-600 dark:text-gray-400 dark:group-hover:text-gray-200" />
        @endif
    </div>

    @if ($facts !== [])
        {{-- Phones stack each label above its value (left-aligned), so values start on one edge and long
             ones get the full card width; from sm the label takes a fixed 88px column and the value
             starts right after it, so values line up instead of drifting to the far edge. --}}
        <dl class="pt-3 mt-3 space-y-2.5 border-t border-gray-100 sm:space-y-1.5 dark:border-white/[0.06]">
            @foreach ($facts as $fact)
                <div class="flex flex-col gap-0.5 text-sm sm:grid sm:grid-cols-[5.5rem_minmax(0,1fr)] sm:gap-x-3 sm:items-center">
                    <dt class="text-gray-500 shrink-0 dark:text-gray-400">{{ __($fact['name']) }}</dt>
                    @php $plain = is_scalar($fact['value']) ? trim(html_entity_decode(strip_tags((string) $fact['value']), ENT_QUOTES)) : ''; @endphp
                    <dd class="min-w-0 text-gray-900 truncate dark:text-gray-100" @if ($plain !== '') title="{{ $plain }}" @endif>{!! $fact['value'] !!}</dd>
                </div>
            @endforeach
        </dl>
    @endif
</div>
