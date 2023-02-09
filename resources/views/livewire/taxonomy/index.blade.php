<div>
    @section('title', 'All '. Str::plural($slug) . ' • ')

    <div class="flex items-start justify-between">

        <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-7 h-7 mr-0" />
            <x-aura::breadcrumbs.li :title="Str::plural($slug)"  />
        </x-aura::breadcrumbs.li>

        <button class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition border border-transparent rounded-md bg-primary-800 hover:bg-primary-700 active:bg-primary-900 focus:outline-none focus:border-primary-900 focus:ring focus:ring-primary-300 disabled:opacity-25" wire:click='$emit("openModal", "taxonomy.create", {{ json_encode([$slug, null]) }})'>Create Taxonomy</button>

    </div>

    <livewire:table.table :model="$taxonomy" :slug="$slug"/>
</div>
