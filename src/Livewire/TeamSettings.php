<?php

namespace Aura\Base\Livewire;

use Aura\Base\Resources\Option;
use Aura\Base\Traits\InputFields;
use Aura\Base\Traits\MediaFields;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class TeamSettings extends Component
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
                'name' => 'Panel',
                'slug' => 'panel-DZzV',
            ],
            [
                'name' => 'App Logo',
                'type' => 'Aura\\Base\\Fields\\Image',
                'slug' => 'app-logo',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'App Logo (Darkmode)',
                'type' => 'Aura\\Base\\Fields\\Image',
                'slug' => 'app-logo-darkmode',
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
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Theme',
                'slug' => 'tab-theme',
                'global' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Sidebar settings',
                'slug' => 'panel-theme-sidebar',
                'style' => [
                    'width' => '100',
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
                    'width' => '33',
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
                    'width' => '33',
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
                    'width' => '33',
                ],
                'conditional_logic' => function ($model, $form) {
                    if ($form && $form['fields'] && $form['fields']['darkmode-type']) {
                        return $form['fields']['darkmode-type'] == 'auto';
                    }
                },
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Primary Colors',
                'slug' => 'panel-theme-primary',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Color Theme',
                'slug' => 'tab-primary-colors-theme',
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
                    'green' => 'Green',
                    'emerald' => 'Emerald',
                    'teal' => 'Teal',
                    'cyan' => 'Cyan',
                    'sky' => 'Sky',
                    'blue' => 'Blue',
                    'indigo' => 'Indigo',
                    'violet' => 'Violet',
                    'purple' => 'Purple',
                    'fuchsia' => 'Fuchsia',
                    'pink' => 'Pink',
                    'rose' => 'Rose',
                    'mountain-meadow' => 'Mountain Meadow',
                    'sandal' => 'Sandal',
                    'slate' => 'Slate',
                    'gray' => 'Gray',
                    'zinc' => 'Zinc',
                    'neutral' => 'Neutral',
                    'stone' => 'Stone',
                    'custom' => 'Custom',
                ],
                'slug' => 'color-palette',
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
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
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Gray Colors',
                'slug' => 'panel-theme-gray',
                'style' => [
                    'width' => '33',
                ],
            ],

            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Gray Colors',
                'slug' => 'tab-primary-gray-colors-theme',
            ],
            [
                'name' => 'Gray Color Palette',
                'type' => 'Aura\\Base\\Fields\\Select',
                'options' => [
                    'slate' => 'Slate',
                    'purple-slate' => 'Purple Slate',
                    'gray' => 'Gray',
                    'zinc' => 'Zinc',
                    'neutral' => 'Neutral',
                    'stone' => 'Stone',
                    'blue' => 'Blue',
                    'smaragd' => 'Smaragd',
                    'dark-slate' => 'Dark Slate',
                    'blackout' => 'Blackout',
                    'custom' => 'Custom',
                ],
                'slug' => 'gray-color-palette',
            ],

            [
                'type' => 'Aura\\Base\\Fields\\Tab',
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
        abort_unless(config('aura.features.theme_options'), 404);

        abort_unless(auth()->user()->resource->isSuperAdmin(), 403);

        $valueString = [
            'darkmode-type' => 'auto',
            'sidebar-type' => 'primary',
            'color-palette' => 'aura',
            'gray-color-palette' => 'slate',
        ];

        $this->model = Option::firstOrCreate([
            'name' => 'team.' . auth()->user()->current_team_id .'.team-settings',
        ], [
            'value' => $valueString,
        ]);


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
        return view('aura::livewire.team-settings')->layout('aura::components.layout.app');
    }

    public function rules()
    {
        return Arr::dot([
            'form.fields' => $this->validationRules(),
        ]);
    }

    public function save()
    {
        app('aura')::updateOption('team-settings', $this->form['fields']);

        Cache::clear();

        return $this->notify(__('Successfully updated'));
    }
}
