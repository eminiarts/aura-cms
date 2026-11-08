@php
    $slug = $field['slug'];
    $fieldItems = collect();

    // Reuse the relation eager-loaded by the table when available, otherwise
    // fall back to a scoped id lookup.
    if ($row instanceof \Illuminate\Database\Eloquent\Model && $row->relationLoaded($slug)) {
        $fieldItems = $row->getRelation($slug);
    } elseif (!empty($field['resource'])) {
        $fieldItemIds = $row->fields[$slug] ?? [];

        if (!is_array($fieldItemIds)) {
            $fieldItemIds = [$fieldItemIds];
        }

        $resourceClass = $field['resource'];
        $fieldItems = $resourceClass::whereIn('id', $fieldItemIds)->get();
    }
@endphp

<div class="flex flex-wrap gap-1.5">
    @foreach($fieldItems as $item)
        @if(isset($field['view_index']))
            @include($field['view_index'], ['item' => $item])
        @else
            @php
                $thumbnailField = $field['thumbnail'] ?? null;
                $thumbnailValue = ($thumbnailField && $thumbnailField !== '')
                    ? ($item->{$thumbnailField} ?? null)
                    : null;

                if (is_array($thumbnailValue)) {
                    $thumbnailId = $thumbnailValue[0] ?? null;
                } elseif (is_object($thumbnailValue) && method_exists($thumbnailValue, 'first')) {
                    $first = $thumbnailValue->first();
                    $thumbnailId = is_object($first) ? ($first->id ?? $first->getKey() ?? null) : $first;
                } else {
                    $thumbnailId = $thumbnailValue;
                }
            @endphp
            <span class="aura-tag">
                @if($thumbnailField && $thumbnailId)
                    <x-aura::image :id="$thumbnailId" alt="{{ $item->title() }}" class="thumbnail-image aura-avatar aura-avatar-xs aura-avatar-circle aura-avatar-image" />
                @elseif(method_exists($item, 'avatarShape') && $item->avatarShape())
                    <x-aura::avatar :record="$item" size="xs" />
                @endif
                <span class="aura-record-link-title">{{ $item->title() }}</span>
            </span>
        @endif
    @endforeach
</div>
