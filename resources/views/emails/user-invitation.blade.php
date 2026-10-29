@component('mail::message')
{{ __('You have been invited to :app', ['app' => config('app.name')]) }}

{{ __('You may register an account by clicking the button below:') }}

@component('mail::button', ['url' => $registerUrl])
{{ __('Register') }}
@endcomponent

{{ __('If you did not expect this invitation, you may discard this email.') }}
@endcomponent
