# Authentication

Aura provides the admin panel's authentication routes, controllers, and Blade views. These handle registration, login, logout, password resets, and team invitations. Laravel Fortify supplies two-factor authentication, email verification support, and password-confirmation middleware.

Aura does not include social login providers or an API login endpoint. The default user resource includes Sanctum's token trait. To use API authentication, add the API routes and token policy in your application.

## Configure the user model

The default user resource, `Aura\Base\Resources\User`, supports Laravel authentication and password resets. It also includes traits for email verification, Fortify two-factor authentication, notifications, and Sanctum API tokens. Email verification requires an additional interface, as described below.

Extend this resource to use your own user model:

~~~php
<?php

namespace App\Models;

use Aura\Base\Resources\User as AuraUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends AuraUser implements MustVerifyEmail
{
}
~~~

Point Laravel's Eloquent provider at that class:

~~~php
// config/auth.php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
],
~~~

Also set Aura's resource mapping so it uses your class when creating and resolving users:

~~~php
// config/aura.php
'resources' => [
    'user' => App\Models\User::class,
],
~~~

You can also run `aura:extend-user-model` to make an existing Laravel user model extend Aura's resource. The command does not add the `MustVerifyEmail` interface. Add it yourself if you use Laravel's `verified` middleware or want automatic verification notifications.

## Configuration

Authentication settings live in `config/aura.php`:

~~~php
'teams' => env('AURA_TEAMS', true),

'auth' => [
    'registration' => env('AURA_REGISTRATION', true),
    'redirect' => '/'.trim(env('AURA_PATH', 'admin'), '/'),
    '2fa' => true,
    'user_invitations' => true,
    'invitation_expiry' => 7,
    'create_teams' => env('AURA_CREATE_TEAMS', true),
],
~~~

| Key | Default | Effect |
| --- | --- | --- |
| teams | true | Enables team-scoped storage and membership. It also controls team creation during public registration and whether new-user invitation registration routes are registered. |
| auth.registration | true | Registers the public registration routes. Disabled registration returns 404 and the login view hides its registration link. |
| auth.redirect | /{AURA_PATH} | Login, registration, verification, and invitation registration redirect here. With the default AURA_PATH value, the path is /admin. |
| auth.2fa | true | Registers two-factor management routes. Enrolled accounts still require a login challenge when management is disabled. |
| auth.user_invitations | true | Allows the new-user invitation registration controller. Existing-user invitation acceptance has a separate teams-only path. |
| auth.invitation_expiry | 7 | Number of days used when Aura creates temporary signed invitation URLs. |
| auth.create_teams | true | Allows TeamPolicy to authorize Team resource creation, but only for an Aura Global Admin. It does not enable public registration or grant a team Super Admin permission to create teams. |

Teams-off mode is a supported single-tenant installation. Invitation registration routes are not registered in that mode, and accepting a team invitation returns 404. The current-team route remains registered, but switching returns false when teams are disabled.

### Runtime Fortify setup

Aura configures Fortify at runtime through its authentication service provider:

- Fortify's routes are disabled to avoid registering a second set of authentication routes.
- Login and two-factor challenges use Aura's `aura::auth.login` and `aura::auth.two-factor-challenge` views.
- Aura replaces `fortify.features` with email verification and two-factor authentication. Its own controllers handle registration and password resets.
- Password reset notifications link to `aura.password.reset`. Verification notifications use signed links to `aura.verification.verify`.
- After two-factor authentication, Aura dispatches `LoggedIn` and redirects to the intended URL or `aura.auth.redirect`.
- The two-factor provider uses Google2FA and Laravel's cache repository.

A host application's `config/fortify.php` can therefore contain feature entries that Aura replaces at runtime. Change the Aura settings and host application routes for the behavior you want.

Login allows five failed attempts per lowercased email and IP address within 60 seconds. The two-factor challenge allows five attempts per pending login session. Email-verification routes allow six requests per minute, as defined in `routes/auth.php`.

## Route map

Authentication routes start at the application root, even when the admin panel uses a prefix. The panel's path comes from `aura.path` and defaults to `/admin`. Aura loads the authentication routes from `routes/auth.php` through its web route file.

| Method | URI | Name | Middleware or condition |
| --- | --- | --- | --- |
| GET | /login | login | guest |
| POST | /login | login.store | guest |
| POST | /logout | aura.logout | web |
| GET | /login-as/{id} | aura.login-as | guest, local environment, host ending in .test |
| GET | /register | aura.register | guest, auth.registration |
| POST | /register | aura.register.post | guest, auth.registration |
| GET, POST | /register/{team}/{teamInvitation} | aura.invitation.register, aura.invitation.register.post | guest, signed, teams, auth.user_invitations |
| GET | /forgot-password | aura.password.request | guest |
| POST | /forgot-password | aura.password.email | guest |
| GET | /reset-password/{token} | aura.password.reset | guest |
| POST | /reset-password | aura.password.store | guest |
| PUT | /password | aura.password.update | auth |
| GET | /confirm-password | aura.password.confirm | auth |
| POST | /confirm-password | unnamed | auth |
| GET | /email/verify | aura.verification.notice | auth |
| GET | /email/verify/{id}/{hash} | aura.verification.verify | auth, signed, throttle:6,1 |
| POST | /email/verification-notification | aura.verification.send | auth, throttle:6,1 |
| PUT | /current-team | aura.current-team.update | auth |
| GET | /team-invitations/{invitation} | aura.team-invitations.accept | auth, signed, teams |
| DELETE | /teams/{team}/team-invitations/{invitation} | aura.team-invitations.destroy | auth, teams, can:invite-users,team |
| POST | /teams/{team}/team-invitations/{invitation}/resend | aura.team-invitations.resend | auth, teams, can:invite-users,team |
| GET | /two-factor-challenge | two-factor.login | guest, pending login session |
| POST | /two-factor-challenge | two-factor.login.store | guest, pending login session, two-factor throttle |
| POST | /user/two-factor-authentication | aura.two-factor.enable | auth:web, password.confirm:aura.password.confirm, auth.2fa |
| POST | /user/confirmed-two-factor-authentication | aura.two-factor.confirm | auth:web, password.confirm:aura.password.confirm, auth.2fa |
| DELETE | /user/two-factor-authentication | aura.two-factor.disable | auth:web, password.confirm:aura.password.confirm, auth.2fa |
| GET | /user/two-factor-qr-code | aura.two-factor.qr-code | auth:web, password.confirm:aura.password.confirm, auth.2fa |
| GET | /user/two-factor-secret-key | aura.two-factor.secret-key | auth:web, password.confirm:aura.password.confirm, auth.2fa |
| GET, POST | /user/two-factor-recovery-codes | aura.two-factor.recovery-codes, unnamed POST | auth:web, password.confirm:aura.password.confirm, auth.2fa |

The login page uses Laravel's standard route name, `login`, so authentication middleware can redirect guests to it. There is no `/aura-login` route. Logout accepts only `POST /logout`.

## Login and logout

The login form submits to `POST /login`. Aura validates the email and password, then lowercases the email for the authentication lookup. The form supports remembering the user through the `remember` field. A successful attempt clears the five-attempt rate limit.

After authentication, Aura regenerates the session and redirects to the intended URL or `aura.auth.redirect`. It also dispatches a login event that your application can listen for:

~~~php
use Aura\Base\Events\LoggedIn;
use Illuminate\Support\Facades\Event;

Event::listen(LoggedIn::class, function (LoggedIn $event): void {
    $user = $event->user;
});
~~~

A request to `POST /logout` logs out the web guard, invalidates the session, regenerates the CSRF token, and redirects to `/`.

## Registration and admission policy

Aura has two admission paths for a new user.

### Public registration

Set `auth.registration` to `true` and open `/register`. The controller validates the submitted name, email, and confirmed password. The team field is also required when teams are enabled.

With teams enabled, Aura:

1. Creates the user.
2. Creates a team owned by the user.
3. Sets that team as the user's current team.
4. Adds a membership with the shared global role whose slug is `admin`. This gives the user Super Admin permissions within that team.

With teams disabled, Aura creates the user and assigns the catalog role whose slug is `user`. It creates this role if it does not already exist in the catalog.

Both paths dispatch Laravel's `Registered` event, log the user in, and redirect to `aura.auth.redirect`. Users cannot choose their role during public registration.

Aura validates email uniqueness case-insensitively but stores the submitted email string. Login and password-reset lookups lowercase their input. Normalize email addresses before registration until this casing mismatch is fixed in the source.

### Invitation registration for a new user

Team administrators send invitations through Aura's invitation form. The inviter must have the `invite-users` ability on the current team, be a Global Admin, or own the team.

The form allows roles belonging to the current team and visible global roles. Only a Super Admin or Global Admin can invite someone to a role with `super_admin` enabled. Aura sends the invitation using its `TeamInvitation` mailable.

The email contains a temporary signed registration URL and an existing-user acceptance URL. The registration URL is available only when teams and `auth.user_invitations` are enabled.

Invitation registration validates the name and confirmed password. The invitation determines the email and role. The role must still exist and either belong to the invited team or be a global role. Aura rejects emails that already belong to a user, using a case-insensitive comparison.

Aura creates the user and membership inside a transaction, with the invited team set as the user's current team. It then deletes the invitation, dispatches `Registered`, logs in the new user, and redirects to `aura.auth.redirect`.

### Invitation acceptance for an existing user

Existing users accept invitations through `GET /team-invitations/{invitation}`. The request must be authenticated with the email that appears on the invitation, compared case-insensitively. The URL must be signed and unexpired. The invitation role must still be a team role for the invited team or a visible global role.

Aura adds the membership, switches the user to the team, deletes the invitation, and redirects to `aura.dashboard`. Existing-user acceptance requires teams to be enabled, but does not depend on `auth.user_invitations`. This path does not use Jetstream or Laravel's `AddsTeamMembers` contract.

An invitation expires through its signed URL. The database row remains until it is accepted or revoked. The `auth.invitation_expiry` setting controls the URL lifetime when Aura sends or resends the message.

The invitation mailable currently decides whether an address belongs to an existing user with a case-sensitive database comparison. Acceptance and new-user registration use case-insensitive comparisons. Keep the invitation address casing equal to the stored address until this mismatch is fixed in the source.

### Team and membership boundaries

A Super Admin has elevated permissions within the current team. A Global Admin has instance-wide access, evaluated by the `AuraGlobalAdmin` gate, and can enter any team without a membership. Other users must already belong to a team before they can switch to it.

Creating a team requires Global Admin access, with both `Team::$createEnabled` and `auth.create_teams` set to `true`. A team's Super Admin role alone does not grant permission to create teams.

## Password reset and password updates

Aura uses its own password reset controllers and Laravel's Password broker.

- GET /forgot-password renders the request form.
- POST /forgot-password sends a broker reset link. Aura rewrites the link to aura.password.reset and lowercases the email used for lookup.
- GET /reset-password/{token} renders the reset form.
- POST /reset-password validates the token, email, and confirmed password, updates the password, rotates remember_token, dispatches PasswordReset, and redirects to login.
- PUT /password is the authenticated password update endpoint. It requires current_password and a confirmed password and returns to the previous page with password-updated.

Password changes through the profile form also remove the user's other database-backed sessions. This happens when both the current and new password are provided, and only if the `sessions` table exists. Aura's migrations do not create that table. The direct `PUT /password` endpoint does not remove other sessions.

## Email verification

Aura enables Fortify's email verification feature and registers these routes:

- GET /email/verify shows the notice unless the user is already verified.
- GET /email/verify/{id}/{hash} requires a valid signed URL and marks the authenticated user as verified.
- POST /email/verification-notification sends a new notification unless the user is already verified.

Laravel sends the initial verification notification after registration only if the user model implements `Illuminate\Contracts\Auth\MustVerifyEmail`. Aura's default user resource includes the verification trait but does not implement this interface. Add it to your user model, as shown earlier.

Protect any routes that require a verified email address with Laravel's `verified` middleware:

~~~php
Route::middleware(['auth', 'verified'])->group(function () {
    // Routes that require a verified email address.
});
~~~

The verification routes require authentication. Aura does not automatically apply `verified` middleware to the admin routes.

## Two-factor authentication

Users can manage two-factor authentication from their profile. They can enable it, view the QR code and secret, confirm an authenticator code, display or regenerate recovery codes, and disable it. These actions require password confirmation.

Aura's user resource provides this through Fortify's two-factor trait and the `TwoFactorAuthenticationForm` Livewire component.

When `auth.2fa` is true, Aura registers the management endpoints behind authentication and `password.confirm:aura.password.confirm`. The profile component also requires password confirmation before changing two-factor settings.

After entering a valid password, a user with confirmed two-factor authentication remains unauthenticated until they complete the challenge at `/two-factor-challenge`. Fortify stores their pending login in the session as `login.id`. A valid authenticator code or recovery code completes login, regenerates the session, dispatches `LoggedIn`, and redirects to the intended URL or `aura.auth.redirect`. An unconfirmed secret does not require a challenge.

The guest challenge remains available when `auth.2fa` is false. Disabling management does not bypass the second factor on an enrolled account. Aura defaults to five failed OTP attempts per pending user in a minute. Set `fortify.limiters.two-factor` to a custom named limiter to replace that policy.

## Customizing authentication views

The authentication views are in `resources/views/auth` and use the `aura` namespace:

- aura::auth.login
- aura::auth.register
- aura::auth.forgot-password
- aura::auth.reset-password
- aura::auth.verify-email
- aura::auth.confirm-password
- aura::auth.two-factor-challenge
- aura::auth.user_invitation

To override a view, copy it to `resources/views/vendor/aura/auth` in your application. Keep the route names and CSRF fields expected by the controllers when replacing a form.

## Local quick login

For local development, `GET /login-as/{id}` logs in a user directly by ID and redirects to `aura.dashboard`. The lookup bypasses team scoping.

This route is available only when the application environment is `local` and the request host ends in `.test`. It returns 404 in every other environment or host. The login page shows the **Admin** shortcut under the same condition.

## Related guides

- [Configuration](/docs/configuration)
- [Teams](/docs/teams)
- [Roles and permissions](/docs/roles-permissions)
- [Profile](/docs/profile)
- [Customizing views](/docs/customizing-views)
