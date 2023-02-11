@props(['id' => null, 'maxWidth' => null])

<x-aura::jet-modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
    <div class="px-aura::6 py-4">
        <div class="text-lg">
            {{ $title }}
        </div>

        <div class="mt-4">
            {{ $content }}
        </div>
    </div>

    <div class="flex flex-row justify-end px-aura::6 py-4 bg-gray-100 text-right">
        {{ $footer }}
    </div>
</x-aura::jet-modal>
