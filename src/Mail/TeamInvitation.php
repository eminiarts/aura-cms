<?php

namespace Eminiarts\Aura\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Eminiarts\Aura\Resources\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Queue\SerializesModels;
use Eminiarts\Aura\Resources\TeamInvitation as TeamInvitationResource;

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
            'registerUrl' => URL::signedRoute('invitation.register', [
                'team' => $this->invitation->team,
                'teamInvitation' => $this->invitation,
            ]),
            'userExists' => User::where('email', $this->invitation->email)->exists(),
            'acceptUrl' => URL::signedRoute('team-invitations.accept', ['invitation' => $this->invitation,])
            ])->subject(__('Aura CMS - You have been invited to ' . $this->invitation->team->name));
    }
}
