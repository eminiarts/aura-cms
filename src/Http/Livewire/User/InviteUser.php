<?php

namespace Eminiarts\Aura\Http\Livewire\User;

use Illuminate\Support\Arr;
use Eminiarts\Aura\Resources\Role;
use Eminiarts\Aura\Resources\Team;
use Illuminate\Support\Facades\Mail;
use LivewireUI\Modal\ModalComponent;
use Eminiarts\Aura\Traits\InputFields;
use Eminiarts\Aura\Mail\TeamInvitation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Artisan;

class InviteUser extends ModalComponent
{
    use InputFields;
    use AuthorizesRequests;

    public $post;

    public static function getFields()
    {
        return [
            [
                'name' => 'Email',
                'type' => 'Eminiarts\\Aura\\Fields\\Email',
                'placeholder' => 'email@example.com',
                'validation' => 'required|email|unique:users,email',
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
        return Arr::dot([
            'post.fields' => $this->validationRules(),
        ]);
    }

    public function save()
    {
        $this->validate();

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
