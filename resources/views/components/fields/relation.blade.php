<div class="mt-16">

  {{-- {{ $field['name']}} --}}

  {{-- <x-aura::fields.label :label="$field['name']" /> --}}

  {{-- @dump(app($field['posttype'])) --}}

  <livewire:aura::table 
  :model="app($field['posttype'])" 
  :query="function($query){ return $query->where('email', 'bajram@eminiarts.ch'); }"
  :settings="[
    'create' => 'modal'
  ]"/>
</div>

