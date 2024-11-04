@php
$values = $taxonomy->pluck('name', 'id')->map(fn($name, $key) => ['value' => $key, 'label' => $name])->values()->toArray();
@endphp

<h3>{{ $taxonomy->title }}</h3>

<div
x-data="{
    multiple: true,
    value: $wire.entangle('form.terms.{{ $taxonomy->title }}'),
    options: {{ Js::from($values) }},
    init() {

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
class="mx-auto max-w-sm"
>
<select x-ref="select" :multiple="multiple" ></select>

@assets
    <link rel="stylesheet" href="/public/js/choicesJS/choices.min.css" />
    <script src="/public/js/choicesJS/choices.min.js"></script>
@endassets
</div>
