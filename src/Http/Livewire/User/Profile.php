<?php

namespace Eminiarts\Aura\Http\Livewire\User;

use Closure;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Eminiarts\Aura\Resources\User;
use Illuminate\Support\Facades\Auth;
use Eminiarts\Aura\Traits\InputFields;
use Eminiarts\Aura\Traits\MediaFields;
use Eminiarts\Aura\Resources\Attachment;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rules\Password;

class Profile extends Component
{
    use InputFields;
    use MediaFields;

    public $confirmingUserDeletion = false;

    public $model;

    /**
     * The user's current password.
     *
     * @var string
     */
    public $password = '';

    public $post = [
        'fields' => [],
    ];

    // Listen for selectedAttachment
    protected $listeners = ['updateField' => 'updateField'];

    /**
     * Confirm that the user would like to delete their account.
     *
     * @return void
     */
    public function confirmUserDeletion()
    {
        $this->dispatchBrowserEvent('confirming-delete-user');

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
                'name' => 'Avatar',
                'type' => 'Eminiarts\\Aura\\Fields\\Image',
                'max' => '1',
                'validation' => ['array','max:1',
                    // function (string $attribute, mixed $value, Closure $fail) {
                    //     // Check if the attachment is an image
                    //     Attachment::find($value)->each(function ($attachment) use ($fail, $attribute) {
                    //         if (! $attachment->isImage()) {
                    //             $fail("The {$attribute} is not an image.");
                    //         }
                    //     });

                    // }
            ],
                'conditional_logic' => [],
                'slug' => 'avatar',
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
                'validation' => ['required_with:post.fields.password', 'current_password'],
                'slug' => 'current_password',
                'on_index' => false,
            ],
            [
                'name' => 'New Password',
                'type' => 'Eminiarts\\Aura\\Fields\\Password',
                'validation' => ['nullable', 'confirmed', Password::min(8)],
                'slug' => 'password',
                'on_index' => false,
            ],
            [
                'name' => 'Confirm Password',
                'type' => 'Eminiarts\\Aura\\Fields\\Password',
                'validation' => '',
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
            return [$field['slug'] => $this->post['fields'][$field['slug']] ?? null];
        });
    }

    public function mount()
    {
        $this->model = auth()->user()->resource;

        $this->post = $this->model->attributesToArray();

        // dd($this->post['fields'], $this->model);
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
        return $this->postFieldValidationRules();
    }

    public function save()
    {
        ray($this->post['fields'], $this->rules());
        $this->validate();

        // if $this->post['fields']['current_password'] and  is set, save password
        if (optional($this->post['fields'])['current_password'] && optional($this->post['fields'])['password']) {


            $this->model->update([
                'password' => bcrypt($this->post['fields']['password']),
            ]);


            // unset password fields
            unset($this->post['fields']['current_password']);
            unset($this->post['fields']['password']);
            unset($this->post['fields']['password_confirmation']);
        }
        // dd('here2', $this->post['fields']);
        if (empty(optional($this->post['fields'])['password'])) {
            unset($this->post['fields']['password']);
        }

        $this->model->update($this->post);

        // dd($this->post['fields'], $this->rules(), $this->model);
        return $this->notify('Successfully updated.');
    }
}
