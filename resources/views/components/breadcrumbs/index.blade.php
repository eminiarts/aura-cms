<nav class="flex" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        {{ $slot }}
    </ol>
</nav>

{{-- Livewire component bookmarkPage --}}

<livewire:aura::bookmark-page :site="['title' => url()->current(), 'url' => url()->current()]" />

{{-- @livewire('aura::bookmark-page', ['site' => ['title' => 'Example Page', 'url' => 'https://example.com']]) --}}
