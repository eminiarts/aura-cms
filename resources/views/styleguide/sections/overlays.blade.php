<div class="flex flex-wrap gap-4 mt-6 aura-card">
    <x-aura::dialog>
        <x-aura::dialog.open>
            <x-aura::button.border>{{ __('Open modal') }}</x-aura::button.border>
        </x-aura::dialog.open>
        <x-aura::dialog.panel>
            <x-aura::dialog.title>{{ __('Delete post') }}</x-aura::dialog.title>
            <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('Are you sure you want to delete this post? This action cannot be undone.') }}</p>
            <x-slot:footer>
                <x-aura::dialog.close>
                    <x-aura::button.transparent>{{ __('Cancel') }}</x-aura::button.transparent>
                </x-aura::dialog.close>
                <x-aura::button.error>{{ __('Delete') }}</x-aura::button.error>
            </x-slot:footer>
        </x-aura::dialog.panel>
    </x-aura::dialog>

    <x-aura::dialog>
        <x-aura::dialog.open>
            <x-aura::button.border>{{ __('Open slide-over') }}</x-aura::button.border>
        </x-aura::dialog.open>
        <x-aura::dialog.slideover>
            <x-aura::dialog.title>{{ __('Edit details') }}</x-aura::dialog.title>
            <div class="space-y-5">
                <x-aura::input.text label="Title" placeholder="My first post" />
                <div>
                    <x-aura::fields.label label="Status" />
                    <x-aura::input.select name="styleguide-slideover-select" :options="['draft' => 'Draft', 'published' => 'Published']" selected="draft" />
                </div>
            </div>
        </x-aura::dialog.slideover>
    </x-aura::dialog>
</div>

<div class="grid gap-4 mt-4 lg:grid-cols-2">
    {{-- Static modal panel: same surface, close button, title and footer as dialog/panel. --}}
    <div class="flex justify-center items-center p-8 rounded-xl bg-gray-950/30 dark:bg-black/40" data-overlay-specimen="modal">
        <div class="overflow-hidden relative w-full max-w-md bg-white rounded-xl shadow-2xl ring-1 ring-gray-950/10 dark:bg-gray-800 dark:ring-white/10">
            <div class="absolute top-0 right-0 pt-4 pr-4">
                <span class="flex justify-center items-center w-8 h-8 text-gray-400 rounded-lg dark:text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                </span>
            </div>
            <div class="p-6">
                <x-aura::dialog.title>{{ __('Delete post') }}</x-aura::dialog.title>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('Are you sure you want to delete this post? This action cannot be undone.') }}</p>
                <x-aura::dialog.footer>
                    <x-aura::button.transparent>{{ __('Cancel') }}</x-aura::button.transparent>
                    <x-aura::button.error>{{ __('Delete') }}</x-aura::button.error>
                </x-aura::dialog.footer>
            </div>
        </div>
    </div>

    {{-- Static slide-over panel: same surface as dialog/slideover. --}}
    <div class="flex overflow-hidden justify-end h-80 rounded-xl bg-gray-950/30 dark:bg-black/40" data-overlay-specimen="slideover">
        <div class="flex flex-col justify-between w-72 h-full bg-white border-l shadow-2xl border-gray-950/5 ring-1 ring-gray-950/10 dark:bg-gray-800 dark:border-white/10 dark:ring-white/10">
            <div class="flex flex-col p-8">
                <x-aura::dialog.title>{{ __('Edit details') }}</x-aura::dialog.title>
                <x-aura::input.text placeholder="My first post" />
            </div>
            <div class="flex gap-3 justify-end px-6 py-4 border-t border-gray-950/5 bg-gray-50/50 dark:border-white/10 dark:bg-white/[0.02]">
                <x-aura::button.transparent>{{ __('Cancel') }}</x-aura::button.transparent>
                <x-aura::button>{{ __('Save') }}</x-aura::button>
            </div>
        </div>
    </div>
</div>
<p class="mt-3 text-xs text-gray-400 dark:text-gray-500">{{ __('Open overlays are teleported to <body>, so a forced dark mode on this page does not reach them. The static panels above follow it.') }}</p>
