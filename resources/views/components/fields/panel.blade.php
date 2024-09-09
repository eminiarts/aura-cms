@aware(['mode'])

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

<div class="px-2" id="resource-field-{{ optional($field)['slug'] }}-wrapper">
    <div class="{{ $field['style']['class'] ?? 'aura-card' }}">
        <div class="mb-2">
          @if(isset($field['name']))
        <div class="px-2 mt-1">
          <h2 class="font-semibold">{{ __($field['name']) }}</h2>
        </div>
        @endif

        <x-aura::fields.fields />
        </div>
    </div>
</div>
