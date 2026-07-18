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
                // Offer the merged, shadow-resolved catalog — each slug once,
                // the team's Shadow winning over the Global Role it shadows —
                // the same set the Roles index and the user-form picker show.
                'options' => config('aura.teams')
                    ? Role::shadowResolved(optional(auth()->user())->current_team_id)->get()->pluck('name', 'id')->toArray()
                    : [],
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

    public function mount(): void
    {
        abort_unless(config('aura.teams'), 404);
    }

    public function render()
    {
        abort_unless(config('aura.teams'), 404);

        return view('aura::livewire.user.invite-user');
    }

    public function rules()
    {
        abort_unless(config('aura.teams'), 404);

        $rules = Arr::dot([
            'form.fields' => $this->validationRules(),

        ]);

        $rules['form.fields.email'] = [
            'required', 'email',
            function ($attribute, $value, $fail) {
                $team = auth()->user()->currentTeam;

                if (! $team) {
                    $fail('Teams are disabled.');

                    return;
                }

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
        abort_unless(config('aura.teams'), 404);

        $this->validate();

        $team = auth()->user()->currentTeam;

        abort_unless($team, 404);

        $this->authorize('invite-users', $team);

        $invitation = $team->teamInvitations()->create([
            'email' => $email = $this->form['fields']['email'],
            'role' => $this->form['fields']['role'],
        ]);

        Mail::to($email)->send(new TeamInvitation($invitation));

        $this->notify('Invitation sent successfully.');

        $this->dispatch('closeModal');
        $this->dispatch('refreshTable');
    }
}
