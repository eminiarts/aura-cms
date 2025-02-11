<x-dynamic-component :component="config('aura.views.layout')">
    <x-slot name="header">
        <h2 class="text-2xl font-semibold leading-tight text-gray-800">
            {{ __('Create Team') }}
        </h2>
    </x-slot>

    <div>
        <div class="py-10 mx-auto max-w-7xl sm:px-6 lg:px-8">
            @livewire('teams.create-team-form')
        </div>
    </div>
</x-dynamic-component>
