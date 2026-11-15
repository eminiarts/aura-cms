@php
    // Defensive: works with LengthAwarePaginator and simple paginators.
    $isLengthAware = method_exists($paginator, 'total');
    $pageName = method_exists($paginator, 'getPageName') ? $paginator->getPageName() : 'page';

    $btnBase = 'inline-flex items-center justify-center h-8 text-sm font-medium rounded-control transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 disabled:opacity-50';
    $btnIdle = 'text-gray-600 hover:bg-gray-950/5 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white';
    // The current page is no box: its number in primary over the lying mark (.aura-mark-x).
    $btnCurrent = 'relative font-semibold text-primary-700 dark:text-primary-300';
    $btnDisabled = 'text-gray-400 dark:text-gray-500 cursor-default';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex justify-between items-center">

        {{-- Mobile: page position and the same chevron pager as desktop --}}
        <div class="flex flex-1 gap-2 justify-between items-center sm:hidden">
            <p class="text-sm tabular-nums text-gray-600 dark:text-gray-400">
                @if ($isLengthAware)
                    {!! __('Page :current of :last', ['current' => '<span class="font-medium text-gray-900 dark:text-white">'.$paginator->currentPage().'</span>', 'last' => '<span class="font-medium text-gray-900 dark:text-white">'.$paginator->lastPage().'</span>']) !!}
                @else
                    {!! __('Page :current', ['current' => '<span class="font-medium text-gray-900 dark:text-white">'.$paginator->currentPage().'</span>']) !!}
                @endif
            </p>

            <div class="flex gap-1 items-center">
                @if ($paginator->onFirstPage())
                    <span class="{{ $btnBase }} {{ $btnDisabled }} w-9 h-9" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                        <x-aura::icon.caret direction="left" class="w-4 h-4" />
                    </span>
                @else
                    <button type="button" wire:click="previousPage('{{ $pageName }}')" wire:loading.attr="disabled" class="{{ $btnBase }} {{ $btnIdle }} w-9 h-9" aria-label="{{ __('pagination.previous') }}">
                        <x-aura::icon.caret direction="left" class="w-4 h-4" />
                    </button>
                @endif

                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $pageName }}')" wire:loading.attr="disabled" class="{{ $btnBase }} {{ $btnIdle }} w-9 h-9" aria-label="{{ __('pagination.next') }}">
                        <x-aura::icon.caret direction="right" class="w-4 h-4" />
                    </button>
                @else
                    <span class="{{ $btnBase }} {{ $btnDisabled }} w-9 h-9" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                        <x-aura::icon.caret direction="right" class="w-4 h-4" />
                    </span>
                @endif
            </div>
        </div>

        {{-- Desktop --}}
        <div class="hidden sm:flex sm:flex-1 sm:justify-between sm:items-center">
            <div>
                <p class="text-sm tabular-nums text-gray-600 dark:text-gray-400">
                    {!! __('Showing') !!}
                    @if ($isLengthAware && $paginator->firstItem())
                        <span class="font-medium text-gray-900 dark:text-white">{{ $paginator->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium text-gray-900 dark:text-white">{{ $paginator->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium text-gray-900 dark:text-white">{{ $paginator->total() }}</span>
                    @else
                        <span class="font-medium text-gray-900 dark:text-white">{{ $paginator->count() }}</span>
                    @endif
                    {!! __('results') !!}
                </p>
            </div>

            <div class="flex gap-1 items-center">
                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <span class="{{ $btnBase }} {{ $btnDisabled }} w-8" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                        <x-aura::icon.caret direction="left" class="w-4 h-4" />
                    </span>
                @else
                    <button type="button" wire:click="previousPage('{{ $pageName }}')" wire:loading.attr="disabled" class="{{ $btnBase }} {{ $btnIdle }} w-8" aria-label="{{ __('pagination.previous') }}">
                        <x-aura::icon.caret direction="left" class="w-4 h-4" />
                    </button>
                @endif

                {{-- Page numbers (LengthAwarePaginator only): the first and last page, the current page with its
                     neighbours, and one "…" per gap ("1 2 3 … 11"), so a long list never spells out every page.
                     A gap of a single page shows that page instead of the dots. --}}
                @if ($isLengthAware && method_exists($paginator, 'lastPage'))
                    @php
                        $current = $paginator->currentPage();
                        $last = $paginator->lastPage();

                        if ($last <= 7) {
                            $pages = range(1, $last);
                        } else {
                            $pages = [1, $last, $current - 1, $current, $current + 1];

                            if ($current <= 3) {
                                array_push($pages, 2, 3);
                            }

                            if ($current >= $last - 2) {
                                array_push($pages, $last - 1, $last - 2);
                            }

                            $pages = array_values(array_unique(array_filter($pages, fn ($page) => $page >= 1 && $page <= $last)));
                            sort($pages);
                        }

                        $pageItems = [];
                        $previous = null;

                        foreach ($pages as $page) {
                            if ($previous !== null && $page - $previous === 2) {
                                $pageItems[] = $previous + 1;
                            } elseif ($previous !== null && $page - $previous > 2) {
                                $pageItems[] = '…';
                            }

                            $pageItems[] = $page;
                            $previous = $page;
                        }
                    @endphp

                    @foreach ($pageItems as $page)
                        @if (is_string($page))
                            <span class="{{ $btnBase }} min-w-8 px-1 text-gray-400 cursor-default dark:text-gray-500" aria-hidden="true">{{ $page }}</span>
                        @elseif ($page == $current)
                            <span class="{{ $btnBase }} {{ $btnCurrent }} min-w-8 px-2 tabular-nums" aria-current="page">{{ $page }}<span class="absolute bottom-0.5 left-1/2 w-4 -translate-x-1/2 aura-mark-x bg-primary-600 dark:bg-primary-400" aria-hidden="true"></span></span>
                        @else
                            <button type="button" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')" wire:loading.attr="disabled" class="{{ $btnBase }} {{ $btnIdle }} min-w-8 px-2 tabular-nums" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                {{ $page }}
                            </button>
                        @endif
                    @endforeach
                @endif

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $pageName }}')" wire:loading.attr="disabled" class="{{ $btnBase }} {{ $btnIdle }} w-8" aria-label="{{ __('pagination.next') }}">
                        <x-aura::icon.caret direction="right" class="w-4 h-4" />
                    </button>
                @else
                    <span class="{{ $btnBase }} {{ $btnDisabled }} w-8" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                        <x-aura::icon.caret direction="right" class="w-4 h-4" />
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
