<div class="w-full">

    <ul role="list" class="divide-y divide-gray-200">
        {{-- @dump($this->notifications) --}}

        @forelse ($this->unreadNotifications as $notification)
        <li class="py-4">
            <div class="flex space-x-3">
                {{-- @dump( app('App\\Aura')->findResourceBySlug($notification->data['resource']['type'])) --}}
                <span class="flex justify-center items-center mr-2 w-10 h-10 text-white rounded-full ring-8 ring-white bg-primary-400">
                    <!-- Get the SVG of the Post Resource -->
                    @if(class_exists($notification->data['type']))
                        {!! app($notification->data['type'])->getIcon() !!}
                    @else
                        {{-- Default or fallback behavior here --}}
                        {{-- <i class="default-icon-class"></i> --}}
                    @endif
                </span>

                <div class="flex-1 space-y-1">


                    @if(optional($notification->data)['message'] && optional($notification->data)['id'] )
                    {{-- <p class="text-sm text-gray-500">{{ $notification->data['message'] }}</p> --}}
                    <div class="flex justify-between items-center">
                        <a href="{{ route('aura.resource.edit', ['slug' => app($notification->data['type'])->getType(), 'id' =>$notification->data['id']]) }}" class="hover:underline"><h3 class="text-sm font-medium">{{ $notification->data['message'] }}</h3></a>
                        <p class="text-sm text-gray-500">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                    @else
                    <p class="text-sm text-gray-500">{{ $notification->type }}</p>
                    @endif
                </div>
            </div>
        </li>
        @empty
        {{-- a good looking empty state with text: you're all set, everything read --}}
        <div class="flex flex-col justify-center items-center w-full h-full">

            <p class="mt-2 text-sm text-gray-400">{{ __('You\'re all set, everything read') }}</p>
        </div>

        @endforelse
    </ul>
</div>
