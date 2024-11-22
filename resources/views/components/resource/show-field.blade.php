

@php
    $validation = 'fields.' . $field['_id'] - 1 . '.slug';
@endphp

<div>

  @error($validation) <span class="text-sm font-semibold text-red-500 error">{{ $message }}</span> @enderror

  @if (optional($field)['field']->group)
    <div class="aura-card">
      <div class="flex justify-between mb-4 flex-start">

        <div class="mt-1 mr-2 draggable-handle">
          <svg class="w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 9L2 12M2 12L5 15M2 12H22M9 5L12 2M12 2L15 5M12 2V22M15 19L12 22M12 22L9 19M19 9L22 12M22 12L19 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>

        <div class="flex-1">
          <span class="text-lg font-semibold">{{ $field['name'] }}</span>
          @php
            // split string by / and get last item
            $field['type'] = explode('\\', $field['type']);
            // get last item of array as string
            $field['type'] = end($field['type']);
          @endphp
          <span class="inline-flex items-center px-2.5 py-0.5 ml-1 text-xs font-medium rounded-full bg-primary-100 dark:bg-primary-400/10 text-primary-800 dark:text-primary-300 dark:ring-1 dark:ring-primary-400/20">
            <svg class="mr-1.5 -ml-0.5 w-2 h-2 text-primary-500 dark:text-primary-400" fill="currentColor" viewBox="0 0 8 8">
            <circle cx="4" cy="4" r="3" />
            </svg>
            {{ $field['type'] }}
          </span>
        </div>

        <div>
          <x-aura::tippy text="Edit field">
             <div wire:click="$dispatch('openSlideOver', { component: 'edit-field', parameters: { fieldSlug: '{{ $field['slug'] }}', slug: '{{ $slug }}', model: '{{ $this->slug }}', field: @js($this->sendField($field['slug'])) }})">
            <x-aura::button.border>
              <x-aura::icon.edit class="w-5 h-5" />
            </x-aura::button.border>
            </div>
          </x-aura::tippy>
        </div>
      </div>

      @if (isset($field['fields']))
        <div class="flex flex-wrap items-start -mx-4 draggable-container">
          @foreach($field['fields'] as $key => $f)
            <div
              style="width: {{ optional(optional($f)['style'])['width'] ?? '100' }}%;"
              id="field_{{ optional($f)['_id'] }}"
              class="px-4 reorder-item draggable-item resource-field-{{ optional($f)['slug'] }}-wrapper"
              wire:key="pt-field-{{ $f['_id'] }}"
            >
              @include('aura::components.resource.show-field', ['field' => $f, 'slug' => $slug])
            </div>
          @endforeach
        </div>

        @foreach($field['fields'] as $key => $f)
          @if ($loop->last)
          <div class="w-full">
              @if ($f['type'] == 'Aura\Base\Fields\Repeater')
              @elseif ($f['type'] == 'Aura\Base\Fields\Tab')
                <div class="w-full">
                  <x-aura::resource.add-field :id="$f['_id']" :slug="$field['slug']" :type="$f['type']" :children="$this->countChildren($field)" :model="$this->slug" />
                </div>
              @elseif ($f['type'] == 'Aura\Base\Fields\Panel')
                <div class="w-full">
                  <x-aura::resource.add-field :id="$f['_id']" :slug="$field['slug']" :type="$f['type']" :children="$this->countChildren($field)" :model="$this->slug" />
                </div>
              @else
                <div class="w-full">
                  <x-aura::resource.add-field :id="$f['_id']" :slug="$field['slug']" type="Aura\Base\Fields\Text" :children="$this->countChildren($f)" :model="$this->slug" />
                </div>
              @endif
            </div>
          @endif
        @endforeach

      @else
        <div class="flex relative flex-wrap justify-center items-center mb-4 h-12 bg-gray-50 rounded-md border border-gray-100 dark:border-gray-800 draggable-container dark:bg-black/10">
          <span class="absolute text-xs text-gray-400">Drag field here</span>
        </div>
        <x-aura::resource.add-field :id="$field['_id']" :slug="$field['slug']" type="Aura\Base\Fields\Text" :children="$this->countChildren($field)" :model="$this->slug"/>
      @endif

    </div>
    @else

        <div class="flex justify-between items-center mb-4 aura-card-small">
          <div class="mt-1 mr-2 draggable-handle">
            <svg class="w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 9L2 12M2 12L5 15M2 12H22M9 5L12 2M12 2L15 5M12 2V22M15 19L12 22M12 22L9 19M19 9L22 12M22 12L19 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </div>

          <div class="flex-1">
              <span class="text-base font-semibold">{{ $field['name'] }}</span>
              @php
                  // split string by / and get last item
                  $field['type'] = explode('\\', $field['type']);
                  // get last item of array as string
                  $field['type'] = end($field['type']);
              @endphp

              <span class="inline-flex items-center px-2.5 py-0.5 ml-1 text-xs font-medium rounded-full bg-primary-100 dark:bg-primary-400/10 text-primary-800 dark:text-primary-300 dark:ring-1 dark:ring-primary-400/20">
                  <svg class="mr-1.5 -ml-0.5 w-2 h-2 text-primary-500 dark:text-primary-400" fill="currentColor" viewBox="0 0 8 8">
                  <circle cx="4" cy="4" r="3" />
                  </svg>
                  {{ $field['type'] }}
              </span>
          </div>

          <div class="flex space-x-2">
              <x-aura::tippy text="Duplicate">
                <x-aura::button.border size="xs" class="duplicate-field-{{ $field['slug'] }}" wire:click="duplicateField({{ $field['_id'] }}, '{{ $field['slug'] }}', '{{ $this->slug }}')">
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10.5 2.0028C9.82495 2.01194 9.4197 2.05103 9.09202 2.21799C8.71569 2.40973 8.40973 2.71569 8.21799 3.09202C8.05103 3.4197 8.01194 3.82495 8.0028 4.5M19.5 2.0028C20.1751 2.01194 20.5803 2.05103 20.908 2.21799C21.2843 2.40973 21.5903 2.71569 21.782 3.09202C21.949 3.4197 21.9881 3.82494 21.9972 4.49999M21.9972 13.5C21.9881 14.175 21.949 14.5803 21.782 14.908C21.5903 15.2843 21.2843 15.5903 20.908 15.782C20.5803 15.949 20.1751 15.9881 19.5 15.9972M22 7.99999V9.99999M14.0001 2H16M5.2 22H12.8C13.9201 22 14.4802 22 14.908 21.782C15.2843 21.5903 15.5903 21.2843 15.782 20.908C16 20.4802 16 19.9201 16 18.8V11.2C16 10.0799 16 9.51984 15.782 9.09202C15.5903 8.71569 15.2843 8.40973 14.908 8.21799C14.4802 8 13.9201 8 12.8 8H5.2C4.0799 8 3.51984 8 3.09202 8.21799C2.71569 8.40973 2.40973 8.71569 2.21799 9.09202C2 9.51984 2 10.0799 2 11.2V18.8C2 19.9201 2 20.4802 2.21799 20.908C2.40973 21.2843 2.71569 21.5903 3.09202 21.782C3.51984 22 4.07989 22 5.2 22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                </x-aura::button.border>
              </x-aura::tippy>

              <x-aura::tippy text="Edit {{ $field['name'] }}" position="top-end">
                <div class="edit-field-{{ $field['slug'] }}" wire:click="$dispatch('openSlideOver', { component: 'edit-field', parameters: { fieldSlug: '{{ $field['slug'] }}', slug: '{{ $slug }}', field: @js($this->sendField($field['slug'])), model: '{{ $this->slug }}' }})">
                <x-aura::button.border size="xs">
                    <x-aura::icon.edit class="w-4 h-4" />
                </x-aura::button.border>
                </div>
              </x-aura::tippy>
          </div>
        </div>
    @endif
</div>
