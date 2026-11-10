@aware(['mode', 'frame' => null])

<style >
  #resource-field-{{ optional($field)['slug'] }}-wrapper {
    width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;
  }

  @media screen and (max-width: 768px) {
    #resource-field-{{ optional($field)['slug'] }}-wrapper {
      width: 100%;
    }
  }
</style>


@php
    // Record view pages: an unboxed section (title, then one hairline row per value) unless the
    // panel brings its own class. Forms keep the card, and so does a view in its form's frame
    // (frame "form", RecordViewSections::formFrame()), so toggling view and edit keeps the card in place.
    $isSection = $mode === 'view' && $frame !== 'form' && empty($field['style']['class']);
    $showTitle = isset($field['name']) && (! isset($field['hide_title']) || $field['hide_title'] === false);
@endphp

@if ($isSection)
<div class="px-2 aura-record-section" id="resource-field-{{ optional($field)['slug'] }}-wrapper" data-record-section="{{ optional($field)['slug'] }}">
    @if ($showTitle)
        <h2 class="aura-record-section-title">{{ __($field['name']) }}</h2>
    @endif

    <div class="aura-record-section-body">
        <x-aura::fields.fields :mode="$mode" />
    </div>
</div>
@else
<div class="px-2" id="resource-field-{{ optional($field)['slug'] }}-wrapper">
    <div class="{{ $field['style']['class'] ?? 'aura-card mb-8' }}">
        <div class="mb-2">
          @if ($showTitle)
            <div class="px-2">
              <h2 class="aura-section-title">{{ __($field['name']) }}</h2>
            </div>
          @endif

          <x-aura::fields.fields :mode="$mode" />
        </div>
    </div>
</div>
@endif
