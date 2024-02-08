<?php

namespace Aura\Base\Mail;

use Aura\Base\Resources\TeamInvitation as TeamInvitationResource;
use Aura\Base\Resources\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class TeamInvitation extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * The team invitation instance.
     *
     * @var \Laravel\Jetstream\TeamInvitation
     */
    public $invitation;

    /**
     * Create a new message instance.
     *
     * @param  \Laravel\Jetstream\TeamInvitation  $invitation
     * @return void
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
            'registerUrl' => URL::signedRoute('aura.invitation.register', [
                'team' => $this->invitation->team,
                'teamInvitation' => $this->invitation,
            ]),
            'userExists' => User::where('email', $this->invitation->email)->exists(),
            'acceptUrl' => URL::signedRoute('aura.team-invitations.accept', ['invitation' => $this->invitation]),
        ])
            ->subject(__('You have been invited to join the :team team!', ['team' => $this->invitation->team->name]));
    }
}
