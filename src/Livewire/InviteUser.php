<?php

namespace Eminiarts\Aura\Livewire;

use Eminiarts\Aura\Mail\TeamInvitation;
use Eminiarts\Aura\Resources\Role;
use Eminiarts\Aura\Traits\InputFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use LivewireUI\Modal\ModalComponent;

class InviteUser extends ModalComponent
{
    use AuthorizesRequests;
    use InputFields;

    public $post = [
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
                'type' => 'Eminiarts\\Aura\\Fields\\Email',
                'placeholder' => 'email@example.com',
                'validation' => 'required|email',
                'slug' => 'email',
            ],
            [
                'name' => 'Role',
                'type' => 'Eminiarts\\Aura\\Fields\\Select',
                'validation' => 'required',
                'slug' => 'role',
                'options' => Role::get()->pluck('title', 'id')->toArray(),
            ],
        ];
    }

    public function getFieldsProperty()
    {
        $fields = collect($this->mappedFields());

        return $this->fieldsForView($fields);
    }

    public static function modalMaxWidth(): string
    {
        return '7xl';
    }

    public function render()
    {
        return view('aura::livewire.user.invite-user');
    }

    public function rules()
    {
        $rules = Arr::dot([
            'post.fields' => $this->validationRules(),

        ]);

        $rules['post.fields.email'] = [
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
        // dd($this->rules());

        $this->authorize('invite-users', $team);

        $invitation = $team->teamInvitations()->create([
            'email' => $email = $this->post['fields']['email'],
            'role' => $this->post['fields']['role'],
        ]);

        Mail::to($email)->send(new TeamInvitation($invitation));

        $this->notify('Erfolgreich eingeladen.');

        $this->dispatch('closeModal');
    }
}