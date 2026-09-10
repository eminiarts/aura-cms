# Profile

Aura's default profile page lets the signed-in user manage their account. A Livewire component displays the profile fields declared by the configured user resource. The page includes personal details, password changes, two-factor authentication management, and account deletion.

## Route and feature flag

The profile page uses your configured admin domain and path prefix. Its named route is `aura.profile`, and its default URL is `/admin/profile`. Set the prefix through `aura.path`, which defaults to `admin` through the `AURA_PATH` environment variable.

To disable access to the page, set `aura.features.profile` to `false`. The component checks this setting when it mounts and returns a 403 response. The route remains registered:

```php
'features' => [
    'profile' => true,
],
```

You can replace the page with your own Livewire component through the profile component setting:

```php
'components' => [
    'profile' => \Aura\Base\Livewire\Profile::class,
],
```

The profile route uses the component you configure here.

## Profile component and view

The default component, `Aura\Base\Livewire\Profile`, loads the authenticated user when it mounts, after checking that the page is enabled. It stores the user in its `model` property and copies the user's attributes into `form`. The input and media behavior comes from the `InputFields` and `MediaFields` traits.

The user resource defines which fields appear through `getProfileFields()`. The component returns these definitions from `getFields()` and uses them to build validation rules for `form.fields.*` in its `rules()` method. Saving validates the form before updating the user.

The page renders the `aura::livewire.user.profile` view inside the `aura::components.layout.app` layout.

The package view at `resources/views/livewire/user/profile.blade.php` displays the breadcrumbs, heading, Save button, validation errors, and profile fields. Use its two injection points to add content around the header:

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

The default user resource gets its profile fields from the `Aura\Base\Traits\ProfileFields` trait. Its `getProfileFields()` method returns a flat array, with tab fields dividing the page into these sections:

| Tab | Fields |
| --- | --- |
| Details | Text inputs for the name and email address. Both are required, and the email must be valid. |
| Password | Password inputs for the current password, new password, and confirmation. |
| 2FA | A Livewire component for managing two-factor authentication, rendered by `aura::two-factor-authentication-form`. |
| Delete | An account deletion form, rendered by the view field `aura::profile.delete-user-form`. |

Each tab uses a tab field with `global => true`, followed by a panel field. These profile definitions are separate from the user resource's regular fields. Disabling `aura.auth.2fa` does not remove the 2FA tab.

The default user resource has an image field for the avatar on its regular edit form, but the profile page does not include an avatar editor. You can [add that field to the profile](#customize-profile-fields). The default navigation uses the `avatarUrl` accessor, which currently returns an image from ui-avatars.com based on the user's initials. It does not display the stored avatar.

## Saving profile data

Clicking Save validates the form against the profile field definitions, then updates the user:

```php
$this->model->update([
    'fields' => $validatedData['form']['fields'],
]);
```

The user resource's regular field definitions determine where values are stored. Core attributes such as the name, email, and password use columns in the `users` table. Other declared inputs, including the avatar image, use meta storage because the default resource enables it with `$usesMeta = true`.

Declare custom inputs in both `getProfileFields()` and the resource's regular `getFields()` method so Aura can save their values. A profile-only field has no field class available during the model save, so Aura skips it unless the model defines a matching `set{Slug}Field()` hook. See [Fields](/docs/fields) for field definitions and storage profiles.

Media fields also support updates in the profile form through the `MediaFields` trait. When media is reordered, `reorderMedia()` converts the sortable IDs and passes them to `updateField()`. This updates the form's field values and dispatches the events that media fields use to refresh their state.

## Password changes

The default Password tab defines these rules:

| Field | Rules |
| --- | --- |
| `current_password` | `required_with:form.fields.password`, `current_password` |
| `password` | `nullable`, `confirmed`, `Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()` |
| `password_confirmation` | `required_with:form.fields.password`, `same:form.fields.password` |

The current password is required only when a new password is supplied. A new password must be at least 12 characters, use upper- and lowercase letters, contain a number and a symbol, and pass Laravel's `uncompromised` rule.

When the password changes, the profile component updates it separately and removes all three password inputs before saving the remaining fields. It then calls `logoutOtherBrowserSessions()` to revoke the user's other database-backed browser sessions. This deletes the user's other rows from the configured sessions table, if that table exists, and keeps the current session.

This behavior depends on the application having a matching sessions table. The separate `PUT /password` authentication endpoint does not call the profile component's session cleanup method.

## Two-factor authentication management

The default user resource supports two-factor authentication through Fortify's `TwoFactorAuthenticatable` trait. On the profile page, `Aura\Base\Livewire\TwoFactorAuthenticationForm` calls Fortify's actions to manage setup and recovery codes:

- Enabling two-factor authentication confirms the password, creates the secret and recovery codes, and displays the QR code and confirmation input.
- Confirming setup checks the submitted authenticator code and displays the recovery codes.
- Regenerating recovery codes replaces them with a new set.
- Viewing recovery codes displays the current set after password confirmation.
- Disabling two-factor authentication confirms the password and clears the setup data.

The current package enables Fortify's `confirm` and `confirmPassword` options, so these management actions require password confirmation. When the component mounts, it clears an unconfirmed secret if `two_factor_confirmed_at` is null. A completed setup generates eight recovery codes. The view displays the QR code, decrypted setup key, authenticator-code input, and recovery codes.

`aura.auth.2fa` controls registration of the Aura-prefixed management routes. It does not remove the profile field or change the Fortify feature configuration:

```php
'auth' => [
    '2fa' => true,
],
```

The management route names are `aura.two-factor.enable`, `aura.two-factor.confirm`, `aura.two-factor.disable`, `aura.two-factor.qr-code`, `aura.two-factor.secret-key`, and `aura.two-factor.recovery-codes`. If disabling this setting should also hide the management UI, omit the field from your profile definitions by overriding `getProfileFields()`.

## Login-time two-factor challenge

Users who have confirmed their two-factor setup must complete a challenge after entering a valid password. Before authenticating the session, the password login controller calls Fortify's `RedirectsIfTwoFactorAuthenticatable` action. It stores the user ID in the `login.id` session key and redirects to `/two-factor-challenge`. The web guard remains unauthenticated until the challenge succeeds.

The challenge routes are guest routes with the canonical Fortify names `two-factor.login` and `two-factor.login.store`. A missing pending-login state redirects back to `login`. A valid authenticator code or recovery code completes the login, consumes a recovery code when one was used, emits Aura's `LoggedIn` event after authentication, and redirects to the intended URL or `aura.auth.redirect`.

These challenge routes remain registered when `aura.auth.2fa` is `false`. Disabling that setting removes the Aura-prefixed management routes. It must not turn an already enrolled account into password-only login.

## Account deletion

The Delete tab opens a Livewire confirmation dialog that asks for the user's password. Its view, `aura::profile.delete-user-form`, binds that password to the profile component instead of submitting to a separate account-deletion route.

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

<a id="customize-profile-fields"></a>

## Customize profile fields

Override `getProfileFields()` on your application's user resource to change the profile form. Include the parent fields to retain the password, 2FA, and Delete tabs. Declare custom inputs in the resource's regular `getFields()` method too, so Aura can resolve their field classes and save their values.

This example adds a Preferences tab with a theme selector. On the default user resource, the selection is saved in meta storage:

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

To add an avatar editor, include an image field with the `avatar` slug in your profile definitions. The default user resource already declares this field in its regular field list.

To replace the whole profile component, point `aura.components.profile` to a compatible Livewire component. To change only the markup, publish and override `aura::livewire.user.profile` as described above.

## Related guides

- [Authentication](/docs/authentication) for login, password reset, and Fortify configuration.
- [Fields](/docs/fields) for field types, validation, and storage.
- [Resources](/docs/resources) for extending the user resource.
- [Customizing views](/docs/customizing-views) for published Aura views and injection points.
