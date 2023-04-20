<x-aura::dropdown width="w-96">
        <x-slot name="trigger">
            <x-aura::button.transparent>
                <x-aura::icon.dots class="w-5 h-5 mr-2" />
                Actions
            </x-aura::button.transparent>
        </x-slot>
        <x-slot name="content">
            <div class="px-2">
                @foreach($this->actions as $action => $options)

                @if(optional($options)['confirm'] === true)
                <div @click="stopPropagation($event)">
                    <x-aura::confirms-action 
                        wire:then="singleAction('{{ $action }}')" 
                        :confirmingPassword="true"
                        :title="optional($options)['confirm-title']" 
                        :content="optional($options)['confirm-content']"
                        :button="optional($options)['confirm-button']"
                        :button_class="optional($options)['confirm-button-class']"
                    >
                        <div class="p-2 cursor-pointer hover:bg-primary-100">
                            @if(is_array($options))
                            <div class="flex flex-col {{ $options['class'] ?? ''}}">
                                <div class="flex space-x-2 items-center">
                                    <div class="shrink-0">
                                        {!! $options['icon'] ?? '' !!}
                                        @if(optional($options)['icon-view'])
                                        @include($options['icon-view'])
                                        @endif
                                    </div>
                                    <strong class="font-semibold">{{ $options['label'] ?? '' }}
                                        @if(optional($options)['description'])
                                        <span
                                            class="text-sm text-gray-500 font-normal leading-tight inline-block">{{ $options['description'] ?? '' }}</span>
                                        @endif
                                    </strong>
                                </div>

                            </div>
                            @else
                            {{ $options }}
                            @endif
                        </div>
                    </x-aura::confirms-action>
                </div>

                @else
                <div wire:click="singleAction('{{ $action }}')" class="p-2 cursor-pointer hover:bg-primary-100">
                    @if(is_array($options))
                    <div class="flex flex-col {{ $options['class'] ?? ''}}">
                        <div class="flex space-x-2 items-center">
                            <div class="shrink-0">
                                {!! $options['icon'] ?? '' !!}
                                @if(optional($options)['icon-view'])
                                @include($options['icon-view'])
                                @endif
                            </div>
                            <strong class="font-semibold">{{ $options['label'] ?? '' }}
                                @if(optional($options)['description'])
                                <span
                                    class="text-sm text-gray-500 font-normal leading-tight inline-block">{{ $options['description'] ?? '' }}</span>
                                @endif
                            </strong>
                        </div>

                    </div>
                    @else
                    {{ $options }}
                    @endif
                </div>
                @endif
                @endforeach
            </div>
        </x-slot>
    </x-aura::dropdown>
