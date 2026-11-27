<x-aura::fields.wrapper :field="$field" :showLabel="false">
    <h3 class="mt-4 mb-1 aura-section-title">
        {{ optional($field)['name'] }}
    </h3>
</x-aura::fields.wrapper>
