{{-- resources/views/components/table/kanban-view.blade.php --}}
<div class="kanban-board flex overflow-x-auto p-4 space-x-4">
    @php
        $statuses = ['New', 'In Progress', 'Completed'];
    @endphp

    @foreach ($statuses as $status)
        <div class="kanban-column flex-shrink-0 w-80 bg-gray-100 rounded-lg p-4">
            <h3 class="font-bold mb-4">{{ $status }}</h3>
            <div class="space-y-4">
                @foreach ($rows->filter(function ($row) use ($status) {
                    return $row->status == $status;
                }) as $row)
                    <div class="bg-white p-4 rounded shadow">
                        <h4 class="font-semibold">{{ $row->subject }}</h4>
                        <p class="text-sm text-gray-600">{{ $row->from }}</p>
                        <p class="text-sm text-gray-600">Due: {{ $row->due_date }}</p>
                        <div class="mt-2 flex justify-between items-center">
                            <span class="text-xs bg-gray-200 rounded-full px-2 py-1">{{ $row->category }}</span>
                            @if($row->assigned_to)
                                <span class="text-xs text-gray-600">Assigned: {{ $row->assigned_to }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>