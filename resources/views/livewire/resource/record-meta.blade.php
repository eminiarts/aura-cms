{{--
    Record meta line: "Created Nov 3, 2026 by Bajram Emini · Updated 4 hours ago". Uses only what the
    model already holds: its timestamps and, when the record carries a user_id, its author (one
    primary-key lookup through the base user() relation, so a field named "user" cannot shadow it).
    Updated is left out while it still equals Created.
--}}
@php
    $createdAt = $model->created_at ?? null;
    $updatedAt = $model->updated_at ?? null;
    $author = null;

    if (array_key_exists('user_id', $model->getAttributes()) && $model->user_id && method_exists($model, 'user')) {
        try {
            $author = $model->relationLoaded('user') ? $model->getRelation('user') : $model->user()->first();
        } catch (\Throwable $e) {
            $author = null;
        }
    }

    $wasUpdated = $createdAt && $updatedAt && $updatedAt->diffInSeconds($createdAt) >= 60;
@endphp

@if ($createdAt)
    <p {{ $attributes->merge(['class' => 'aura-record-meta']) }} data-record-meta>
        <span>
            {{ __('Created') }}
            <time datetime="{{ $createdAt->toIso8601String() }}" title="{{ $createdAt->isoFormat('LLLL') }}">{{ $createdAt->isoFormat('ll') }}</time>
            @if ($author)
                {{ __('by') }} <span data-record-meta-author>{{ $author->name ?? $author->title() }}</span>
            @endif
        </span>
        @if ($wasUpdated)
            <span class="aura-record-meta-sep" aria-hidden="true">&middot;</span>
            <span>
                {{ __('Updated') }}
                <time datetime="{{ $updatedAt->toIso8601String() }}" title="{{ $updatedAt->isoFormat('LLLL') }}">{{ $updatedAt->diffForHumans() }}</time>
            </span>
        @endif
    </p>
@endif
