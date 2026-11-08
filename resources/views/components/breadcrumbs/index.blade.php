{{ app('aura')::injectView('breadcrumbs_before') }}

{{-- Phones show the page title and the top bar instead of a breadcrumb trail. --}}
<nav class="hidden sm:flex" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        {{ $slot }}
    </ol>
    @auth
    <livewire:aura::bookmark-page :site="['title' => url()->current(), 'url' => url()->current()]" />
    @endauth
</nav>

{{ app('aura')::injectView('breadcrumbs_after') }}