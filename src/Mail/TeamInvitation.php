<?php

namespace Aura\Base\Mail;

use Aura\Base\Resources\TeamInvitation as TeamInvitationResource;
use Aura\Base\Resources\User;
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
        return $this->markdown('aura::emails.team-invitation', [
            'registerUrl' => URL::temporarySignedRoute('aura.invitation.register', $this->expiresAt(), [
                'team' => $this->invitation->team,
                'teamInvitation' => $this->invitation,
            ]),
            'userExists' => User::withoutGlobalScopes()->where('email', $this->invitation->email)->exists(),
            'acceptUrl' => URL::temporarySignedRoute('aura.team-invitations.accept', $this->expiresAt(), ['invitation' => $this->invitation]),
        ])
            ->subject(__('You have been invited to join the :team team!', ['team' => $this->invitation->team->name]));
    }

    protected function expiresAt(): Carbon
    {
        return now()->addDays((int) config('aura.auth.invitation_expiry', 7));
    }
}
