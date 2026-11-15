<div>
    @if(! app(\Aura\Base\Ai\AiStatus::class)->enabled())
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
            {{ __('AI features are disabled by AURA_AI_ENABLED.') }}
        </div>
    @endif

    @php
        // The path and the command are code chips; the sentence stays one translatable string.
        $setupNote = strtr(e(__('Provider settings come from :file and your environment. Run :command after changing them when configuration is cached.')), [
            ':file' => '<code class="aura-code">config/ai.php</code>',
            ':command' => '<code class="aura-code">php artisan config:cache</code>',
        ]);
    @endphp

    {{-- A card in the shared look, its content inset like a form card's (panel padding plus 8px): without providers it leads with that fact and what to
         do, left-aligned like a table's empty state, the setup details as quieter secondary text. With
         providers: section title and the setup note, then one row per provider. --}}
    <div class="overflow-hidden bg-white rounded-panel ring-1 ring-aura-edge dark:bg-gray-800">
        @if($this->providers() === [])
            {{-- Without providers the card speaks to whoever opens it: what is missing and who sets it up. The
                 developer setup (environment, config cache) waits in a closed disclosure. --}}
            <div class="p-5 sm:p-[calc(var(--aura-panel-padding,1.25rem)+0.5rem)]" data-ai-providers-empty>
                <h2 class="aura-section-title">{{ __('AI features are not set up') }}</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ __('Ask an administrator to connect a provider.') }}</p>
                <details class="mt-4 group" data-ai-setup>
                    <summary class="inline-flex gap-1 items-center text-sm font-medium text-gray-700 rounded-sm cursor-pointer select-none list-none [&::-webkit-details-marker]:hidden hover:text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-gray-300 dark:hover:text-white">
                        <x-aura::icon.caret direction="right" class="size-4 text-gray-500 transition-transform duration-150 group-open:rotate-90 dark:text-gray-400" />
                        {{ __('Developer setup') }}
                    </summary>
                    <div class="mt-2 pl-5">
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('Add provider credentials to the application environment to enable AI features.') }}</p>
                        <p class="aura-code-note mt-2 text-[13px] leading-5 text-gray-500 dark:text-gray-400">{!! $setupNote !!}</p>
                    </div>
                </details>
            </div>
        @else
            <div class="px-5 sm:px-[calc(var(--aura-panel-padding,1.25rem)+0.5rem)] py-4 border-b border-gray-950/[0.06] dark:border-white/10">
                <h2 class="aura-section-title">{{ __('Configured providers') }}</h2>
                <p class="aura-code-note mt-1 text-sm text-gray-500 dark:text-gray-400">{!! $setupNote !!}</p>
            </div>
            <div class="divide-y divide-gray-950/[0.06] dark:divide-white/10">
                @foreach($this->providers() as $provider)
                    <div class="flex flex-col gap-4 px-5 sm:px-[calc(var(--aura-panel-padding,1.25rem)+0.5rem)] py-4 lg:flex-row lg:items-center lg:justify-between" wire:key="ai-provider-{{ $provider['name'] }}">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $provider['label'] }}</p>
                                @if($provider['default'])
                                    <span class="aura-tag aura-tag-status bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300">{{ __('Default') }}</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('Driver: :driver', ['driver' => $provider['driver']]) }}
                                @if($provider['endpoint'])
                                    <span aria-hidden="true"> · </span>{{ $provider['endpoint'] }}
                                @endif
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Credentials set: :value', ['value' => $provider['credentials'] ? __('Yes') : __('No')]) }}
                            </p>
                            @if(isset($results[$provider['name']]))
                                <p @class([
                                    'mt-2 text-sm',
                                    'text-green-700 dark:text-green-300' => $results[$provider['name']]['successful'],
                                    'text-red-700 dark:text-red-300' => ! $results[$provider['name']]['successful'],
                                ])>
                                    {{ $results[$provider['name']]['message'] }}
                                    @if($results[$provider['name']]['duration_ms'] !== null)
                                        {{ __('(:duration ms)', ['duration' => $results[$provider['name']]['duration_ms']]) }}
                                    @endif
                                </p>
                            @endif
                        </div>

                        <x-aura::button.border
                            type="button"
                            wire:click="testProvider('{{ $provider['name'] }}')"
                            wire:loading.attr="disabled"
                            wire:target="testProvider('{{ $provider['name'] }}')"
                        >
                            <span wire:loading wire:target="testProvider('{{ $provider['name'] }}')" class="mr-2">
                                <x-aura::icon.loading />
                            </span>
                            {{ __('Test connection') }}
                        </x-aura::button.border>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
