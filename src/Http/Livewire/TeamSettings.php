<?php

namespace Eminiarts\Aura\Http\Livewire;

use Eminiarts\Aura;
use Eminiarts\Aura\Resources\Option;
use Eminiarts\Aura\Traits\InputFields;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Component;

class TeamSettings extends Component
{
    use InputFields;

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
                'type' => 'App\\Aura\\Fields\\Tab',
                'name' => 'General',
                'slug' => 'tab-general',
                'global' => true,
            ],
            [
                'type' => 'App\\Aura\\Fields\\Panel',
                'name' => 'Panel',
                'slug' => 'panel-DZzV',
            ],

            [
                'name' => 'App Logo',
                'type' => 'App\\Aura\\Fields\\File',
                'slug' => 'app-logo',
                'style' => [
                    'width' => '50',
                ],
            ],

            [
                'name' => 'App Logo (Darkmode)',
                'type' => 'App\\Aura\\Fields\\File',
                'slug' => 'app-logo-darkmode',
                'style' => [
                    'width' => '50',
                ],
            ],

            [
                'name' => 'Timezone',
                'type' => 'App\\Aura\\Fields\\Text',
                'slug' => 'timezone1',
            ],
            [
                'name' => 'Timezone',
                'type' => 'App\\Aura\\Fields\\Text',
                'slug' => 'timezone',
            ],

            [
                'type' => 'App\\Aura\\Fields\\Tab',
                'name' => 'Theme',
                'slug' => 'tab-theme',
                'global' => true,
            ],
            [
                'type' => 'App\\Aura\\Fields\\Panel',
                'name' => 'Sidebar settings',
                'slug' => 'panel-theme-sidebar',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Sidebar',
                'type' => 'App\\Aura\\Fields\\Radio',
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
                'type' => 'App\\Aura\\Fields\\Radio',
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
                'type' => 'App\\Aura\\Fields\\Panel',
                'name' => 'Primary Colors',
                'slug' => 'panel-theme-primary',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'name' => 'Primary Color Palette',
                'type' => 'App\\Aura\\Fields\\Select',
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
                'type' => 'App\\Aura\\Fields\\Tab',
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
                'type' => 'App\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-25',
            ],
            [
                'name' => 'Primary 50',
                'type' => 'App\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-50',
            ],
            [
                'name' => 'Primary 100',
                'type' => 'App\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-100',
            ],
            [
                'name' => 'Primary 200',
                'type' => 'App\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-200',
            ],
            [
                'name' => 'Primary 300',
                'type' => 'App\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-300',
            ],
            [
                'name' => 'Primary 400',
                'type' => 'App\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-400',
            ],
            [
                'name' => 'Primary 500',
                'type' => 'App\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-500',
            ],
            [
                'name' => 'Primary 600',
                'type' => 'App\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-600',
            ],
            [
                'name' => 'Primary 700',
                'type' => 'App\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-700',
            ],
            [
                'name' => 'Primary 800',
                'type' => 'App\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-800',
            ],
            [
                'name' => 'Primary 900',
                'type' => 'App\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'primary-900',
            ],

            [
                'type' => 'App\\Aura\\Fields\\Panel',
                'name' => 'Gray Colors',
                'slug' => 'panel-theme-gray',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'name' => 'Gray Color Palette',
                'type' => 'App\\Aura\\Fields\\Select',
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
                'type' => 'App\\Aura\\Fields\\Tab',
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
                'type' => 'App\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-25',
            ],
            [
                'name' => 'Gray 50',
                'type' => 'App\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-50',
            ],
            [
                'name' => 'Gray 100',
                'type' => 'App\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-100',
            ],
            [
                'name' => 'Gray 200',
                'type' => 'App\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-200',
            ],
            [
                'name' => 'Gray 300',
                'type' => 'App\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-300',
            ],
            [
                'name' => 'Gray 400',
                'type' => 'App\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-400',
            ],
            [
                'name' => 'Gray 500',
                'type' => 'App\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-500',
            ],
            [
                'name' => 'Gray 600',
                'type' => 'App\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-600',
            ],
            [
                'name' => 'Gray 700',
                'type' => 'App\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-700',
            ],
            [
                'name' => 'Gray 800',
                'type' => 'App\\Aura\\Fields\\Color',
                'options' => [
                    'native' => false,
                ],
                'slug' => 'gray-800',
            ],
            [
                'name' => 'Gray 900',
                'type' => 'App\\Aura\\Fields\\Color',
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
        $valueString = json_encode(
            [
                'darkmode-type' => 'auto',
                'sidebar-type' => 'primary',
                'color-palette' => 'aura',
                'gray-color-palette' => 'slate',
            ]
        );
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
        }
    }

    public function render()
    {
        return view('livewire.team-settings');
    }

    public function reorderMedia($slug, $ids)
    {
        $ids = collect($ids)->map(function ($id) {
            return Str::after($id, '_file_');
        })->toArray();

        $this->updateField([
            'slug' => $slug,
            'value' => $ids,
        ]);
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
        $value = json_encode($this->post['fields']);
        $o = Option::updateOrCreate(['name' => $option], ['value' => $value]);

        // $this->validate();

        // clear cache of auth()->user()->current_team_id . '.aura.team-settings'
        Cache::forget(auth()->user()->current_team_id.'.aura.team-settings');

        return $this->notify('Successfully updated.');

        // dd('hier')

        // $this->post->save();

        // Artisan::call('make:posttype', [
        //     'name' => $this->post['fields']['name'],
        // ]);

        // return $this->notify('Created successfully.');
    }

    // Select Attachment
    public function updateField($data)
    {
        $this->post['fields'][$data['slug']] = $data['value'];

        // dd($this->post['fields'][$data['slug']], $data['value']);
        // dd($this->post);
        $this->save();
    }
}
