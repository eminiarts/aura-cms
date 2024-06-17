<?php

namespace Aura\Base\Livewire;

use Aura\Base\Facades\Aura;
use Aura\Base\Traits\InputFields;
use Aura\Base\Traits\MediaFields;
use Aura\Base\Traits\RepeaterFields;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Config extends Component
{
    use InputFields;
    use MediaFields;
    use RepeaterFields;

    public $config;

    public $form = [
        'fields' => [],
    ];

    public $model;

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
            // [
            //     'type' => 'Aura\\Base\\Fields\\Panel',
            //     'name' => 'Registration Settings',
            //     'slug' => 'panel-registration',
            // ],
            // [
            //     'name' => 'Team registration',
            //     'type' => 'Aura\\Base\\Fields\\Boolean',
            //     'instructions' => 'With this option enabled, users will be able to create a team when they register for an account. This is useful if you want to allow organizations or groups to use your site.',
            //     'slug' => 'team_registration',
            //     'style' => [
            //         'width' => '100',
            //     ],
            //     'conditional_logic' => [
            //         function () {
            //             return config('aura.teams');
            //         },
            //         // function () {
            //         //     return auth()->user()->email == 'ivan@eminiarts.ch';
            //         // },
            //     ],
            // ],
            // [
            //     'name' => 'User Invitations ',
            //     'type' => 'Aura\\Base\\Fields\\Boolean',
            //     'instructions' => 'With this option enabled, users will be able to invite other users to their team. This is useful if you want to allow organizations or groups to use your site.',
            //     'slug' => 'user_invitations',
            //     'style' => [
            //         'width' => '100',
            //     ],
            // ],

            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'General Aura Settings',
                'slug' => 'panel-DZzV',
            ],

            [
                'name' => 'App Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'app_name',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'App Description',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'app_description',
                'style' => [
                    'width' => '100',
                ],
            ],
            // [
            //     'name' => 'App URL',
            //     'type' => 'Aura\\Base\\Fields\\Text',
            //     'slug' => 'app_url',
            //     'style' => [
            //         'width' => '100',
            //     ],
            // ],

            [
                'name' => 'App Logo',
                'type' => 'Aura\\Base\\Fields\\Image',
                'slug' => 'logo',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'App Logo (Darkmode)',
                'type' => 'Aura\\Base\\Fields\\Image',
                'slug' => 'logo-darkmode',
                'style' => [
                    'width' => '50',
                ],
            ],

            [
                'name' => 'App Favicon',
                'type' => 'Aura\\Base\\Fields\\Image',
                'slug' => 'app-favicon',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'App Favicon (Darkmode)',
                'type' => 'Aura\\Base\\Fields\\Image',
                'slug' => 'app-favicon-darkmode',
                'style' => [
                    'width' => '50',
                ],
            ],

            // [
            //     'name' => 'App Locale',
            //     'type' => 'Aura\\Base\\Fields\\Text',
            //     'slug' => 'app_locale',
            //     'style' => [
            //         'width' => '100',
            //     ],
            // ],

            // // app_timezone text field
            // [
            //     'name' => 'App Timezone',
            //     'type' => 'Aura\\Base\\Fields\\Text',
            //     'slug' => 'app_timezone',
            //     'style' => [
            //         'width' => '100',
            //     ],
            // ],

            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Media Settings',
                'slug' => 'tab-media',
                'global' => true,
            ],

            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Media Settings',
                'slug' => 'panel-DZzV',
            ],

            [
                'name' => 'Media',
                'type' => 'Aura\\Base\\Fields\\Group',
                'slug' => 'media',
                'style' => [
                    'width' => '100',
                ],
            ],

            // media.disk text field
            [
                'name' => 'Media Disk',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'media.disk',
                'style' => [
                    'width' => '100',
                ],
            ],

            // media.url text field
            [
                'name' => 'Media Path',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'media.path',
                'style' => [
                    'width' => '100',
                ],
            ],

            // media.max_file_size text field
            [
                'name' => 'Max File Size',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'media.max_file_size',
                'style' => [
                    'width' => '100',
                ],
            ],

            // generate_thumbnails boolean field
            [
                'name' => 'Generate Thumbnails',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'slug' => 'media.generate_thumbnails',
                'style' => [
                    'width' => '100',
                ],
            ],

            // media.thumbnails repeater field
            [
                'name' => 'Thumbnails',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'nested' => true,
                'slug' => 'media.thumbnails',
                'style' => [
                    'width' => '100',
                ],

            ],

            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'name',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'name' => 'Width',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'width',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'name' => 'Height',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'height',
                'style' => [
                    'width' => '33',
                ],
            ],

            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Features',
                'slug' => 'tab-features',
                'global' => true,
            ],

            [
                'name' => 'Features',
                'type' => 'Aura\\Base\\Fields\\View',
                'slug' => 'features-view',
                'view' => 'aura::aura.features',
                'style' => [
                    'width' => '100',
                ],
            ],

            // [
            //     'name' => 'Features',
            //     'type' => 'Aura\\Base\\Fields\\Text',
            //     'slug' => 'features',
            //     'style' => [
            //         'width' => '100',
            //     ],
            // ],

            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Login',
                'slug' => 'tab-login',
                'global' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Login Settings',
                'slug' => 'login-panel',
            ],
            [
                'name' => 'Darkmode',
                'type' => 'Aura\\Base\\Fields\\Radio',
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
                'name' => 'Login Logo',
                'type' => 'Aura\\Base\\Fields\\Image',
                'slug' => 'login-logo',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Login Logo (Darkmode)',
                'type' => 'Aura\\Base\\Fields\\Image',
                'slug' => 'login-logo-darkmode',
                'style' => [
                    'width' => '50',
                ],
            ],

            [
                'name' => 'Login Background',
                'type' => 'Aura\\Base\\Fields\\Image',
                'slug' => 'login-bg',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Login Background (Darkmode)',
                'type' => 'Aura\\Base\\Fields\\Image',
                'slug' => 'login-bg-darkmode',
                'style' => [
                    'width' => '50',
                ],
            ],

            [
                'name' => 'Login Color Palette',
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
                ],
                'slug' => 'color-palette',
            ],
            [
                'name' => 'Login Gray Color Palette',
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
                    'blackout' => 'Blackout',
                ],
                'slug' => 'gray-color-palette',
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
        abort_unless(config('aura.features.global_config'), 404);

        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $this->model = Aura::getGlobalOptions();

        if (is_string($this->model->value)) {
            $this->form['fields'] = json_decode($this->model->value, true);
            // set default values of fields if not set to null
            $this->form['fields'] = $this->inputFields()->mapWithKeys(function ($field) {
                return [$field['slug'] => $this->form['fields'][$field['slug']] ?? null];
            })->toArray();
        }
    }

    public function render()
    {
        return view('aura::livewire.config')->layout('aura::components.layout.app');
    }

    public function rules()
    {
        return Arr::dot([
            'form.fields' => $this->validationRules(),
            'form.fields.features' => '',
        ]);
    }

    public function save()
    {
        $this->validate();

        $this->model->value = json_encode($this->form['fields']);

        $this->model->save();

        Cache::forget('aura-settings');

        $this->notify('Erfolgreich gespeichert!');
    }

    public function updatedConfig()
    {
        $this->save();
    }
}
