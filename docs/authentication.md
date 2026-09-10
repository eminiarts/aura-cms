# Authentication

Aura owns the web authentication routes and Blade views used by the admin panel. It uses Laravel Fortify for two-factor authentication, email-verification primitives, and password-confirmation middleware. Registration, login, logout, password reset, team invitations, and the controllers that handle them are provided by Aura.

Aura does not ship social login providers or an API login endpoint. Sanctum's token trait is present on the default User resource, but a host application must add its own API routes and token policy if it needs them.

## Configure the user model

The default resource is Aura\Base\Resources\User. It uses Laravel's authentication and password-reset contracts, the MustVerifyEmail trait, Fortify's TwoFactorAuthenticatable trait, Notifiable, and Sanctum's HasApiTokens.

A host application can extend the resource model:

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

If Aura should create and resolve the host class everywhere, set the resource mapping too:

~~~php
// config/aura.php
'resources' => [
    'user' => App\Models\User::class,
],
~~~

The aura:extend-user-model command changes an existing Laravel User model to extend Aura's resource. It does not add the MustVerifyEmail interface. Add that interface when the application uses Laravel's verified middleware or wants automatic verification notifications.

## Configuration

Authentication settings live in config/aura.php:

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

Aura\Base\Providers\AuthServiceProvider runs these Fortify registrations:

- Fortify::ignoreRoutes() prevents Fortify from registering a second set of authentication routes.
- Fortify::loginView() uses aura::auth.login.
- Fortify::twoFactorChallengeView() uses aura::auth.two-factor-challenge.
- fortify.features is replaced at runtime with email verification and two-factor authentication. Aura's own controllers handle registration and password reset.
- ResetPassword::createUrlUsing() generates links for aura.password.reset.
- Aura's two-factor response uses `aura.auth.redirect` or the intended URL, then dispatches `LoggedIn` after authentication.
- `VerifyEmail::createUrlUsing()` generates signed links for `aura.verification.verify`.
- The two-factor provider uses Google2FA and Laravel's cache repository.

A host application's `config/fortify.php` can therefore contain feature entries that Aura replaces at runtime. Change the Aura settings and host application routes for the behavior you want.

Login requests allow five failed attempts for each lowercased email and IP address during a 60-second window. Fortify's two-factor POST route uses the two-factor limiter, which allows five attempts per pending login session. Email-verification routes use the six-per-minute throttle declared in routes/auth.php.

## Route map

Aura loads routes/auth.php through its web route file. Authentication routes are at the application root. The admin panel uses the path configured by aura.path, which defaults to /admin.

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

The GET login route deliberately has the plain Laravel name login. Laravel's authentication middleware redirects guests to that name. There is no /aura-login route, and Aura registers only POST /logout.

## Login and logout

Aura's login form posts to POST /login. AuthenticatedSessionController uses LoginRequest, which:

1. Validates email and password.
2. Lowercases the email for the authentication lookup.
3. Supports the remember field.
4. Applies the five-attempt rate limit.
5. Clears the limit after a successful attempt.

After authentication, Aura regenerates the session, dispatches Aura\Base\Events\LoggedIn, and redirects to the intended URL or aura.auth.redirect.

~~~php
use Aura\Base\Events\LoggedIn;
use Illuminate\Support\Facades\Event;

Event::listen(LoggedIn::class, function (LoggedIn $event): void {
    $user = $event->user;
});
~~~

Logout accepts POST /logout. It logs out the web guard, invalidates the session, regenerates the CSRF token, and redirects to /.

## Registration and admission policy

Aura has two admission paths for a new user.

### Public registration

Set auth.registration to true and open /register. The controller validates the submitted name, email, and confirmed password. The team field is also required when teams are enabled.

With teams enabled, Aura:

1. Creates the user.
2. Creates a Team owned by the user.
3. Sets current_team_id.
4. Attaches the shared Global Role with slug admin as a Membership in that team. That role is a Super Admin role.

With teams disabled, Aura creates the user and assigns the catalog role with slug user. The role is created automatically if the catalog does not contain it.

Both branches dispatch Illuminate\Auth\Events\Registered, log the user in, and redirect to aura.auth.redirect. Public registration does not allow the requester to choose a role.

Aura validates email uniqueness case-insensitively but stores the submitted email string. Login and password-reset lookups lowercase their input. Normalize email addresses before registration until this casing mismatch is fixed in the source.

### Invitation registration for a new user

A team administrator sends an invitation through Aura\Base\Livewire\InviteUser. The component:

- requires the invite-users ability on the current team, a Global Admin, or the team owner;
- validates the selected role against the current team's team roles and visible Global Roles;
- refuses a super_admin role unless the inviter is a Super Admin or Global Admin;
- sends Aura\Base\Mail\TeamInvitation.

The email contains a temporary signed registration URL and an existing-user acceptance URL. The registration URL is available only when teams and auth.user_invitations are enabled.

InvitationRegisterUserController validates the name and confirmed password. It takes the email and role from the invitation, not from the request. The invitation role must still exist and belong to the team or be a Global Role. Aura rejects an email that already belongs to a user, case-insensitively. It then creates the user with the invited team's current_team_id and Membership inside a transaction, deletes the invitation, dispatches Registered, logs in the new user, and redirects to aura.auth.redirect.

### Invitation acceptance for an existing user

Existing users use GET /team-invitations/{invitation}. The request must be authenticated with the email that appears on the invitation, compared case-insensitively. The URL must be signed and unexpired. The invitation role must still be a team role for the invited team or a visible Global Role.

Aura attaches the Membership, switches the user to the team, deletes the invitation, and redirects to aura.dashboard. This path does not use Jetstream or Laravel's AddsTeamMembers contract. The auth.user_invitations setting does not gate this existing-user acceptance path. The teams setting does.

An invitation expires through its signed URL. The database row remains until it is accepted or revoked. auth.invitation_expiry controls the URL lifetime used when Aura sends or resends the message.

The invitation mailable currently decides whether an address belongs to an existing user with a case-sensitive database comparison. Acceptance and new-user registration use case-insensitive comparisons. Keep the invitation address casing equal to the stored address until this mismatch is fixed in the source.

### Team and membership boundaries

A Super Admin is a role-level grant inside the current team. A Global Admin is an instance-level grant evaluated by the AuraGlobalAdmin gate. A Global Admin can enter any team without a Membership. A normal user must already hold a Membership to switch teams.

TeamPolicy allows team creation only when Team::$createEnabled is true, auth.create_teams is true, and the actor is a Global Admin. A team's Super Admin does not gain team-creation rights from the role alone.

## Password reset and password updates

Aura uses its own password reset controllers and Laravel's Password broker.

- GET /forgot-password renders the request form.
- POST /forgot-password sends a broker reset link. Aura rewrites the link to aura.password.reset and lowercases the email used for lookup.
- GET /reset-password/{token} renders the reset form.
- POST /reset-password validates the token, email, and confirmed password, updates the password, rotates remember_token, dispatches PasswordReset, and redirects to login.
- PUT /password is the authenticated password update endpoint. It requires current_password and a confirmed password and returns to the previous page with password-updated.

The Profile Livewire component has a separate password update path. When current_password and password are provided, it updates the password and calls logoutOtherBrowserSessions(). That method removes other database-backed session rows only when the sessions table exists. The Aura migrations do not create that table. The direct PUT /password controller does not remove other sessions.

## Email verification

AuthServiceProvider enables Fortify's emailVerification feature, and Aura registers these routes:

- GET /email/verify shows the notice unless the user is already verified.
- GET /email/verify/{id}/{hash} requires a valid signed URL and marks the authenticated user as verified.
- POST /email/verification-notification sends a new notification unless the user is already verified.

Laravel's Registered listener sends the initial verification notification only when the registered model implements Illuminate\Contracts\Auth\MustVerifyEmail. Aura\Base\Resources\User uses the matching trait but currently does not implement that contract. A host User model should implement the interface, as shown earlier, and the application should protect routes that require verification with Laravel's verified middleware.

~~~php
Route::middleware(['auth', 'verified'])->group(function () {
    // Routes that require a verified email address.
});
~~~

The verification routes themselves are authenticated, but Aura does not apply verified middleware to the admin routes automatically.

## Two-factor authentication

Aura's User resource includes Fortify's TwoFactorAuthenticatable trait and the profile field that renders Aura\Base\Livewire\TwoFactorAuthenticationForm. The profile component can enable 2FA, show the QR code and secret, confirm the code, display or regenerate recovery codes, and disable 2FA. These management actions require password confirmation.

When `auth.2fa` is true, Aura registers the management endpoints behind authentication and `password.confirm:aura.password.confirm`. The profile component also requires password confirmation before changing two-factor settings.

After a valid password, a user with confirmed 2FA stays unauthenticated while Fortify stores `login.id` and redirects to `/two-factor-challenge`. A valid authenticator code or recovery code completes login, regenerates the session, dispatches `LoggedIn`, and redirects to the intended URL or `aura.auth.redirect`. An unconfirmed secret does not require a challenge.

The guest challenge remains available when `auth.2fa` is false. Disabling management does not bypass the second factor on an enrolled account. Aura defaults to five failed OTP attempts per pending user in a minute. Set `fortify.limiters.two-factor` to a custom named limiter to replace that policy.

## Customizing authentication views

The package views are under resources/views/auth and are published under the aura namespace:

- aura::auth.login
- aura::auth.register
- aura::auth.forgot-password
- aura::auth.reset-password
- aura::auth.verify-email
- aura::auth.confirm-password
- aura::auth.two-factor-challenge
- aura::auth.user_invitation

Copy a view to resources/views/vendor/aura/auth in the host application to override it. Keep the route names and CSRF fields expected by the controllers when replacing a form.

## Local quick login

Aura adds GET /login-as/{id} only when the application environment is local and the request host ends in .test. The route bypasses TeamScope to find the user by ID, logs the user in, and redirects to aura.dashboard. It returns 404 in every other environment or host. The login view shows the Admin shortcut under the same condition.

## Related guides

- [Configuration](/docs/configuration)
- [Teams](/docs/teams)
- [Roles and permissions](/docs/roles-permissions)
- [Profile](/docs/profile)
- [Customizing views](/docs/customizing-views)
