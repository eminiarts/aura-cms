{{-- resources/views/components/table/kanban-view.blade.php --}}
<div class="kanban-board flex overflow-x-auto p-4 space-x-4">
    @php
        $statuses = ['New', 'In Progress', 'Completed'];
    @endphp

    @foreach ($statuses as $status)
        <div class="kanban-column flex-shrink-0 w-80 bg-gray-950/[0.04] dark:bg-white/5 rounded-xl p-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">{{ $status }}</h3>
            <div class="space-y-3">
                @foreach ($rows->filter(function ($row) use ($status) {
                    return $row->status == $status;
                }) as $row)
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10 shadow-xs">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $row->subject }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $row->from }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Due: {{ $row->due_date }}</p>
                        <div class="mt-2 flex justify-between items-center">
                            <span class="text-xs text-gray-700 dark:text-gray-300 bg-gray-950/5 dark:bg-white/10 rounded-full px-2 py-1">{{ $row->category }}</span>
                            @if($row->assigned_to)
                                <span class="text-xs text-gray-600 dark:text-gray-400">Assigned: {{ $row->assigned_to }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
