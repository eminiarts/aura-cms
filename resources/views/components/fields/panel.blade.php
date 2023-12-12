<style >
  #post-field-{{ optional($field)['slug'] }}-wrapper {
    width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;
  }

  @media screen and (max-width: 768px) {
    #post-field-{{ optional($field)['slug'] }}-wrapper {
      width: 100%;
    }
  }
</style>

<div class="px-2" id="post-field-{{ optional($field)['slug'] }}-wrapper">
    <div class="aura-card {{ $field['style']['class'] ?? '' }}">
        <div class="mt-1 mb-2">
          @if($field['name'] != 'Main Panel')
        <div class="px-2">
          <h2 class="font-semibold">{{ __($field['name']) }}</h2>
        </div>
        @endif

        <x-aura::fields.fields />
        </div>
    </div>
</div>
