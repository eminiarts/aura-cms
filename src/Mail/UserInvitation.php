<?php

namespace Aura\Base\Mail;

use Aura\Base\Resources\UserInvitation as UserInvitationResource;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class UserInvitation extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public UserInvitationResource $invitation) {}

    public function build(): static
    {
        return $this->markdown('aura::emails.user-invitation', [
            'registerUrl' => URL::temporarySignedRoute('aura.user-invitations.register',
                now()->addDays((int) config('aura.auth.invitation_expiry', 7)),
                ['invitation' => $this->invitation]),
        ])->subject(__('You have been invited to :app', ['app' => config('app.name')]));
    }
}
