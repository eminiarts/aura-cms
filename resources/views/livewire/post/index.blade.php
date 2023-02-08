<div>
    @section('title', 'All ' . Str::plural($slug) . ' • ')
    
    <div class="flex items-center justify-between">
        <x-breadcrumbs>
            <x-breadcrumbs.li :href="route('dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-7 h-7 mr-0" />
            <x-breadcrumbs.li :title="Str::plural($slug)"  />
        </x-breadcrumbs.li>
        
        <div>
            <x-button.transparent route="posttype.edit" :id="$slug" size="" >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Edit Posttype
            </x-button.transparent>
        </div>
    </div>
    
    
    @if($widgets = $post->getWidgets())
    <x-widgets :widgets="$widgets" />
    @endif
    
    {{-- @dump($post) --}}
    {{-- @dump($fields) --}}
    
    <livewire:table.table :model="$post"/>
</div>
