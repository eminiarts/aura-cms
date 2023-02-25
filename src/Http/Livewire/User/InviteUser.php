<?php

namespace Eminiarts\Aura\Http\Livewire\User;

use Eminiarts\Aura\Mail\TeamInvitation;
use Eminiarts\Aura\Resources\Role;
use Eminiarts\Aura\Resources\Team;
use Eminiarts\Aura\Traits\InputFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use LivewireUI\Modal\ModalComponent;

class InviteUser extends ModalComponent
{
    use AuthorizesRequests;
    use InputFields;

    public $post;

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
                'required', 'email', 'unique:users,email',
                function ($attribute, $value, $fail) {
                    $team = auth()->user()->currentTeam;

                    dd($team->users);

                    if ($team->users()->where('email', $value)->exists()) {
                        $fail('User already exists.');
                    }
                },
            ];

        return $rules;
    }

    public function save()
    {
        $this->validate();

        dd($this->rules());

        $this->authorize('invite-users', Team::class);

        $team = auth()->user()->currentTeam;

        $invitation = $team->teamInvitations()->create([
            'email' => $email = $this->post['fields']['email'],
            'role' => $this->post['fields']['role'],
        ]);

        Mail::to($email)->send(new TeamInvitation($invitation));

        $this->notify('Erfolgreich eingeladen.');

        $this->emit('closeModal');
    }
}
