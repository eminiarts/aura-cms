<?php

namespace Eminiarts\Aura\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Resources\Option;
use Illuminate\Support\Facades\Cache;
use Eminiarts\Aura\Traits\InputFields;
use Eminiarts\Aura\Traits\RepeaterFields;
use Eminiarts\Aura\Models\Scopes\TeamScope;

class AuraConfig extends Component
{
    use InputFields;
    use RepeaterFields;

    public $config;

    public $model;

    public $post = [
        'fields' => [],
    ];



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
                'name' => 'Registration Settings',
                'slug' => 'panel-registration',
            ],
            [
                'name' => 'Team registration',
                'type' => 'Eminiarts\\Aura\\Fields\\Boolean',
                'instructions' => 'With this option enabled, users will be able to create a team when they register for an account. This is useful if you want to allow organizations or groups to use your site.',
                'slug' => 'team_registration',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'User Invitations ',
                'type' => 'Eminiarts\\Aura\\Fields\\Boolean',
                'instructions' => 'With this option enabled, users will be able to invite other users to their team. This is useful if you want to allow organizations or groups to use your site.',
                'slug' => 'user_invitations',
                'style' => [
                    'width' => '100',
                ],
            ],

            [
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'name' => 'General Aura Settings',
                'slug' => 'panel-DZzV',
            ],

            [
                'name' => 'App Name',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'slug' => 'app_name',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'App Description',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'slug' => 'app_description',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'App URL',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'slug' => 'app_url',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'App Locale',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'slug' => 'app_locale',
                'style' => [
                    'width' => '100',
                ],
            ],

            // app_timezone text field
            [
                'name' => 'App Timezone',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'slug' => 'app_timezone',
                'style' => [
                    'width' => '100',
                ],
            ],

            [
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'name' => 'Media Settings',
                'slug' => 'panel-DZzV',
            ],

            [
                'name' => 'Media',
                'type' => 'Eminiarts\\Aura\\Fields\\Group',
                'slug' => 'media',
                'style' => [
                    'width' => '100',
                ],
            ],

            // media.disk text field
            [
                'name' => 'Media Disk',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'slug' => 'media.disk',
                'style' => [
                    'width' => '100',
                ],
            ],

            // media.url text field
            [
                'name' => 'Media Path',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'slug' => 'media.path',
                'style' => [
                    'width' => '100',
                ],
            ],

            // media.max_file_size text field
            [
                'name' => 'Max File Size',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'slug' => 'media.max_file_size',
                'style' => [
                    'width' => '100',
                ],
            ],

            // generate_thumbnails boolean field
            [
                'name' => 'Generate Thumbnails',
                'type' => 'Eminiarts\\Aura\\Fields\\Boolean',
                'slug' => 'media.generate_thumbnails',
                'style' => [
                    'width' => '100',
                ],
            ],

            // media.thumbnails repeater field
            [
                'name' => 'Thumbnails',
                'type' => 'Eminiarts\\Aura\\Fields\\Repeater',
                'nested' => true,
                'slug' => 'thumbnails',
                'style' => [
                    'width' => '100',
                ],

            ],

            [
                'name' => 'Name',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'slug' => 'name',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'name' => 'Width',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'slug' => 'width',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'name' => 'Height',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'slug' => 'height',
                'style' => [
                    'width' => '33',
                ],
            ],

            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Features',
                'slug' => 'tab-features',
                'global' => true,
            ],

            [
                'name' => 'Features',
                'type' => 'Eminiarts\\Aura\\Fields\\View',
                'slug' => 'features',
                'view' => 'aura::aura.features',
                'style' => [
                    'width' => '100',
                ],
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
        $this->model = Aura::getGlobalOptions();

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
        return view('aura::livewire.aura-config')->layout('aura::components.layout.app');
    }

    public function rules()
    {
        return Arr::dot([
            'post.fields' => $this->validationRules(),
        ]);
    }

    public function save()
    {
        $this->validate();

        $this->model->value = json_encode($this->post['fields']);

        $this->model->save();

        Cache::forget('aura-settings');

        $this->notify('Erfolgreich gespeichert!');
    }



    public function updatedConfig()
    {
        // Update Config
        //$this->config = config('aura')['features'];

        // Save Config
        $this->save();
    }
}
