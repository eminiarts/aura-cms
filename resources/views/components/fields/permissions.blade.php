@aware(['model'])
@php
    $name = 'form.fields.'  . optional($field)['slug'];

    $abilities = \Aura\Base\Fields\Permissions::ABILITIES;

    $features = collect(\Aura\Base\Facades\Aura::permissions())
        ->map(fn ($permission, $key) => [...$permission, 'slug' => $key])
        ->groupBy('group');

    $rows = \Aura\Base\Fields\Permissions::matrix(app($field['resource'])->get()->map(fn ($item) => [
        'name' => $item->fields['name'],
        'description' => $item->fields['description'],
        'slug' => $item->fields['slug'],
        'group' => $item->group,
    ]));

    $hasOther = collect($rows)->contains(fn ($row) => $row['other'] !== []);

    $slugs = fn ($items) => collect($items)->filter()->pluck('slug')->values()->all();

    // Slug lists for the Alpine toggles: every permission per group, the ones
    // bulk selection may switch on (all but "scope"), and each ability column.
    $featureGroups = $features->mapWithKeys(fn ($items, $group) => ['feature:'.$group => $slugs($items)]);
    $groups = collect($rows)->mapWithKeys(fn ($row, $group) => ['resource:'.$group => $slugs([...array_values($row['cells']), ...$row['other']])])->merge($featureGroups);
    $bulk = collect($rows)->mapWithKeys(fn ($row, $group) => ['resource:'.$group => $slugs([...array_values(array_diff_key($row['cells'], ['scope' => null])), ...$row['other']])])->merge($featureGroups);
    $columns = collect($abilities)->map(fn ($label, $ability) => $slugs(array_column(array_column($rows, 'cells'), $ability)));

    $checkboxClass = 'form-checkbox block size-4 shrink-0 rounded border-0 bg-white text-primary-600 shadow-xs ring-1 ring-gray-950/15 transition duration-150 cursor-pointer hover:ring-gray-950/25 checked:bg-primary-600 checked:ring-primary-600 checked:hover:bg-primary-700 checked:hover:ring-primary-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:bg-gray-800 dark:ring-white/20 dark:hover:ring-white/30 dark:checked:bg-primary-600 dark:checked:ring-primary-600 dark:focus-visible:ring-offset-gray-900';
@endphp

<x-aura::fields.conditions :field="$field" :model="$model">
<div class="w-full" x-data="{
    groups: @js($groups),
    bulk: @js($bulk),
    columns: @js($columns),
    field: @entangle($name),
    init(){
        if (this.field === null || Array.isArray(this.field)) {
            this.field = {};
        }

        // Set the initial value of the field to false, if not set
        Object.values(this.groups).flat().forEach(slug => {
            if (this.field[slug] === undefined) {
                this.field[slug] = false;
            }
        });
    },
    allOn(slugs){
        return slugs.length > 0 && slugs.every(slug => Boolean(this.field[slug]));
    },
    setAll(slugs, value){
        slugs.forEach(slug => this.field[slug] = value);
    },
    toggleColumn(ability){
        this.setAll(this.columns[ability], ! this.allOn(this.columns[ability]));
    },
    toggleGroup(group){
        if (this.allOn(this.bulk[group])) {
            this.setAll(this.groups[group], false);
        } else {
            this.setAll(this.bulk[group], true);
        }
    },
    selectedCount(group){
        return this.bulk[group].filter(slug => Boolean(this.field[slug])).length;
    },
    selectAllGroups(){
        this.setAll(Object.values(this.bulk).flat(), true);
    },
    removeAllGroups(){
        this.setAll(Object.values(this.groups).flat(), false);
    },
}">
<x-aura::fields.wrapper :field="$field">
    <div class="flex items-center justify-end gap-1 mb-3">
        <button type="button" @click="removeAllGroups()" class="px-3 py-1.5 text-xs font-medium text-gray-500 transition-colors rounded-md hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100">
            {{ __('Clear all') }}
        </button>
        <button type="button" @click="selectAllGroups()" class="px-3 py-1.5 text-xs font-medium transition-colors rounded-md text-primary-700 bg-primary-50 hover:bg-primary-100 dark:text-primary-300 dark:bg-primary-500/10 dark:hover:bg-primary-500/20">
            {{ __('Select all permissions') }}
        </button>
    </div>

    @if($features->isNotEmpty())
        <div class="mb-4 overflow-x-auto rounded-lg border border-gray-500/20 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50/80 dark:bg-white/[0.03] border-b border-gray-200/80 dark:border-white/10">
                        <th scope="col" colspan="2" class="px-4 py-2.5 text-left">
                            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ __('Features') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach($features as $group => $items)
                        <tr class="transition-colors hover:bg-gray-50/80 dark:hover:bg-white/[0.04]">
                            <th scope="row" class="px-4 py-1 text-left font-normal">
                                <button type="button" @click="toggleGroup(@js('feature:'.$group))" title="{{ __('Toggle all :group permissions', ['group' => __($group)]) }}" aria-label="{{ __('Toggle all :group permissions', ['group' => __($group)]) }}" class="flex items-baseline gap-2 py-1 text-left whitespace-nowrap rounded-md transition-colors text-gray-900 hover:text-primary-600 dark:text-gray-100 dark:hover:text-primary-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                                    <span class="font-medium">{{ __($group) }}</span>
                                    <span class="text-[11px] font-medium text-gray-400 dark:text-gray-500">
                                        <span x-text="selectedCount(@js('feature:'.$group))"></span>/{{ count($items) }}
                                    </span>
                                </button>
                            </th>
                            <td class="px-4 py-1.5">
                                <div class="flex flex-wrap gap-x-4 gap-y-2">
                                    @foreach($items as $item)
                                        <label for="permissions_{{ $item['slug'] }}" title="{{ __($item['description'] ?? '') }}" class="inline-flex items-center gap-2 py-1 cursor-pointer text-gray-700 dark:text-gray-300">
                                            <input x-model="field[@js($item['slug'])]" type="checkbox" id="permissions_{{ $item['slug'] }}" class="{{ $checkboxClass }}">
                                            <span>{{ __($item['name']) }}</span>
                                            <span class="text-xs font-mono text-gray-400 dark:text-gray-500">{{ $item['slug'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="overflow-x-auto rounded-lg border border-gray-500/20 dark:border-gray-700">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50/80 dark:bg-white/[0.03] border-b border-gray-200/80 dark:border-white/10">
                    <th scope="col" class="px-4 py-2.5 text-left">
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ __('Resource') }}</span>
                    </th>
                    @foreach($abilities as $ability => $label)
                        <th scope="col" class="px-2 py-2.5 text-center {{ $ability === 'scope' ? 'border-l border-gray-200/80 dark:border-white/10' : '' }}">
                            <button type="button" @click="toggleColumn('{{ $ability }}')" title="{{ $ability === 'scope' ? __('Limit to records the user owns. Toggle for all resources.') : __('Toggle for all resources') }}" aria-label="{{ __('Toggle :ability for all resources', ['ability' => __($label)]) }}" class="px-1.5 py-1 text-[11px] font-semibold uppercase tracking-wide whitespace-nowrap rounded-md transition-colors text-gray-500 hover:bg-gray-100 hover:text-primary-600 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-primary-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                                {{ __($label) }}
                            </button>
                        </th>
                    @endforeach
                    @if($hasOther)
                        <th scope="col" class="px-4 py-2.5 text-left border-l border-gray-200/80 dark:border-white/10">
                            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ __('Other') }}</span>
                        </th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @foreach($rows as $group => $row)
                    <tr class="transition-colors hover:bg-gray-50/80 dark:hover:bg-white/[0.04]">
                        <th scope="row" class="px-4 py-1 text-left font-normal">
                            <button type="button" @click="toggleGroup(@js('resource:'.$group))" title="{{ __('Toggle all :group permissions', ['group' => $group]) }}" aria-label="{{ __('Toggle all :group permissions', ['group' => $group]) }}" class="flex items-baseline gap-2 py-1 text-left whitespace-nowrap rounded-md transition-colors text-gray-900 hover:text-primary-600 dark:text-gray-100 dark:hover:text-primary-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                                <span class="font-medium">{{ $group }}</span>
                                <span class="text-[11px] font-medium text-gray-400 dark:text-gray-500">
                                    <span x-text="selectedCount(@js('resource:'.$group))"></span>/{{ count($bulk['resource:'.$group]) }}
                                </span>
                            </button>
                        </th>
                        @foreach($abilities as $ability => $label)
                            <td class="p-0 text-center {{ $ability === 'scope' ? 'border-l border-gray-200/80 dark:border-white/10' : '' }}">
                                @if($item = $row['cells'][$ability])
                                    <label for="permissions_{{ $item['slug'] }}" title="{{ $item['description'] ?: $item['name'] }}" class="flex justify-center px-2 py-2.5 cursor-pointer">
                                        <input x-model="field[@js($item['slug'])]" type="checkbox" id="permissions_{{ $item['slug'] }}" aria-label="{{ $item['name'] }}" class="{{ $checkboxClass }}">
                                    </label>
                                @endif
                            </td>
                        @endforeach
                        @if($hasOther)
                            <td class="px-4 py-1.5 border-l border-gray-200/80 dark:border-white/10">
                                @foreach($row['other'] as $item)
                                    <label for="permissions_{{ $item['slug'] }}" title="{{ $item['description'] }}" class="flex items-center gap-2 py-1 cursor-pointer whitespace-nowrap">
                                        <input x-model="field[@js($item['slug'])]" type="checkbox" id="permissions_{{ $item['slug'] }}" class="{{ $checkboxClass }}">
                                        <span class="text-gray-700 dark:text-gray-200">{{ $item['name'] }}</span>
                                    </label>
                                @endforeach
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-aura::fields.wrapper>
</div>
</x-aura::fields.conditions>
