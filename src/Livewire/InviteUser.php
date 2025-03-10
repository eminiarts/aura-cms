<?php

namespace Aura\Base\Livewire;

use Aura\Base\Mail\TeamInvitation;
use Aura\Base\Resources\Role;
use Aura\Base\Traits\InputFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class InviteUser extends Component
{
    use AuthorizesRequests;
    use InputFields;

    public $form = [
        'fields' => [
            'email' => '',
            'role' => '',
        ],
    ];

    public static function getFields()
    {
        return [
            [
                'name' => 'Email',
                'type' => 'Aura\\Base\\Fields\\Email',
                'placeholder' => 'email@example.com',
                'validation' => 'required|email',
                'slug' => 'email',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Role',
                'type' => 'Aura\\Base\\Fields\\Select',
                'validation' => 'required',
                'slug' => 'role',
                'options' => Role::get()->pluck('name', 'id')->toArray(),
                'style' => [
                    'width' => '50',
                ],
            ],
        ];
    }

    public function getFieldsProperty()
    {
        $fields = collect($this->mappedFields());

        return $this->fieldsForView($fields);
    }

    public function render()
    {
        return view('aura::livewire.user.invite-user');
    }

    public function rules()
    {
        $rules = Arr::dot([
            'form.fields' => $this->validationRules(),

        ]);

        $rules['form.fields.email'] = [
            'required', 'email',
            function ($attribute, $value, $fail) {
                $team = auth()->user()->currentTeam;

                if ($team->users()->where('email', $value)->exists()) {
                    $fail('User already exists.');
                }

                if ($team->teamInvitations()->whereMeta('email', $value)->exists()) {
                    $fail('User already invited.');
                }
            },
        ];

        return $rules;
    }

    public function save()
    {
        $this->validate();

        $team = auth()->user()->currentTeam;

        $this->authorize('invite-users', $team);

        $invitation = $team->teamInvitations()->create([
            'email' => $email = $this->form['fields']['email'],
            'role' => $this->form['fields']['role'],
        ]);

        Mail::to($email)->send(new TeamInvitation($invitation));

        $this->notify('Erfolgreich eingeladen.');

        $this->dispatch('closeModal');
        $this->dispatch('refreshTable');
    }
}
