<div class="w-full">
    <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
        {{-- @dump($this->notifications) --}}

        @foreach ($this->notifications as $notification)
        <li class="py-4">
            <div class="flex space-x-3">
                {{-- @dump( app('App\\Aura')->findResourceBySlug($notification->data['resource']['type'])) --}}
                <span class="flex items-center justify-center w-10 h-10 mr-2 text-white rounded-full bg-primary-400 dark:bg-primary-500 ring-8 ring-white dark:ring-gray-900">
                    <!-- Get the SVG of the Post Resource -->
                    {!! app('aura')::findResourceBySlug($notification->data['type'])->getIcon() !!}
                </span>

                <div class="flex-1 space-y-1">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('aura.' . $notification->data['type'] . '.edit', ['id' => $notification->data['id']]) }}" class="hover:underline"><h3 class="text-sm font-medium">{{ $notification->data['message'] ?? '' }}</h3></a>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $notification->type }}</p>
                </div>
            </div>
        </li>
        @endforeach
    </ul>
</div>
