@if(count($this->actions))
<x-aura::dropdown width="w-72">
        <x-slot name="trigger">
            <x-aura::button.transparent>
                <x-aura::icon.dots class="mr-2 w-5 h-5" />
                {{ __('Actions') }}
            </x-aura::button.transparent>
        </x-slot>
        <x-slot name="content">
            <div class="px-0">
                @foreach($this->actions as $action => $options)

                @if(optional($options)['confirm'] === true)
                <div @click="stopPropagation($event)">
                    <x-aura::confirms-action
                        wire:then="singleAction('{{ $action }}')"
                        :title="__(optional($options)['confirm-title'])"
                        :content="__(optional($options)['confirm-content'])"
                        :button="__(optional($options)['confirm-button'])"
                        :button_class="optional($options)['confirm-button-class']"
                    >
                        <div class="px-4 py-2 text-sm cursor-pointer hover:bg-gray-50">
                            @if(is_array($options))
                            <div class="flex flex-col {{ $options['class'] ?? ''}}">
                                <div class="flex items-center space-x-2">
                                    <div class="shrink-0">
                                        {!! $options['icon'] ?? '' !!}
                                        @if(optional($options)['icon-view'])
                                        @include($options['icon-view'])
                                        @endif
                                    </div>
                                    <strong class="font-normal">
                                        {{ __($options['label'] ?? '') }}
                                        @if(optional($options)['description'])
                                        <span
                                            class="inline-block text-sm font-normal leading-tight text-gray-500">
                                            {{ __(($options['description'] ?? '')) }}
                                        </span>
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
                @elseif(optional($options)['onclick'])
                    <div
                        class="px-4 py-2 text-sm cursor-pointer hover:bg-gray-50"
                    @if( $options['onclick'] )
                        onclick="{!! $options['onclick'] !!}"
                    @endif
                    >
                        @if(is_array($options))
                        <div class="flex flex-col {{ $options['class'] ?? ''}}">
                            <div class="flex items-center space-x-2">
                                <div class="shrink-0">
                                    {!! $options['icon'] ?? '' !!}
                                    @if(optional($options)['icon-view'])
                                    @include($options['icon-view'])
                                    @endif
                                </div>
                                <strong class="font-normal">{{ $options['label'] ?? '' }}
                                    @if(optional($options)['description'])
                                    <span
                                        class="inline-block text-sm font-normal leading-tight text-gray-500">{{ $options['description'] ?? '' }}</span>
                                    @endif
                                </strong>
                            </div>

                        </div>
                        @else
                        {{ $options }}
                        @endif
                    </div>
                @else
                <div wire:click="singleAction('{{ $action }}')" class="px-4 py-2 text-sm cursor-pointer hover:bg-gray-50">
                    @if(is_array($options))
                    <div class="flex flex-col {{ $options['class'] ?? ''}}">
                        <div class="flex items-center space-x-2">
                            <div class="shrink-0">
                                {!! $options['icon'] ?? '' !!}
                                @if(optional($options)['icon-view'])
                                @include($options['icon-view'])
                                @endif
                            </div>
                            <strong class="font-normal">{{ $options['label'] ?? '' }}
                                @if(optional($options)['description'])
                                <span
                                    class="inline-block text-sm font-normal leading-tight text-gray-500">{{ $options['description'] ?? '' }}</span>
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
@endif