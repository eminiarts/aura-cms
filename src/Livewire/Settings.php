<?php

namespace Aura\Base\Livewire;

use Aura\Base\Resources\Option;
use Aura\Base\Traits\InputFields;
use Aura\Base\Traits\MediaFields;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Settings extends Component
{
    use InputFields;
    use MediaFields;

    public $form = [
        'fields' => [],
    ];

    public $model;

    // Listen for selectedAttachment
    protected $listeners = ['updateField' => 'updateField'];

    public static function getFields()
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'General',
                'slug' => 'tab-general',
                'global' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Appearance',
                'slug' => 'panel-DZzV',
            ],
            [
                'name' => 'Logo',
                'type' => 'Aura\\Base\\Fields\\Image',
                'slug' => 'logo',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Logo Darkmode',
                'type' => 'Aura\\Base\\Fields\\Image',
                'slug' => 'logo-darkmode',
                'style' => [
                    'width' => '50',
                ],
            ],
            // [
            //     'name' => 'Timezone',
            //     'type' => 'Aura\\Base\\Fields\\Text',
            //     'slug' => 'timezone',
            // ],

            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Sidebar',
                'slug' => 'panel-theme-sidebar',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Size',
                'type' => 'Aura\\Base\\Fields\\Radio',
                'options' => [
                    [
                        'key' => 'standard',
                        'value' => 'Standard',
                    ],
                    [
                        'key' => 'compact',
                        'value' => 'Compact',
                    ],
                ],
                'slug' => 'sidebar-size',
                'style' => [
                    'width' => '25',
                ],
            ],
            [
                'name' => 'Sidebar',
                'type' => 'Aura\\Base\\Fields\\Radio',
                'options' => [
                    [
                        'key' => 'primary',
                        'value' => 'Primary',
                    ],
                    [
                        'key' => 'light',
                        'value' => 'Light',
                    ],
                    [
                        'key' => 'dark',
                        'value' => 'Dark',
                    ],
                ],
                'slug' => 'sidebar-type',
                'style' => [
                    'width' => '25',
                ],
            ],
            [
                'name' => 'Darkmode',
                'live' => true,
                'type' => 'Aura\\Base\\Fields\\Radio',
                'options' => [
                    [
                        'key' => 'auto',
                        'value' => 'Auto',
                    ],
                    [
                        'key' => 'light',
                        'value' => 'Light',
                    ],
                    [
                        'key' => 'dark',
                        'value' => 'Dark',
                    ],
                ],
                'slug' => 'darkmode-type',
                'style' => [
                    'width' => '25',
                ],
            ],

            [
                'name' => 'Sidebar Darkmode',
                'type' => 'Aura\\Base\\Fields\\Radio',
                'options' => [
                    [
                        'key' => 'primary',
                        'value' => 'Primary',
                    ],
                    [
                        'key' => 'light',
                        'value' => 'Light',
                    ],
                    [
                        'key' => 'dark',
                        'value' => 'Dark',
                    ],
                ],
                'slug' => 'sidebar-darkmode-type',
                'style' => [
                    'width' => '25',
                ],
                'conditional_logic' => function ($model, $form) {
                    if ($form && $form['fields'] && $form['fields']['darkmode-type']) {
                        return $form['fields']['darkmode-type'] == 'auto';
                    }
                },
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Theme',
                'slug' => 'panel-theme-primary',
                'style' => [
                    'width' => '50',
                ],
            ],

            [
                'name' => 'Primary Color Palette',
                'type' => 'Aura\\Base\\Fields\\Select',
                'options' => [
                    'aura' => 'Aura',
                    'red' => 'Red',
                    'orange' => 'Orange',
                    'amber' => 'Amber',
                    'yellow' => 'Yellow',
                    'lime' => 'Lime',
                    'forest-green' => 'Forest Green',
                    'green' => 'Green',
                    'emerald' => 'Emerald',
                    'mountain-meadow' => 'Mountain Meadow',
                    'teal' => 'Teal',
                    'ocean-breeze' => 'Ocean Breeze',
                    'cyan' => 'Cyan',
                    'sky' => 'Sky',
                    'blue' => 'Blue',
                    'indigo' => 'Indigo',
                    'violet' => 'Violet',
                    'purple' => 'Purple',
                    'fuchsia' => 'Fuchsia',
                    'pink' => 'Pink',
                    'rose' => 'Rose',
                    'sandal' => 'Sandal',
                    'desert-sand' => 'Desert Sand',
                    'salmon' => 'Salmon',
                    'autumn-rust' => 'Autumn Rust',



                    'slate' => 'Slate',
                    'dark-slate' => 'Dark Slate',
                    'blackout' => 'Blackout',
                    'obsidian' => 'Obsidian',
                    'amethyst' => 'Amethyst',
                    'opal' => 'Opal',
                    'gray' => 'Gray',
                    'zinc' => 'Zinc',
                    'neutral' => 'Neutral',
                    'stone' => 'Stone',
                    'sandstone' => 'Sandstone',
                    'rose-quartz' => 'Rose Quartz',
                    'olive' => 'Olive',
                    'smaragd' => 'Smaragd',

                    'custom' => 'Custom',
                ],
                'slug' => 'color-palette',
                'live' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Group',
                'name' => 'Custom Colors',
                'slug' => 'tab-primary-colors-lightmode',
                'conditional_logic' => function ($model, $form) {
                    if ($form && $form['fields'] && $form['fields']['color-palette']) {
                        return $form['fields']['color-palette'] == 'custom';
                    }
                },
            ],
            [
                'name' => 'Primary 25',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-25',
            ],
            [
                'name' => 'Primary 50',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-50',
            ],
            [
                'name' => 'Primary 100',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-100',
            ],
            [
                'name' => 'Primary 200',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-200',
            ],
            [
                'name' => 'Primary 300',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-300',
            ],
            [
                'name' => 'Primary 400',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-400',
            ],
            [
                'name' => 'Primary 500',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-500',
            ],
            [
                'name' => 'Primary 600',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-600',
            ],
            [
                'name' => 'Primary 700',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-700',
            ],
            [
                'name' => 'Primary 800',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-800',
            ],
            [
                'name' => 'Primary 900',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-900',
            ],
            [
                'name' => 'Primary 950',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-950',
            ],

            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Theme',
                'slug' => 'panel-theme-gray',
                'style' => [
                    'width' => '50',
                ],
            ],

            [
                'name' => 'Gray Color Palette',
                'type' => 'Aura\\Base\\Fields\\Select',
                'options' => [
                    'slate' => 'Slate',
                    'dark-slate' => 'Dark Slate',
                    'blackout' => 'Blackout',
                    'obsidian' => 'Obsidian',
                    'amethyst' => 'Amethyst',
                    'opal' => 'Opal',
                    'gray' => 'Gray',
                    'zinc' => 'Zinc',
                    'neutral' => 'Neutral',
                    'stone' => 'Stone',
                    'sandstone' => 'Sandstone',
                    'rose-quartz' => 'Rose Quartz',
                    'olive' => 'Olive',
                    'smaragd' => 'Smaragd',
                    'custom' => 'Custom',
                ],
                'slug' => 'gray-color-palette',
                'live' => true,
            ],

            [
                'type' => 'Aura\\Base\\Fields\\Group',
                'name' => 'Custom Colors',
                'slug' => 'tab-gray-colors-custom-tab',
                'conditional_logic' => function ($model, $form) {
                    if ($form && $form['fields'] && $form['fields']['gray-color-palette']) {
                        return $form['fields']['gray-color-palette'] == 'custom';
                    }
                },
            ],

            [
                'name' => 'Gray 25',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-25',
            ],
            [
                'name' => 'Gray 50',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-50',
            ],
            [
                'name' => 'Gray 100',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-100',
            ],
            [
                'name' => 'Gray 200',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-200',
            ],
            [
                'name' => 'Gray 300',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-300',
            ],
            [
                'name' => 'Gray 400',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-400',
            ],
            [
                'name' => 'Gray 500',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-500',
            ],
            [
                'name' => 'Gray 600',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-600',
            ],
            [
                'name' => 'Gray 700',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-700',
            ],
            [
                'name' => 'Gray 800',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-800',
            ],
            [
                'name' => 'Gray 900',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-900',
            ],
            [
                'name' => 'Gray 950',
                'type' => 'Aura\\Base\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-950',
            ],

        ];
    }

    public function getFieldsForViewProperty()
    {
        $fields = collect($this->mappedFields());

        return $this->fieldsForView($fields);
    }

    public function getFieldsProperty()
    {
        return $this->inputFields()->mapWithKeys(function ($field) {
            return [$field['slug'] => $this->form['fields'][$field['slug']] ?? null];
        });
    }

    public function mount()
    {
        abort_unless(config('aura.features.settings'), 404);

        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $valueString = [
            'darkmode-type' => config('aura.theme.darkmode-type'),
            'sidebar-type' => config('aura.theme.sidebar-type'),
            'color-palette' => config('aura.theme.color-palette'),
            'gray-color-palette' => config('aura.theme.gray-color-palette'),
            'sidebar-size' => config('aura.theme.sidebar-size'),
            'sidebar-darkmode-type' => config('aura.theme.sidebar-darkmode-type'),
        ];

        if (config('aura.teams')) {
            $this->model = Option::firstOrCreate([
                'name' => 'team.'.auth()->user()->current_team_id.'.settings',
            ], [
                'value' => $valueString,
            ]);
        } else {
            $this->model = Option::firstOrCreate([
                'name' => 'settings',
            ], [
                'value' => $valueString,
            ]);
        }

        if (is_string($this->model->value)) {
            $this->form['fields'] = json_decode($this->model->value, true);
            // set default values of fields if not set to null
            $this->form['fields'] = $this->inputFields()->mapWithKeys(function ($field) {
                return [$field['slug'] => $this->form['fields'][$field['slug']] ?? null];
            })->toArray();
        } else {
            $this->form['fields'] = $this->inputFields()->mapWithKeys(function ($field) {
                return [$field['slug'] => $this->model->value[$field['slug']] ?? ''];
            })->toArray();

        }
    }

    public function render()
    {
        return view('aura::livewire.settings')->layout('aura::components.layout.app');
    }

    public function rules()
    {
        return Arr::dot([
            'form.fields' => $this->validationRules(),
        ]);
    }

    public function save()
    {
        app('aura')::updateOption('settings', $this->form['fields']);

        Cache::clear();

        return $this->notify(__('Successfully updated'));
    }
}
