{{ app('aura')::injectView('breadcrumbs_before') }}

{{-- Phones show the page title and the top bar instead of a breadcrumb trail. --}}
<nav class="hidden items-center h-5 sm:flex" aria-label="Breadcrumb">
    <ol class="inline-flex gap-x-1.5 items-center">
        {{ $slot }}
    </ol>
    @auth
    <livewire:aura::bookmark-page :site="['title' => url()->current(), 'url' => url()->current()]" />
    @endauth
</nav>

{{ app('aura')::injectView('breadcrumbs_after') }}