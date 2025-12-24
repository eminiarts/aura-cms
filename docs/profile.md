# User Profile

Aura CMS provides a built-in user profile page that allows users to manage their personal information, change passwords, configure two-factor authentication, and delete their account.

## Table of Contents

- [Overview](#overview)
- [Configuration](#configuration)
- [Profile Page](#profile-page)
- [Profile Fields](#profile-fields)
- [Password Management](#password-management)
- [Two-Factor Authentication](#two-factor-authentication)
- [Account Deletion](#account-deletion)
- [Customizing the Profile](#customizing-the-profile)
- [Extending Profile Fields](#extending-profile-fields)

## Overview

The profile system in Aura CMS is built around a single Livewire component (`Aura\Base\Livewire\Profile`) that dynamically renders profile fields using the `ProfileFields` trait. The profile is organized into tabs:

- **Details** - Personal information (name, email)
- **Password** - Change password with current password verification
- **2FA** - Two-factor authentication setup
- **Delete** - Account deletion

## Configuration

### Enabling/Disabling the Profile

The profile feature can be enabled or disabled in `config/aura.php`:

```php
'features' => [
    'profile' => true,  // Set to false to disable profile access
],
```

When disabled, users attempting to access the profile will receive a 403 error.

### Customizing the Profile Component

You can replace the default profile component with your own:

```php
// config/aura.php
'components' => [
    'profile' => App\Livewire\CustomProfile::class,
],
```

## Profile Page

The profile page is accessible at `/{admin-path}/profile` (default: `/admin/profile`).

### Profile Livewire Component

The profile component is located at `Aura\Base\Livewire\Profile`:

```php
namespace Aura\Base\Livewire;

use Aura\Base\Traits\InputFields;
use Aura\Base\Traits\MediaFields;
use Livewire\Component;

class Profile extends Component
{
    use InputFields;
    use MediaFields;

    public $form = ['fields' => []];
    public $model;
    public $password = '';
    public $confirmingUserDeletion = false;

    public function mount()
    {
        $this->checkAuthorization();
        $this->model = Auth::user();
        $this->form = $this->model->attributesToArray();
    }

    public function save()
    {
        $validatedData = $this->validate();

        // Handle password update if provided
        if (optional($this->form['fields'])['current_password'] && 
            optional($this->form['fields'])['password']) {
            $this->model->update([
                'password' => $this->form['fields']['password'],
            ]);
            $this->logoutOtherBrowserSessions();
        }

        $this->model->update(['fields' => $validatedData['form']['fields']]);

        return $this->notify(__('Successfully updated'));
    }

    public function render()
    {
        return view('aura::livewire.user.profile')
            ->layout('aura::components.layout.app');
    }
}
```

## Profile Fields

Profile fields are defined in the `ProfileFields` trait, which is used by the User resource. The trait provides a `getProfileFields()` method that returns an array of field definitions.

### Default Profile Fields

```php
namespace Aura\Base\Traits;

use Illuminate\Validation\Rules\Password;

trait ProfileFields
{
    public function getProfileFields()
    {
        return [
            // Details Tab
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Details',
                'slug' => 'tab-user',
                'global' => true,
            ],
            [
                'name' => 'Personal Infos',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'user-details',
            ],
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'slug' => 'name',
            ],
            [
                'name' => 'Email',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required|email',
                'slug' => 'email',
            ],

            // Password Tab
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Password',
                'slug' => 'tab-password',
                'global' => true,
            ],
            [
                'name' => 'Change Password',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'user-details',
            ],
            [
                'name' => 'Current Password',
                'type' => 'Aura\\Base\\Fields\\Password',
                'validation' => ['required_with:form.fields.password', 'current_password'],
                'slug' => 'current_password',
            ],
            [
                'name' => 'New Password',
                'type' => 'Aura\\Base\\Fields\\Password',
                'validation' => [
                    'nullable', 
                    'confirmed', 
                    Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()
                ],
                'slug' => 'password',
            ],
            [
                'name' => 'Confirm Password',
                'type' => 'Aura\\Base\\Fields\\Password',
                'validation' => ['required_with:form.fields.password', 'same:form.fields.password'],
                'slug' => 'password_confirmation',
            ],

            // 2FA Tab
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => '2FA',
                'slug' => '2fa',
                'global' => true,
            ],
            [
                'name' => 'Two Factor Authentication',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'user-2fa',
            ],
            [
                'name' => '2FA',
                'type' => 'Aura\\Base\\Fields\\LivewireComponent',
                'component' => 'aura::two-factor-authentication-form',
                'slug' => '2fa',
            ],

            // Delete Tab
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Delete',
                'slug' => 'delete-tab',
                'global' => true,
            ],
            [
                'name' => 'Delete Account',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'user-delete-panel',
            ],
            [
                'name' => 'Delete Account',
                'type' => 'Aura\\Base\\Fields\\View',
                'view' => 'aura::profile.delete-user-form',
                'slug' => 'delete-account',
            ],
        ];
    }
}
```

## Password Management

### Password Requirements

Passwords must meet the following requirements (enforced via Laravel's Password rule):

- Minimum 12 characters
- Mixed case (uppercase and lowercase)
- At least one number
- At least one symbol
- Not in compromised password databases (via Have I Been Pwned)

### Changing Password

To change their password, users must:

1. Enter their current password
2. Enter a new password meeting the requirements
3. Confirm the new password

When a password is successfully changed, other browser sessions are automatically logged out.

### Session Logout

The `logoutOtherBrowserSessions()` method is called after password changes:

```php
public function logoutOtherBrowserSessions()
{
    if (request()->hasSession() && Schema::hasTable('sessions')) {
        DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', Auth::user()->getAuthIdentifier())
            ->where('id', '!=', request()->session()->getId())
            ->delete();
    }
}
```

This requires the database session driver to be enabled.

## Two-Factor Authentication

Aura CMS includes two-factor authentication powered by Laravel Fortify. The 2FA component is `Aura\Base\Livewire\TwoFactorAuthenticationForm`.

### Enabling 2FA in Configuration

2FA can be toggled in `config/aura.php`:

```php
'auth' => [
    '2fa' => true,  // Enable/disable 2FA feature
],
```

### 2FA Flow

1. **Enable 2FA**: User clicks "Enable" and confirms their password
2. **Scan QR Code**: A QR code is displayed for authenticator apps (Google Authenticator, Authy, etc.)
3. **Enter Code**: User enters the code from their authenticator to confirm setup
4. **Recovery Codes**: 8 recovery codes are displayed and should be stored securely
5. **Disable 2FA**: User can disable 2FA by confirming their password

### 2FA Component

```php
namespace Aura\Base\Livewire;

use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Component;

class TwoFactorAuthenticationForm extends Component
{
    use ConfirmsPasswords;

    public $code;
    public $showingConfirmation = false;
    public $showingQrCode = false;
    public $showingRecoveryCodes = false;

    public function enableTwoFactorAuthentication(EnableTwoFactorAuthentication $enable)
    {
        $this->ensurePasswordIsConfirmed();
        $enable($this->user);
        $this->showingQrCode = true;
        $this->showingConfirmation = true;
    }

    public function confirmTwoFactorAuthentication(ConfirmTwoFactorAuthentication $confirm)
    {
        $this->ensurePasswordIsConfirmed();
        $confirm($this->user, $this->code);
        $this->showingQrCode = false;
        $this->showingConfirmation = false;
        $this->showingRecoveryCodes = true;
    }

    public function disableTwoFactorAuthentication(DisableTwoFactorAuthentication $disable)
    {
        $this->ensurePasswordIsConfirmed();
        $disable($this->user);
    }

    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generate)
    {
        $this->ensurePasswordIsConfirmed();
        $generate($this->user);
        $this->showingRecoveryCodes = true;
    }

    public function showRecoveryCodes()
    {
        $this->ensurePasswordIsConfirmed();
        $this->showingRecoveryCodes = true;
    }
}
```

### QR Code Display

The QR code is generated using Laravel Fortify's built-in method:

```blade
{!! $this->user->twoFactorQrCodeSvg() !!}
```

Users can also manually enter the setup key displayed below the QR code.

### Recovery Codes

8 recovery codes are provided when 2FA is enabled. Users should:
- Store codes in a secure password manager
- Each code can only be used once
- Regenerate codes if they've been used or compromised

## Account Deletion

Users can permanently delete their account from the Delete tab.

### Delete User Form

The delete form is a separate view (`aura::profile.delete-user-form`) that:

1. Warns the user about permanent data deletion
2. Requires password confirmation
3. Uses a confirmation dialog

### Deletion Process

```php
public function deleteUser(Request $request)
{
    $this->validate(['password' => ['required', 'current_password']]);

    $user = app(config('aura.resources.user'))::find(auth()->id());
    $user->delete();

    session()->invalidate();
    session()->regenerateToken();

    Auth::logout();

    return Redirect::to('/');
}
```

## Customizing the Profile

### Custom Profile Component

Create a custom profile component that extends the base:

```php
namespace App\Livewire;

use Aura\Base\Livewire\Profile as BaseProfile;

class CustomProfile extends BaseProfile
{
    public function getFields()
    {
        // Return custom fields
        return $this->model->getProfileFields();
    }

    public function save()
    {
        // Custom save logic
        parent::save();
        
        // Additional processing
    }
}
```

Register in `config/aura.php`:

```php
'components' => [
    'profile' => App\Livewire\CustomProfile::class,
],
```

### Custom Profile View

Publish and customize the profile view:

```bash
php artisan vendor:publish --tag=aura-views
```

Then edit `resources/views/vendor/aura/livewire/user/profile.blade.php`.

## Extending Profile Fields

### Override in User Resource

To customize profile fields, override the `getProfileFields()` method in your User resource:

```php
namespace App\Models;

use Aura\Base\Resources\User as BaseUser;
use Illuminate\Validation\Rules\Password;

class User extends BaseUser
{
    public function getProfileFields()
    {
        return [
            // Details Tab
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Details',
                'slug' => 'tab-user',
                'global' => true,
            ],
            [
                'name' => 'Personal Infos',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'user-details',
            ],
            [
                'name' => 'Avatar',
                'type' => 'Aura\\Base\\Fields\\Image',
                'slug' => 'avatar',
            ],
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'slug' => 'name',
            ],
            [
                'name' => 'Email',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required|email',
                'slug' => 'email',
            ],
            [
                'name' => 'Phone',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'nullable',
                'slug' => 'phone',
            ],
            [
                'name' => 'Bio',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'validation' => 'nullable|max:500',
                'slug' => 'bio',
            ],

            // Password Tab (inherit from parent or customize)
            ...parent::getPasswordTabFields(),

            // 2FA Tab (inherit from parent or customize)
            ...parent::getTwoFactorTabFields(),

            // Delete Tab (inherit from parent or customize)
            ...parent::getDeleteTabFields(),
        ];
    }
}
```

### Adding Custom Tabs

Add new tabs to the profile:

```php
public function getProfileFields()
{
    return array_merge(
        parent::getProfileFields(),
        [
            // Preferences Tab
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Preferences',
                'slug' => 'tab-preferences',
                'global' => true,
            ],
            [
                'name' => 'User Preferences',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'user-preferences',
            ],
            [
                'name' => 'Theme',
                'type' => 'Aura\\Base\\Fields\\Select',
                'options' => [
                    ['value' => 'light', 'label' => 'Light'],
                    ['value' => 'dark', 'label' => 'Dark'],
                    ['value' => 'auto', 'label' => 'Auto'],
                ],
                'slug' => 'theme_preference',
            ],
            [
                'name' => 'Timezone',
                'type' => 'Aura\\Base\\Fields\\Select',
                'options' => collect(timezone_identifiers_list())
                    ->map(fn($tz) => ['value' => $tz, 'label' => $tz])
                    ->toArray(),
                'slug' => 'timezone',
            ],
        ]
    );
}
```

---

For more information on authentication and security, see the [Authentication Guide](authentication.md). For information about user resources, see the [Resources Guide](resources.md).
