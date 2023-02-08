<style>
  #post-field-{{ optional($field)['slug'] }}-wrapper {
    width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;
  }

  @media screen and (max-aura::width: 768px) {
    #post-field-{{ optional($field)['slug'] }}-wrapper {
      width: 100%;
    }
  }
</style>

<div class="px-aura::2" id="post-field-{{ optional($field)['slug'] }}-wrapper">
    <div class="aura-card {{ $field['style']['class'] ?? '' }}">
        @if($field['name'] != 'Main Panel')
        <h2 class="font-semibold">{{ $field['name'] }}</h2>
        @endif

        <x-aura::fields.fields />
    </div>
</div>
