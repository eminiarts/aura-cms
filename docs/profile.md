# Profile

Aura's default profile page is a Livewire component for the authenticated user. It renders the profile fields declared by the configured User resource. The page includes personal details, password changes, two-factor authentication management, and account deletion.

## Route and feature flag

Aura registers the profile route inside the configured admin domain and `aura.path` prefix. The route name is `aura.profile`. With the default `AURA_PATH=admin`, the URL is `/admin/profile`.

The component checks `aura.features.profile` during `mount()`. Set it to `false` to keep the route registered but return a 403 response:

```php
'features' => [
    'profile' => true,
],
```

The component is configurable:

```php
'components' => [
    'profile' => \Aura\Base\Livewire\Profile::class,
],
```

The route uses this component value, so an application component can replace `Aura\Base\Livewire\Profile`.

## Profile component and view

`Aura\Base\Livewire\Profile` uses the `InputFields` and `MediaFields` traits. On mount it checks the feature flag, loads `Auth::user()` into `model`, and copies the model attributes into `form`.

The component's `getFields()` method returns `$this->model->getProfileFields()`. The profile field list therefore comes from the User resource. The component's `rules()` method uses those fields to build `form.fields.*` validation rules. `save()` validates the form and updates the user. The component renders `aura::livewire.user.profile` with the `aura::components.layout.app` layout.

The package view is `resources/views/livewire/user/profile.blade.php`. It renders the breadcrumbs, page heading, Save button, validation errors, and the fields returned by `getProfileFields()`. It also exposes two injection points:

```php
use Aura\Base\Facades\Aura;

Aura::registerInjectView(
    'profile_before_header',
    fn (): string => view('partials.profile-banner')->render(),
);

Aura::registerInjectView(
    'profile_after_header',
    fn (): string => view('partials.profile-notice')->render(),
);
```

`profile_before_header` is above the breadcrumbs. `profile_after_header` is below the page heading and above the validation errors.

To replace the layout markup, publish the package views and edit the published copy:

```bash
php artisan vendor:publish --tag=aura-views
```

Edit `resources/views/vendor/aura/livewire/user/profile.blade.php` in the host application.

## Default profile fields

`Aura\Base\Traits\ProfileFields` supplies the default `getProfileFields()` method used by `Aura\Base\Resources\User`. It returns one flat field array grouped into these tabs:

| Tab | Fields |
| --- | --- |
| Details | A `Text` field for `name` and a `Text` field for `email`. Their rules are `required` and `required|email`. |
| Password | `current_password`, `password`, and `password_confirmation` Password fields. |
| 2FA | A `LivewireComponent` field that renders `aura::two-factor-authentication-form`. |
| Delete | A `View` field that renders `aura::profile.delete-user-form`. |

Each tab is an `Aura\Base\Fields\Tab` with `global => true`, followed by an `Aura\Base\Fields\Panel`. The profile field list is separate from the regular User resource field list. `aura.auth.2fa` does not remove the 2FA field from this list.

The default User resource also defines an `avatar` Image field in `getFields()`. `ProfileFields` does not add that field to the profile tab, so the default profile page does not provide an avatar editor. The User resource edit form does show the avatar field. The `avatarUrl` accessor used by the default navigation currently returns a `ui-avatars.com` URL derived from the user's initials rather than the stored `avatar` value.

## Saving profile data

Clicking Save calls `Profile::save()`. It validates the profile field definitions and then calls:

```php
$this->model->update([
    'fields' => $validatedData['form']['fields'],
]);
```

The User resource's regular `getFields()` definitions determine how each slug is saved. Core User attributes such as `name`, `email`, and `password` use the `users` table columns. Other declared input fields, including the default `avatar` Image field, use the User resource's meta storage because the default User resource has `$usesMeta = true`.

A field that appears only in `getProfileFields()` has no regular field class during the model save and is skipped unless the model supplies a matching `set{Slug}Field()` hook. Add a custom field to both `getProfileFields()` and `getFields()` when the value must persist through Aura's normal field pipeline. See [Fields](/docs/fields) for field definitions and storage profiles.

The `MediaFields` trait also handles media updates from the profile form. `reorderMedia()` converts the sortable media IDs and sends them through `updateField()`, which updates `form.fields` and dispatches the media update events used by media fields.

## Password changes

The default Password tab defines these rules:

| Field | Rules |
| --- | --- |
| `current_password` | `required_with:form.fields.password`, `current_password` |
| `password` | `nullable`, `confirmed`, `Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()` |
| `password_confirmation` | `required_with:form.fields.password`, `same:form.fields.password` |

The current password is required only when a new password is supplied. A new password must be at least 12 characters, use upper- and lowercase letters, contain a number and a symbol, and pass Laravel's `uncompromised` rule.

When the password changes, `Profile::save()` updates the password separately, removes all three password values from the form data before saving the remaining fields, and calls `logoutOtherBrowserSessions()`. That method deletes the user's other rows from the configured sessions table when the table exists. It leaves the current session row in place. This revokes database-backed browser sessions when the application has a matching sessions table. The separate `PUT /password` authentication endpoint does not call this profile method.

## Two-factor authentication management

The default 2FA field renders `Aura\Base\Livewire\TwoFactorAuthenticationForm`. `Aura\Base\Resources\User` uses Fortify's `TwoFactorAuthenticatable` trait. The Livewire component calls Fortify's actions directly:

- `enableTwoFactorAuthentication()` confirms the password, creates the secret and recovery codes, and shows the QR code and confirmation input.
- `confirmTwoFactorAuthentication()` confirms the submitted authenticator code and shows the recovery codes.
- `regenerateRecoveryCodes()` creates a new set of recovery codes.
- `showRecoveryCodes()` displays the current recovery codes after password confirmation.
- `disableTwoFactorAuthentication()` confirms the password and clears the 2FA data.

The current package enables Fortify's `confirm` and `confirmPassword` options, so these management actions require password confirmation. When the component mounts, it clears an unconfirmed secret if `two_factor_confirmed_at` is null. A completed setup generates eight recovery codes. The view displays the QR code, decrypted setup key, authenticator-code input, and recovery codes.

`aura.auth.2fa` controls registration of the Aura-prefixed management routes. It does not remove the profile field or change the Fortify feature configuration:

```php
'auth' => [
    '2fa' => true,
],
```

The management route names are `aura.two-factor.enable`, `aura.two-factor.confirm`, `aura.two-factor.disable`, `aura.two-factor.qr-code`, `aura.two-factor.secret-key`, and `aura.two-factor.recovery-codes`. Set the profile field aside or override `getProfileFields()` if the management UI must be hidden when the setting is false.

## Login-time two-factor challenge

Login-time 2FA uses Fortify's pending-login state. The password login controller invokes Fortify's `RedirectsIfTwoFactorAuthenticatable` action before it authenticates the session. For a confirmed 2FA user, a valid password stores the user ID in the `login.id` session key, redirects to `/two-factor-challenge`, and leaves the web guard unauthenticated.

The challenge routes are guest routes with the canonical Fortify names `two-factor.login` and `two-factor.login.store`. A missing pending-login state redirects back to `login`. A valid authenticator code or recovery code completes the login, consumes a recovery code when one was used, emits Aura's `LoggedIn` event after authentication, and redirects to the intended URL or `aura.auth.redirect`.

These challenge routes remain registered when `aura.auth.2fa` is `false`. Disabling that setting removes the Aura-prefixed management routes. It must not turn an already enrolled account into password-only login.

## Account deletion

The Delete tab renders `aura::profile.delete-user-form`. The view opens a Livewire confirmation dialog and binds the password to the profile component. It does not submit to a separate account-deletion route.

`Profile::deleteUser()` requires the current password, deletes the user resolved from `aura.resources.user`, invalidates the current session, regenerates the CSRF token, logs out the guard, and redirects to `/`:

```php
$this->validate([
    'password' => ['required', 'current_password'],
]);

$user = app(config('aura.resources.user'))::find(auth()->id());
$user->delete();

session()->invalidate();
session()->regenerateToken();
Auth::logout();

return Redirect::to('/');
```

## Customize profile fields

Override `getProfileFields()` on the User resource configured for the application. Keep the parent fields when you want to retain the password, 2FA, and Delete tabs. Add the same custom field definition to `getFields()` so `Profile::save()` can resolve its field class and persist the value.

This example adds a Preferences tab and stores its value as User meta on the default User storage profile:

```php
<?php

namespace App\Models;

use Aura\Base\Resources\User as AuraUser;

class User extends AuraUser
{
    public function getProfileFields(): array
    {
        return array_merge(parent::getProfileFields(), [
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Preferences',
                'slug' => 'tab-preferences',
                'global' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Preferences',
                'slug' => 'user-preferences',
            ],
            self::themeField(),
        ]);
    }

    public static function getFields(): array
    {
        return array_merge(parent::getFields(), [
            self::themeField(),
        ]);
    }

    private static function themeField(): array
    {
        return [
            'name' => 'Theme',
            'type' => 'Aura\\Base\\Fields\\Select',
            'options' => [
                'light' => 'Light',
                'dark' => 'Dark',
            ],
            'validation' => 'required',
            'slug' => 'theme_preference',
        ];
    }
}
```

To add the existing avatar field to the profile, add an `Aura\Base\Fields\Image` definition with the `avatar` slug to the profile field array. The default User resource already declares that slug in `getFields()`.

To replace the whole profile component, point `aura.components.profile` to a compatible Livewire component. To change only the markup, publish and override `aura::livewire.user.profile` as described above.

## Related guides

- [Authentication](/docs/authentication) for login, password reset, and Fortify configuration.
- [Fields](/docs/fields) for field types, validation, and storage.
- [Resources](/docs/resources) for extending the User resource.
- [Customizing views](/docs/customizing-views) for published Aura views and injection points.
