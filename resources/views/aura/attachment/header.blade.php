@if(optional(optional($this)->field)['name'])

<div class="flex items-center justify-between mt-6">
            <div>
                @if(optional(optional($this)->field)['name'])
                <h1 class="text-2xl font-semibold">{{ $this->field['name'] }}</h1>
                @else
                <h1 class="text-2xl font-semibold">{{ $model->pluralName() }}</h1>
                @endif

                @if(optional(optional($this)->field)['description'])
                <span class="text-primary-500">{{ $this->field['description'] }}</span>
                @endif
                </h3>
            </div>


        </div>
@endif
