<div>
    @if(! app(\Aura\Base\Ai\AiStatus::class)->enabled())
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
            {{ __('AI features are disabled by AURA_AI_ENABLED.') }}
        </div>
    @endif

    {{-- A card in the shared look: section title and description, then one row per provider. --}}
    <div class="overflow-hidden bg-white rounded-xl ring-1 shadow-xs ring-gray-950/10 dark:bg-gray-800 dark:ring-white/10" style="border-radius: var(--aura-panel-radius, 0.75rem)">
        <div class="px-5 py-4 border-b border-gray-950/[0.06] dark:border-white/10">
            <h2 class="aura-section-title">{{ __('Configured providers') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{-- The path and the command are code chips; the sentence stays one translatable string. --}}
                {!! strtr(e(__('Provider settings come from :file and your environment. Run :command after changing them when configuration is cached.')), [
                    ':file' => '<code class="aura-code">config/ai.php</code>',
                    ':command' => '<code class="aura-code">php artisan config:cache</code>',
                ]) !!}
            </p>
        </div>

        @if($this->providers() === [])
            <div class="px-5 py-8 text-center">
                <h3 class="aura-section-title">{{ __('None configured') }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Add provider credentials to the application environment to enable AI features.') }}</p>
            </div>
        @else
            <div class="divide-y divide-gray-950/[0.06] dark:divide-white/10">
                @foreach($this->providers() as $provider)
                    <div class="flex flex-col gap-4 px-5 py-4 lg:flex-row lg:items-center lg:justify-between" wire:key="ai-provider-{{ $provider['name'] }}">
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
