@php
    $thumbnailField = $field['thumbnail'] ?? null;
    $thumbnailValue = ($thumbnailField && $thumbnailField !== '')
        ? ($item->{$thumbnailField} ?? null)
        : null;

    // Image fields may store a single attachment id or a list of ids.
    if (is_array($thumbnailValue)) {
        $thumbnailId = $thumbnailValue[0] ?? null;
    } elseif (is_object($thumbnailValue) && method_exists($thumbnailValue, 'first')) {
        $first = $thumbnailValue->first();
        $thumbnailId = is_object($first) ? ($first->id ?? $first->getKey() ?? null) : $first;
    } else {
        $thumbnailId = $thumbnailValue;
    }
@endphp

<div class="flex items-center space-x-2">
    @if($thumbnailField)
        @if($thumbnailId)
            <x-aura::image :id="$thumbnailId" alt="{{ $item->title() }}" class="object-cover w-5 h-5 aura-radius-small shrink-0" />
        @else
            <div class="aura-chip-avatar flex justify-center items-center w-5 h-5 aura-radius-small shrink-0">
            </div>
        @endif
    @endif
    <div>
      <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $item->title() }}</span>
      @if(isset($field['description']) && $field['description'] && $field['description'] != '')
        @if($item->{$field['description']})
          <div class="line-clamp-1">
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item->{$field['description']} }}</p>
          </div>
        @endif
      @endif
    </div>
</div>
