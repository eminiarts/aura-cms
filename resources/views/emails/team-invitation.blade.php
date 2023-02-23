@component('mail::message')
{{ __('You have been invited to join the :team team!', ['team' => $invitation->team->name]) }}

@if($userExists)
{{ __('You may accept this invitation by clicking the button below:') }}

@component('mail::button', ['url' => $acceptUrl])
{{ __('Accept Invitation') }}
@endcomponent

@else
{{ __('You may register an account by clicking the button below:') }}

@component('mail::button', ['url' => $registerUrl])
{{ __('Register') }}
@endcomponent
@endif

{{ __('If you did not expect to receive an invitation to this team, you may discard this email.') }}
@endcomponent
