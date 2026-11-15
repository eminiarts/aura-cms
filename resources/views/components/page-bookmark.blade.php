{{-- The bookmark toggle of a top-level page, beside its title (record, edit and create pages carry it in
     their breadcrumb trail). From sm, as the trail; the tooltip names the action. Inside a Livewire
     component (the resource table) a re-render keeps the page's own URL and the same toggle. --}}
@auth
    @php
        $bookmarkUrl = \Livewire\Livewire::originalUrl();
    @endphp
    <div {{ $attributes->merge(['class' => 'hidden items-center self-center sm:flex shrink-0 -ml-0.5']) }} data-page-bookmark>
        <livewire:aura::bookmark-page :site="['title' => $bookmarkUrl, 'url' => $bookmarkUrl]" key="page-bookmark" />
    </div>
@endauth
