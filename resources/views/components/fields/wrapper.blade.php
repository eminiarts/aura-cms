@props([
'field',
'wrapperClass' => 'px-4',
'showLabel' => true,
])

@php
    $label = optional($field)['name'];

    if($label && is_string(__($label))) {
        $label = __($label);
    }

    // if the field is required, add a * to the label
    if (is_string(optional($field)['validation']) && str(optional($field)['validation'])->contains('required')) {
        $label .= '*';
    }

    $help = optional($field)['instructions'];
    $model = 'post.fields.' . $field['slug'];

    $slug = Str::slug(optional($field)['slug']);
@endphp

<div id="post-field-{{ $slug }}-wrapper"
        class="{{ $wrapperClass }}">
        <style >
  #post-field-{{ $slug }}-wrapper {
    width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;
  }

  @media screen and (max-width: 768px) {
    #post-field-{{ $slug }}-wrapper {
      width: 100%;
    }
  }
</style>
  <div class="flex items-center justify-between">
    @if ($label && $showLabel)
      <x-aura::fields.label :label="$label" />
    @endif

    @if($help)

    <div class="text-gray-300">
      <x-aura::tippy text="{{ $help }}" position="left" class="text-sm text-gray-400 bg-white">
        <x-aura::icon icon="info" size='sm' />
      </x-aura::tippy>
    </div>
    @endif
  </div>

  <div class="">
      @if(isset($slot))
       {{ $slot }}
      @else
        @dump('NO SLOT DEFINED')
        <x-aura::input.text :attributes="$attributes"></x-aura::input.text>
      @endif

    @if($model)
      @error($model) <span class="text-sm font-semibold text-red-500 error">{{ $message }}</span> @enderror
    @endif

  </div>
</div>
