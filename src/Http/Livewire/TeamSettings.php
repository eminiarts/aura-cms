<?php

namespace Eminiarts\Aura\Http\Livewire;

use Eminiarts\Aura\Resources\Option;
use Eminiarts\Aura\Traits\InputFields;
use Eminiarts\Aura\Traits\MediaFields;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class TeamSettings extends Component
{
    use InputFields;
    use MediaFields;

    public $model;

    public $post = [
        'fields' => [],
    ];

    // Listen for selectedAttachment
    protected $listeners = ['updateField' => 'updateField'];

    public static function getFields()
    {
        return [
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'General',
                'slug' => 'tab-general',
                'global' => true,
            ],
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'name' => 'Panel',
                'slug' => 'panel-DZzV',
            ],
            [
                'name' => 'App Logo',
                'type' => 'Eminiarts\\Aura\\Fields\\Image',
                'slug' => 'app-logo',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'App Logo (Darkmode)',
                'type' => 'Eminiarts\\Aura\\Fields\\Image',
                'slug' => 'app-logo-darkmode',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Timezone',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'slug' => 'timezone1',
            ],
            [
                'name' => 'Timezone',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'slug' => 'timezone',
            ],

            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Theme',
                'slug' => 'tab-theme',
                'global' => true,
            ],
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'name' => 'Sidebar settings',
                'slug' => 'panel-theme-sidebar',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Sidebar',
                'type' => 'Eminiarts\\Aura\\Fields\\Radio',
                'options' => [
                    'primary' => 'Primary',
                    'light' => 'Light',
                    'dark' => 'Dark',
                ],
                'slug' => 'sidebar-type',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'name' => 'Darkmode',
                'type' => 'Eminiarts\\Aura\\Fields\\Radio',
                'options' => [
                    'auto' => 'Auto',
                    'light' => 'Light',
                    'dark' => 'Dark',
                ],
                'slug' => 'darkmode-type',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'name' => 'Primary Colors',
                'slug' => 'panel-theme-primary',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'name' => 'Primary Color Palette',
                'type' => 'Eminiarts\\Aura\\Fields\\Select',
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
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Custom Colors',
                'slug' => 'tab-primary-colors-lightmode',
                'conditional_logic' => [
                    [
                        'field' => 'color-palette',
                        'operator' => '==',
                        'value' => 'custom',
                    ],
                ],
            ],
            [
                'name' => 'Primary 25',
                'type' => 'Eminiarts\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-25',
            ],
            [
                'name' => 'Primary 50',
                'type' => 'Eminiarts\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-50',
            ],
            [
                'name' => 'Primary 100',
                'type' => 'Eminiarts\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-100',
            ],
            [
                'name' => 'Primary 200',
                'type' => 'Eminiarts\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-200',
            ],
            [
                'name' => 'Primary 300',
                'type' => 'Eminiarts\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-300',
            ],
            [
                'name' => 'Primary 400',
                'type' => 'Eminiarts\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-400',
            ],
            [
                'name' => 'Primary 500',
                'type' => 'Eminiarts\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-500',
            ],
            [
                'name' => 'Primary 600',
                'type' => 'Eminiarts\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-600',
            ],
            [
                'name' => 'Primary 700',
                'type' => 'Eminiarts\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-700',
            ],
            [
                'name' => 'Primary 800',
                'type' => 'Eminiarts\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-800',
            ],
            [
                'name' => 'Primary 900',
                'type' => 'Eminiarts\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-900',
            ],

            [
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'name' => 'Gray Colors',
                'slug' => 'panel-theme-gray',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'name' => 'Gray Color Palette',
                'type' => 'Eminiarts\\Aura\\Fields\\Select',
                'options' => [
                    'slate' => 'Slate',
                    'purple-slate' => 'Purple Slate',
                    'gray' => 'Gray',
                    'zinc' => 'Zinc',
                    'neutral' => 'Neutral',
                    'stone' => 'Stone',
                    'blue' => 'Blue',
                    'smaragd' => 'Smaragd',
                    'custom' => 'Custom',
                ],
                'slug' => 'gray-color-palette',
            ],
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Custom Colors',
                'slug' => 'tab-primary-colors-lightmode',
                'conditional_logic' => [
                    [
                        'field' => 'gray-color-palette',
                        'operator' => '==',
                        'value' => 'custom',
                    ],
                ],
            ],
            [
                'name' => 'Gray 25',
                'type' => 'Eminiarts\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-25',
            ],
            [
                'name' => 'Gray 50',
                'type' => 'Eminiarts\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-50',
            ],
            [
                'name' => 'Gray 100',
                'type' => 'Eminiarts\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-100',
            ],
            [
                'name' => 'Gray 200',
                'type' => 'Eminiarts\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-200',
            ],
            [
                'name' => 'Gray 300',
                'type' => 'Eminiarts\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-300',
            ],
            [
                'name' => 'Gray 400',
                'type' => 'Eminiarts\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-400',
            ],
            [
                'name' => 'Gray 500',
                'type' => 'Eminiarts\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-500',
            ],
            [
                'name' => 'Gray 600',
                'type' => 'Eminiarts\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-600',
            ],
            [
                'name' => 'Gray 700',
                'type' => 'Eminiarts\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-700',
            ],
            [
                'name' => 'Gray 800',
                'type' => 'Eminiarts\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-800',
            ],
            [
                'name' => 'Gray 900',
                'type' => 'Eminiarts\\Aura\\Fields\\Color',
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
            return [$field['slug'] => $this->post['fields'][$field['slug']] ?? null];
        });
    }

    public function mount()
    {
        abort_unless(config('aura.features.theme_options'), 404);

        abort_unless(auth()->user()->resource->isSuperAdmin(), 403);

        // dd('no abort');

        $valueString = [
            'darkmode-type' => 'auto',
            'sidebar-type' => 'primary',
            'color-palette' => 'aura',
            'gray-color-palette' => 'slate',
        ];

        $this->model = Option::firstOrCreate([
            'name' => 'team-settings',
        ], [
            'value' => $valueString,
        ]);

        if (is_string($this->model->value)) {
            $this->post['fields'] = json_decode($this->model->value, true);
            // set default values of fields if not set to null
            $this->post['fields'] = $this->inputFields()->mapWithKeys(function ($field) {
                return [$field['slug'] => $this->post['fields'][$field['slug']] ?? null];
            })->toArray();
        } else {
            $this->post['fields'] = $this->model->value;
        }

        // dd($this->post['fields'], $this->model->value);
    }

    public function render()
    {
        return view('aura::livewire.team-settings')->layout('aura::components.layout.app');
    }

    public function rules()
    {
        return Arr::dot([
            'post.fields' => $this->validationRules(),
        ]);
    }

    public function save()
    {
        // Save Fields as team-settings in Option table
        $option = 'team-settings';

        // $value = json_encode($this->post['fields']);
        // dd($value);

        $o = Option::updateOrCreate(['name' => $option], ['value' => $this->post['fields']]);

        // $this->validate();

        if (config('aura.teams')) {
            Cache::forget(auth()->user()->current_team_id.'.aura.team-settings');
        } else {
            Cache::forget('aura.team-settings');
        }

        return $this->notify(__('Successfully updated'));

        // dd('hier')

        // $this->post->save();

        // Artisan::call('make:posttype', [
        //     'name' => $this->post['fields']['name'],
        // ]);

        // return $this->notify('Created successfully.');
    }
}
