<?php

namespace Aura\Base\Mail;

use Aura\Base\Resources\Team;
use Aura\Base\Resources\TeamInvitation as TeamInvitationResource;
use Aura\Base\Resources\User;
use Aura\Base\Services\InvitationConnectionResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class TeamInvitation extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * The team invitation instance.
     */
    public $invitation;

    /**
     * Create a new message instance.
     */
    public function __construct(TeamInvitationResource $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        /** @var TeamInvitationResource $invitationResource */
        $invitationResource = app(config('aura.resources.team-invitation'));
        $invitationResource = $invitationResource->newInstance();
        $invitationResource->setConnection($this->invitation->getConnectionName());
        $this->invitation = $invitationResource->newQueryWithoutScopes()
            ->without('meta')
            ->useWritePdo()
            ->findOrFail($this->invitation->getKey());
        $this->invitation->setRelation(
            'meta',
            $this->invitation->meta()->useWritePdo()->get(),
        );

        $connectionParameters = app(InvitationConnectionResolver::class)
            ->signedParameters($this->invitation);
        /** @var Team $teamResource */
        $teamResource = app(config('aura.resources.team'));
        $teamResource = $teamResource->newInstance();
        $teamResource->setConnection($this->invitation->getConnectionName());
        $team = $teamResource->newQueryWithoutScopes()
            ->useWritePdo()
            ->findOrFail($this->invitation->team_id);

        return $this->markdown('aura::emails.team-invitation', [
            'registerUrl' => URL::temporarySignedRoute('aura.invitation.register', $this->expiresAt(), [
                'team' => $team,
                'teamInvitation' => $this->invitation,
                ...$connectionParameters,
            ]),
            'userExists' => User::on($this->invitation->getConnectionName())
                ->withoutGlobalScopes()
                ->useWritePdo()
                ->whereRaw('lower(email) = ?', [mb_strtolower($this->invitation->email)])
                ->exists(),
            'acceptUrl' => URL::temporarySignedRoute('aura.team-invitations.accept', $this->expiresAt(), [
                'invitation' => $this->invitation,
                ...$connectionParameters,
            ]),
        ])
            ->subject(__('You have been invited to join the :team team!', ['team' => $team->getAttribute('name')]));
    }

    protected function expiresAt(): Carbon
    {
        return now()->addDays((int) config('aura.auth.invitation_expiry', 7));
    }
}
