{{-- A resource may lead the list toolbar's "more" menu (the actions menu, or on phones the Columns menu when
     there are no bulk actions) with its own items: aura/{type}/actions-menu, the app's view first, then the
     package's. The users list puts "Invite user" there on phones, so the page shows one "more" button. --}}
@php
    $type = Str::lower($model->getType());
    $leadItemsView = collect(['aura.'.$type.'.actions-menu', 'aura::aura.'.$type.'.actions-menu'])->first(fn ($view) => View::exists($view));
@endphp
@if ($leadItemsView)
    @include($leadItemsView, ['itemClass' => $itemClass])
@endif
