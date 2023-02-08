<div class="w-full">

    <ul role="list" class="divide-y divide-gray-200">
        {{-- @dump($this->notifications) --}}

        @forelse ($this->unreadNotifications as $notification)
        <li class="py-4">
            <div class="flex space-x-3">
                {{-- @dump( app('App\\Aura')->findResourceBySlug($notification->data['post']['type'])) --}}
                <span class="flex items-center justify-center w-10 h-10 mr-2 text-white rounded-full bg-primary-400 ring-8 ring-white">
                    <!-- Get the SVG of the Post Resource -->
                    {!! app('App\\Aura')->findResourceBySlug($notification->data['post']['type'])->getIcon() !!}
                </span>

                <div class="flex-1 space-y-1">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('post.edit', ['slug' => $notification->data['post']['type'], 'id' =>$notification->data['post']['id']]) }}" class="hover:underline"><h3 class="text-sm font-medium">{{ $notification->data['message'] }}</h3></a>
                        <p class="text-sm text-gray-500">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                    <p class="text-sm text-gray-500">{{ $notification->type }}</p>
                </div>
            </div>
        </li>
        @empty
        {{-- a good looking empty state with text: you're all set, everything read --}}
        <div class="flex flex-col items-center justify-center w-full h-full">

            <p class="mt-2 text-sm text-gray-400">You're all set, everything read</p>
        </div>

        @endforelse
    </ul>
</div>
