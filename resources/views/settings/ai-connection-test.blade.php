<div class="px-2 pb-2 w-full">
    <div class="flex items-center gap-3">
        <x-aura::button type="button" wire:click="testAiConnection" wire:loading.attr="disabled" wire:target="testAiConnection">
            <span wire:loading wire:target="testAiConnection">
                <x-aura::icon.loading class="mr-2 w-4 h-4" />
            </span>
            {{ __('Test connection') }}
        </x-aura::button>

        @if($this->secretConfigured['ai-api-key'] ?? false)
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('An encrypted API key is configured.') }}</span>
        @endif
    </div>

    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('Save settings before testing a changed provider, endpoint, model, or API key.') }}</p>

    @if($this->aiConnectionStatus)
        <div @class([
            'p-3 mt-4 text-sm rounded-lg border',
            'text-green-800 bg-green-50 border-green-200 dark:text-green-200 dark:bg-green-950/40 dark:border-green-900' => $this->aiConnectionStatus['successful'],
            'text-red-800 bg-red-50 border-red-200 dark:text-red-200 dark:bg-red-950/40 dark:border-red-900' => ! $this->aiConnectionStatus['successful'],
        ])>
            <p>{{ $this->aiConnectionStatus['message'] }}</p>
            @if($this->aiConnectionStatus['successful'] && filled($this->aiConnectionStatus['response']))
                <p class="mt-1 text-xs opacity-75">{{ __('Provider response: :response', ['response' => $this->aiConnectionStatus['response']]) }}</p>
            @endif
        </div>
    @endif
</div>
