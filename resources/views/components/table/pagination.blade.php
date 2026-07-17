@php
    // Defensive: works with LengthAwarePaginator and simple paginators.
    $isLengthAware = method_exists($paginator, 'total');
    $pageName = method_exists($paginator, 'getPageName') ? $paginator->getPageName() : 'page';

    $btnBase = 'inline-flex items-center justify-center h-8 text-sm font-medium rounded-lg transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 disabled:opacity-50';
    $btnIdle = 'text-gray-600 hover:bg-gray-950/5 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white';
    $btnCurrent = 'bg-primary-50 text-primary-700 ring-1 ring-primary-200 dark:bg-primary-500/15 dark:text-primary-300 dark:ring-primary-500/20';
    $btnDisabled = 'text-gray-300 dark:text-gray-600 cursor-default';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex justify-between items-center">

        {{-- Mobile --}}
        <div class="flex flex-1 gap-2 justify-between items-center sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="{{ $btnBase }} {{ $btnDisabled }} px-3 bg-white ring-1 ring-gray-950/10 dark:bg-gray-800 dark:ring-white/10">{!! __('pagination.previous') !!}</span>
            @else
                <button type="button" wire:click="previousPage('{{ $pageName }}')" wire:loading.attr="disabled" class="{{ $btnBase }} px-3 text-gray-700 bg-white ring-1 shadow-xs ring-gray-950/10 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:ring-white/10 dark:hover:bg-gray-700/50">
                    {!! __('pagination.previous') !!}
                </button>
            @endif

            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage('{{ $pageName }}')" wire:loading.attr="disabled" class="{{ $btnBase }} px-3 text-gray-700 bg-white ring-1 shadow-xs ring-gray-950/10 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:ring-white/10 dark:hover:bg-gray-700/50">
                    {!! __('pagination.next') !!}
                </button>
            @else
                <span class="{{ $btnBase }} {{ $btnDisabled }} px-3 bg-white ring-1 ring-gray-950/10 dark:bg-gray-800 dark:ring-white/10">{!! __('pagination.next') !!}</span>
            @endif
        </div>

        {{-- Desktop --}}
        <div class="hidden sm:flex sm:flex-1 sm:justify-between sm:items-center">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
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
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 3.5 5.5 8 10 12.5" /></svg>
                    </span>
                @else
                    <button type="button" wire:click="previousPage('{{ $pageName }}')" wire:loading.attr="disabled" class="{{ $btnBase }} {{ $btnIdle }} w-8" aria-label="{{ __('pagination.previous') }}">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 3.5 5.5 8 10 12.5" /></svg>
                    </button>
                @endif

                {{-- Page numbers (LengthAwarePaginator only) --}}
                @if (isset($elements))
                    @foreach ($elements as $element)
                        {{-- "Three Dots" separator --}}
                        @if (is_string($element))
                            <span class="{{ $btnBase }} {{ $btnDisabled }} min-w-8 px-1" aria-disabled="true">{{ $element }}</span>
                        @endif

                        {{-- Array of page links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span class="{{ $btnBase }} {{ $btnCurrent }} min-w-8 px-2" aria-current="page">{{ $page }}</span>
                                @else
                                    <button type="button" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')" wire:loading.attr="disabled" class="{{ $btnBase }} {{ $btnIdle }} min-w-8 px-2" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </button>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                @endif

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $pageName }}')" wire:loading.attr="disabled" class="{{ $btnBase }} {{ $btnIdle }} w-8" aria-label="{{ __('pagination.next') }}">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3.5 10.5 8 6 12.5" /></svg>
                    </button>
                @else
                    <span class="{{ $btnBase }} {{ $btnDisabled }} w-8" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3.5 10.5 8 6 12.5" /></svg>
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
