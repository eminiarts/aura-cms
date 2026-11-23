@php
    $leftPanels = $recordLayout->panels(\Aura\Base\RecordLayout\RecordLayoutRegion::LeftSummary);
    $rightPanels = $recordLayout->panels(\Aura\Base\RecordLayout\RecordLayoutRegion::RightSidebar);
    $mainColumns = ($leftPanels !== [] ? 3 : 0) + ($rightPanels !== [] ? 3 : 0);
@endphp

<div
    class="grid grid-cols-1 mt-4 aura-layout-grid aura-record-layout lg:grid-cols-12"
    data-record-layout="{{ $inModal ? 'modal' : 'page' }}"
>
    @if ($leftPanels !== [])
        <aside class="flex flex-col aura-layout-grid lg:col-span-3" data-record-layout-region="left-summary">
            @include('aura::livewire.resource.record-layout.region', ['region' => \Aura\Base\RecordLayout\RecordLayoutRegion::LeftSummary])
        </aside>
    @endif

    <main class="flex flex-col aura-layout-grid {{ match ($mainColumns) { 6 => 'lg:col-span-6', 3 => 'lg:col-span-9', default => 'lg:col-span-12' } }}" data-record-layout-region="main-content">
        @include('aura::livewire.resource.record-layout.default-main')
        @include('aura::livewire.resource.record-layout.region', ['region' => \Aura\Base\RecordLayout\RecordLayoutRegion::MainContent])

        @if ($recordLayout->panels(\Aura\Base\RecordLayout\RecordLayoutRegion::ActivityTimeline) !== [])
            <section class="flex flex-col aura-layout-grid" data-record-layout-region="activity-timeline">
                @include('aura::livewire.resource.record-layout.region', ['region' => \Aura\Base\RecordLayout\RecordLayoutRegion::ActivityTimeline])
            </section>
        @endif
    </main>

    @if ($rightPanels !== [])
        <aside class="flex flex-col aura-layout-grid lg:col-span-3" data-record-layout-region="right-sidebar">
            @include('aura::livewire.resource.record-layout.region', ['region' => \Aura\Base\RecordLayout\RecordLayoutRegion::RightSidebar])
        </aside>
    @endif
</div>
