@php
$values = $taxonomy->pluck('name', 'id')->map(fn($name, $key) => ['value' => $key, 'label' => $name])->values()->toArray();
@endphp

<h3>{{ $taxonomy->title }}</h3>

<div
x-aura::data="{
    multiple: true,
    value: $wire.entangle('post.terms.{{ $taxonomy->title }}').defer,
    options: {{ Js::from($values) }},
    init() {
        
        console.log(this.options);
        
        this.$nextTick(() => {
            let choices = new Choices(this.$refs.select, {
                addItems: true,
                removeItems: true,
    {{-- removeItemButton: true, --}}
    editItems: true,
            })
            
            let refreshChoices = () => {
                let selection = this.multiple ? this.value : [this.value]
                
                choices.clearStore()
                choices.setChoices(this.options.map(({ value, label }) => ({
                    value,
                    label,
                    selected: selection.includes(value),
                })))
            }
            
            refreshChoices()
            
            this.$refs.select.addEventListener('change', () => {
                this.value = choices.getValue(true)
            })
            
            this.$watch('value', () => refreshChoices())
            this.$watch('options', () => refreshChoices())
        })
    }
}"
class="max-aura::w-sm mx-aura::auto"
>
<select x-aura::ref="select" :multiple="multiple" ></select>

@once
@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
@endpush
@endonce 
</div>