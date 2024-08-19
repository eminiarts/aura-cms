<?php

namespace Aura\Base\Livewire;

use Aura\Base\Resources\User;
use Aura\Base\Traits\InputFields;
use Aura\Base\Traits\MediaFields;
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

    public $form = [
        'fields' => [],
    ];

    public $model;

    // protected $validationAttributes = [
    //     'form.fields.signatur' => 'signatur',
    // ];

    // protected function validationAttributes()
    // {
    //     return [
    //         'form.fields.signatur' => __('Signature'),
    //     ];
    // }

    /**
     * The user's current password.
     *
     * @var string
     */
    public $password = '';

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

    public function getFields()
    {
       return $this->user->getProfileFields();
    }

    public function getUserProperty()
    {
        return User::find(auth()->id());
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

        $this->model = auth()->user();

        $this->form = $this->model->attributesToArray();

        // dd($this->form['fields'], $this->model);
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

        // if $this->form['fields']['current_password'] and  is set, save password
        if (optional($this->form['fields'])['current_password'] && optional($this->form['fields'])['password']) {

            $this->model->update([
                'password' => bcrypt($this->form['fields']['password']),
            ]);

            // unset password fields
            unset($this->form['fields']['current_password']);
            unset($this->form['fields']['password']);
            unset($this->form['fields']['password_confirmation']);

            // Logout other devices

            $this->logoutOtherBrowserSessions();

        }
        // dd('here2', $this->form['fields']);
        if (empty(optional($this->form['fields'])['password'])) {
            unset($this->form['fields']['password']);
        }

        $this->model->update($this->form);

        // dd($this->form['fields'], $this->rules(), $this->model);
        return $this->notify(__('Successfully updated'));
    }

    public function updateField($data)
    {
        // dd($data);
        $this->form['fields'][$data['slug']] = $data['value'];
        // $this->save();

        $this->dispatch('selectedMediaUpdated', [
            'slug' => $data['slug'],
            'value' => $data['value'],
        ]);
    }
}
