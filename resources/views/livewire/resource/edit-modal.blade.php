<div class="">
    @if(isset($model) && $model)
        <livewire:aura::resource-edit :slug="$type" :id="$resource" :inModal="true" />
    @else
        <div class="p-4 text-center text-gray-500">
            {{ __('Resource not found') }}
        </div>
    @endif
</div>
