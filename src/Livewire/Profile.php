<?php

namespace Eminiarts\Aura\Livewire;

use Closure;
use Eminiarts\Aura\Resources\Attachment;
use Eminiarts\Aura\Resources\User;
use Eminiarts\Aura\Traits\InputFields;
use Eminiarts\Aura\Traits\MediaFields;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class Profile extends Component
{
    use InputFields;
    use MediaFields;

    public $confirmingUserDeletion = false;

    public $model;

    // protected $validationAttributes = [
    //     'resource.fields.signatur' => 'signatur',
    // ];

    // protected function validationAttributes()
    // {
    //     return [
    //         'resource.fields.signatur' => __('Signature'),
    //     ];
    // }

    /**
     * The user's current password.
     *
     * @var string
     */
    public $password = '';

    public $resource = [
        'fields' => [],
    ];

    // Listen for selectedAttachment
    protected $listeners = ['updateField' => 'updateField'];

    public function checkAuthorization()
    {
        if (config('aura.features.user_profile') == false) {
            abort(403, 'User profile is turned off.');
        }
    }

    /**
     * Confirm that the user would like to delete their account.
     *
     * @return void
     */
    public function confirmUserDeletion()
    {
        $this->dispatch('confirming-delete-user');

        $this->confirmingUserDeletion = true;
    }

    /**
     * Delete the current user.
     */
    public function deleteUser(Request $request)
    {
        $this->validate(['password' => ['required', 'current_password']]);

        $user = User::find(auth()->id());

        $user->delete();

        session()->invalidate();
        session()->regenerateToken();

        Auth::logout();

        return Redirect::to('/');
    }

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
                'name' => 'Change Password',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'slug' => 'user-details',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Current Password',
                'type' => 'Eminiarts\\Aura\\Fields\\Password',
                'validation' => ['required_with:resource.fields.password', 'current_password'],
                'slug' => 'current_password',
                'on_index' => false,
            ],
            [
                'name' => 'New Password',
                'type' => 'Eminiarts\\Aura\\Fields\\Password',
                'validation' => ['nullable', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
                'slug' => 'password',
                'on_index' => false,
            ],
            [
                'name' => 'Confirm Password',
                'type' => 'Eminiarts\\Aura\\Fields\\Password',
                'validation' => ['required_with:resource.fields.password', 'same:resource.fields.password'],
                'slug' => 'password_confirmation',
                'on_index' => false,
            ],
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => '2FA',
                'slug' => '2fa',
                'global' => true,
            ],
            [
                'name' => 'Two Factor Authentication',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'slug' => 'user-2fa',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => '2FA',
                'type' => 'Eminiarts\\Aura\\Fields\\LivewireComponent',
                'component' => 'aura::user-two-factor-authentication-form',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => '2fa',
            ],
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Delete',
                'slug' => 'delete-tab',
                'global' => true,
            ],
            [
                'name' => 'Delete Account',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'slug' => 'user-delete-panel',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => '2FA',
                'type' => 'Eminiarts\\Aura\\Fields\\View',
                'view' => 'aura::profile.delete-user-form',
                'validation' => '',
                'conditional_logic' => [],
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
            return [$field['slug'] => $this->resource['fields'][$field['slug']] ?? null];
        });
    }

    public function logoutOtherBrowserSessions()
    {
        if (request()->hasSession() && Schema::hasTable('sessions')) {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', Auth::user()->getAuthIdentifier())
                ->where('id', '!=', request()->session()->getId())
                ->delete();
        }
    }

    public function mount()
    {
        $this->checkAuthorization();

        $this->model = auth()->user()->resource;

        $this->resource = $this->model->attributesToArray();

        // dd($this->resource['fields'], $this->model);
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
        return $this->resourceFieldValidationRules();
    }

    public function save()
    {
        // dd('save', $this->rules());
        $this->validate();

        // dd('hier');

        // if $this->resource['fields']['current_password'] and  is set, save password
        if (optional($this->resource['fields'])['current_password'] && optional($this->resource['fields'])['password']) {

            $this->model->update([
                'password' => bcrypt($this->resource['fields']['password']),
            ]);

            // unset password fields
            unset($this->resource['fields']['current_password']);
            unset($this->resource['fields']['password']);
            unset($this->resource['fields']['password_confirmation']);

            // Logout other devices

            $this->logoutOtherBrowserSessions();

        }
        // dd('here2', $this->resource['fields']);
        if (empty(optional($this->resource['fields'])['password'])) {
            unset($this->resource['fields']['password']);
        }

        $this->model->update($this->resource);

        // dd($this->resource['fields'], $this->rules(), $this->model);
        return $this->notify(__('Successfully updated'));
    }

    public function updateField($data)
    {
        // dd($data);
        $this->resource['fields'][$data['slug']] = $data['value'];
        // $this->save();

        $this->dispatch('selectedMediaUpdated', [
            'slug' => $data['slug'],
            'value' => $data['value'],
        ]);
    }
}
