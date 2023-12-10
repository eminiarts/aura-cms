@if(count($this->actions))
<x-aura::dropdown width="w-96">
        <x-slot name="trigger">
            <x-aura::button.transparent>
                <x-aura::icon.dots class="mr-2 w-5 h-5" />
                {{ __('Actions') }}
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
                        :title="__(optional($options)['confirm-title'])"
                        :content="__(optional($options)['confirm-content'])"
                        :button="__(optional($options)['confirm-button'])"
                        :button_class="optional($options)['confirm-button-class']"
                    >
                        <div class="p-2 cursor-pointer hover:bg-primary-100">
                            @if(is_array($options))
                            <div class="flex flex-col {{ $options['class'] ?? ''}}">
                                <div class="flex items-center space-x-2">
                                    <div class="shrink-0">
                                        {!! $options['icon'] ?? '' !!}
                                        @if(optional($options)['icon-view'])
                                        @include($options['icon-view'])
                                        @endif
                                    </div>
                                    <strong class="font-semibold">
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

                @else
                <div wire:click="singleAction('{{ $action }}')" class="p-2 cursor-pointer hover:bg-primary-100">
                    @if(is_array($options))
                    <div class="flex flex-col {{ $options['class'] ?? ''}}">
                        <div class="flex items-center space-x-2">
                            <div class="shrink-0">
                                {!! $options['icon'] ?? '' !!}
                                @if(optional($options)['icon-view'])
                                @include($options['icon-view'])
                                @endif
                            </div>
                            <strong class="font-semibold">{{ $options['label'] ?? '' }}
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
