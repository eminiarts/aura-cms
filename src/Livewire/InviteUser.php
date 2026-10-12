<?php

namespace Aura\Base\Livewire;

use Aura\Base\Mail\TeamInvitation;
use Aura\Base\Mail\UserInvitation as UserInvitationMail;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Aura\Base\Resources\UserInvitation;
use Aura\Base\Rules\CaseInsensitiveUniqueEmail;
use Aura\Base\Traits\InputFields;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
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

    public static function getFields(): array
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
                'options' => static::availableRoles()->pluck('name', 'id')->toArray(),
                'style' => [
                    'width' => '50',
                ],
            ],
        ];
    }

    public function getFieldsProperty()
    {
        // Role choices depend on the current actor and must not use static field caches.
        return $this->fieldsForView(collect(static::getFields()));
    }

    public function mount(): void
    {
        $this->authorizeInvitation();
    }

    public function render()
    {
        $this->authorizeInvitation();

        return view('aura::livewire.user.invite-user');
    }

    public function rules(): array
    {
        $this->authorizeInvitation();

        if (! config('aura.teams')) {
            return [
                'form.fields.email' => [
                    'bail', 'required', 'string', 'email', 'max:255', new CaseInsensitiveUniqueEmail,
                    function (string $attribute, string $value, Closure $fail): void {
                        if (UserInvitation::whereHas('meta', fn (Builder $query) => $query
                            ->where('key', 'email')
                            ->whereRaw('lower(value) = ?', [mb_strtolower($value)]))
                            ->where('created_at', '>=', now()->subDays((int) config('aura.auth.invitation_expiry', 7)))
                            ->exists()) {
                            $fail(__('User already invited.'));
                        }
                    },
                ],
                'form.fields.role' => ['required', Rule::in(static::availableRoles()->pluck('id')->all())],
            ];
        }

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

        // `required` alone accepts any role id the client sends, including a
        // role owned by another team or one carrying super_admin. Resolve the
        // submitted id against the same shadow-resolved catalog the select
        // offers, and mirror the Roles field's escalation guard on top.
        $rules['form.fields.role'] = [
            'required',
            function ($attribute, $value, $fail) {
                $role = Role::shadowResolvedForCurrentTeam()->whereKey($value)->first();

                if (! $role) {
                    $fail(__('The selected role is invalid.'));

                    return;
                }

                if ($role->super_admin && ! $this->actorMayGrantSuperAdmin()) {
                    $fail(__('You are not allowed to invite a super admin.'));
                }
            },
        ];

        return $rules;
    }

    public function save(): void
    {
        $this->authorizeInvitation();

        $this->validate();

        if (! config('aura.teams')) {
            $invitation = UserInvitation::create([
                'email' => $this->form['fields']['email'],
                'role' => $this->form['fields']['role'],
            ]);

            Mail::to($invitation->email)->send(new UserInvitationMail($invitation));
        } else {
            $team = auth()->user()->currentTeam;

            abort_unless($team, 404);

            $this->authorize('invite-users', $team);

            $invitation = $team->teamInvitations()->create([
                'email' => $email = $this->form['fields']['email'],
                'role' => $this->form['fields']['role'],
            ]);

            Mail::to($email)->send(new TeamInvitation($invitation));
        }

        $this->notify('Invitation sent successfully.');

        $this->dispatch('closeModal');
        $this->dispatch('refreshTable');
    }

    /**
     * Whether the inviter may hand out a super_admin role — a Super Admin of
     * the current team, or a Global Admin. Mirrors Roles::saved()'s guard so
     * an invitation cannot become the cheaper escalation path.
     */
    protected function actorMayGrantSuperAdmin(): bool
    {
        $actor = auth()->user();

        if (! $actor) {
            return false;
        }

        // The authenticated guard may hand back a bare Authenticatable; the
        // super_admin question is only answerable on the Aura User model.
        if (! $actor instanceof User) {
            $actor = User::withoutGlobalScopes()->find($actor->getAuthIdentifier());
        }

        if (! $actor instanceof User) {
            return false;
        }

        return $actor->isSuperAdmin() || $actor->isAuraGlobalAdmin();
    }

    protected function authorizeInvitation(): void
    {
        abort_unless(config('aura.auth.user_invitations'), 404);

        if (! config('aura.teams')) {
            $this->authorize('create', app(config('aura.resources.user')));
        }
    }

    protected static function availableRoles(): Builder
    {
        if (config('aura.teams')) {
            return Role::shadowResolvedForCurrentTeam();
        }

        $user = auth()->user();

        if ($user && ! $user instanceof User) {
            $user = app(config('aura.resources.user'))->find($user->getAuthIdentifier());
        }

        return Role::query()->when(! $user?->isSuperAdmin(), fn (Builder $query) => $query->where('super_admin', false));
    }

    protected function validationAttributes(): array
    {
        return [
            'form.fields.email' => 'email',
            'form.fields.role' => 'role',
        ];
    }
}
