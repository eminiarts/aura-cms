{{--
    Entity avatar: the record's image when it has one, otherwise its initials
    on a tint derived from the name. Pass a resource record, or name/shape/tone
    directly for specimens. Sizes: xs 20px (relation links), sm 24px (table
    rows), md 28px (stacked lists), lg 40px (record header). Every size shows
    the same initials.
--}}
@props([
    'record' => null,
    'name' => null,
    'shape' => null,
    'tone' => null,
    'imageId' => null,
    'size' => 'sm',
])

@php
    if ($record) {
        $name ??= (string) $record->title();
        $shape ??= $record->avatarShape();
        $tone ??= $record->avatarTone();
        $imageId ??= $record->avatarImageId();
        $initials = $record->avatarInitials();
    } else {
        $words = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = $words === [] ? '?' : Str::upper(Str::substr($words[0], 0, 1).(count($words) > 1 ? Str::substr(end($words), 0, 1) : ''));
        $tones = \Aura\Base\Resource::avatarTones();
        $tone ??= $tones[crc32(Str::lower(trim((string) $name))) % count($tones)];
    }

    $shape = $shape ?: 'square';
    $size = in_array($size, ['xs', 'sm', 'md', 'lg'], true) ? $size : 'sm';

    $imageUrl = null;

    if ($imageId) {
        try {
            $attachment = app(config('aura.resources.attachment'))->newQuery()->find($imageId);

            if ($attachment && \Illuminate\Support\Facades\Gate::allows('view', $attachment) && $attachment->isImage()) {
                $imageUrl = $attachment->thumbnail('xs');
            }
        } catch (\Throwable $e) {
            $imageUrl = null;
        }
    }

    $classes = "aura-avatar aura-avatar-{$size} aura-avatar-{$shape} ".($imageUrl ? 'aura-avatar-image' : "aura-avatar-tone-{$tone}");
@endphp

<span {{ $attributes->merge(['class' => $classes]) }} aria-hidden="true" data-avatar>
    @if ($imageUrl)
        <img src="{{ $imageUrl }}" alt="" loading="lazy">
    @else
        {{ $initials }}
    @endif
</span>
