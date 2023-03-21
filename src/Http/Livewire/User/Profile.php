<?php

namespace Eminiarts\Aura\Http\Livewire\User;

use Eminiarts\Aura\Aura;
use Eminiarts\Aura\Resources\Option;
use Eminiarts\Aura\Traits\InputFields;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Component;

class Profile extends Component
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
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'User details',
                'slug' => 'tab-user',
                'global' => true,
            ],
            [
                'name' => 'Personal Infos',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'validation' => 'required',
                'slug' => 'user-details',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Name',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'name',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Email',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required|email',
                'on_index' => true,
                'slug' => 'email',
                'style' => [
                    'width' => '100',
                ],
            ],
             [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Password',
                'slug' => 'tab-password',
                'global' => true,
            ],
            [
                'name' => 'Current Password',
                'type' => 'Eminiarts\\Aura\\Fields\\Password',
                'validation' => '',
                'slug' => 'current_password',
                'on_index' => false,
                'on_view' => false,
            ],
            [
                'name' => 'New Password',
                'type' => 'Eminiarts\\Aura\\Fields\\Password',
                'validation' => '',
                'slug' => 'password',
                'on_index' => false,
                'on_view' => false,
            ],
            [
                'name' => 'Confirm Password',
                'type' => 'Eminiarts\\Aura\\Fields\\Password',
                'validation' => '',
                'slug' => 'confirm_password',
                'on_index' => false,
                'on_view' => false,
            ],
             [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => '2FA',
                'label' => 'Tab',
                'slug' => '2fa',
                'global' => true,
                'on_view' => false,
            ],
            [
                'name' => '2FA',
                'type' => 'Eminiarts\\Aura\\Fields\\LivewireComponent',
                'component' => 'aura::user-two-factor-authentication-form',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => '2fa',
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
        $this->model = auth()->user();

        $this->post['fields'] = $this->model;
    }

    public function render()
    {
        return view('aura::livewire.user.profile')->layout('aura::components.layout.app');
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
        // $this->validate();

        dd('save');

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
