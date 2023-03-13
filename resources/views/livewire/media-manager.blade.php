<div class="w-full p-8">
    <div class="">
        <livewire:aura::table :model="app('Eminiarts\Aura\Resources\Attachment')"/>
    </div>

    {{--
        $maxWidths = [
        'sm'  => 'sm:max-w-sm',
        'md'  => 'sm:max-w-md',
        'lg'  => 'sm:max-w-md md:max-w-lg',
        'xl'  => 'sm:max-w-md md:max-w-xl',
        '2xl' => 'sm:max-w-md md:max-w-xl lg:max-w-2xl',
        '3xl' => 'sm:max-w-md md:max-w-xl lg:max-w-3xl',
        '4xl' => 'sm:max-w-md md:max-w-xl lg:max-w-3xl xl:max-w-4xl',
        '5xl' => 'sm:max-w-md md:max-w-xl lg:max-w-3xl xl:max-w-5xl',
        '6xl' => 'sm:max-w-md md:max-w-xl lg:max-w-3xl xl:max-w-5xl 2xl:max-w-6xl',
        '7xl' => 'sm:max-w-md md:max-w-xl lg:max-w-3xl xl:max-w-5xl 2xl:max-w-7xl',
        'base' => 'inline-block w-full align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:w-full flex items-end justify-center min-h-screen px-4 pt-4 pb-10 text-center sm:block sm:p-0'
    ]
        --}}

    {{-- Footer with 2 buttons: close and select --}}
    <div class="flex justify-end mt-4">
        <x-aura::button class="ml-4" wire:click="$emit('closeModal')">
            {{ __('Close') }}
        </x-aura::button>
        <x-aura::button.primary class="ml-4" wire:click="select">
            {{ __('Select') }}
        </x-aura::button.primary>
</div>
