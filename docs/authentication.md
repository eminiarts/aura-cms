# Authentication in Aura CMS

Aura CMS provides a robust authentication system built on Laravel Fortify, offering secure user authentication, registration, and two-factor authentication capabilities.

## Table of Contents

- [Overview](#overview)
- [Global Admin](#global-admin)
- [Configuration](#configuration)
- [Features](#features)
- [Authentication Views](#authentication-views)
- [Two-Factor Authentication](#two-factor-authentication)
- [Team-Based Authentication](#team-based-authentication)
- [Customization](#customization)

<a name="overview"></a>
## Overview

Aura CMS's authentication system includes:

- User registration and login
- Password reset functionality
- Email verification
- Two-factor authentication
- Team-based authentication (optional)
- Remember me functionality
- API token authentication

<a name="global-admin"></a>
## Global Admin

Aura CMS includes a powerful Global Admin role that provides system-wide administrative capabilities. Global Admins are defined in your application's service provider using Laravel's Gate system, allowing you to implement any custom logic for determining admin access.

### Capabilities

Global Admins have unrestricted access across the entire system:
- View and manage all teams
- Impersonate other users
- Add or remove team members
- Create new teams regardless of system settings
- Delete any team
- Update team settings and permissions
- Invite users to any team

### Configuration

Define Global Admins in your service provider using the `AuraGlobalAdmin` Gate. You can implement any logic to determine who should have global admin access. Here's an example:

```php
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Add this Gate to your service provider [tl! highlight:8]
        Gate::define('AuraGlobalAdmin', function (User $user) {
            // Example: Check if user email is in an admin list
            return in_array($user->email, [
                'admin@example.com'
            ]);

            // Or use any other logic...
        });
    }
}
```

### Security Considerations

- Global Admin access should be granted sparingly and only to trusted administrators
- Consider implementing audit logging for sensitive operations
- Regularly review who has Global Admin access

<a name="configuration"></a>
## Configuration

Configure authentication settings in your `config/aura.php`:

```php
return [
    'auth' => [
        'registration' => env('AURA_REGISTRATION', true),
        'redirect' => '/admin',
        '2fa' => true,
        'user_invitations' => true,
        'create_teams' => true,
    ],
];
```

### Available Settings

- `registration`: Enable/disable user registration
- `redirect`: Post-login redirect path
- `2fa`: Enable/disable two-factor authentication
- `user_invitations`: Enable/disable team invitations
- `create_teams`: Allow users to create teams

<a name="features"></a>
## Features

### User Registration

When enabled, users can register through the `/register` route:

```php
// Enable/disable registration
'registration' => env('AURA_REGISTRATION', true),
```

The registration form includes:
- Name
- Email
- Password
- Team name (if teams are enabled)

### Login

The login system supports:
- Email/password authentication
- Remember me functionality
- Two-factor authentication challenge
- Rate limiting protection

```php
// Example login route
Route::get('login', [AuthenticatedSessionController::class, 'create'])
    ->name('aura.login');
```

### Password Reset

Built-in password reset functionality:
- Password reset request
- Reset token validation
- Secure password update
- Email notifications

```php
Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
    ->name('aura.password.request');
```

<a name="authentication-views"></a>
## Authentication Views

### Login View

The login view (`resources/views/vendor/aura/auth/login.blade.php`):

```blade
<x-dynamic-component :component="config('aura.views.login-layout')">
    <form method="POST" action="">
        @csrf
        <!-- Email -->
        <x-aura::input.text
            id="email"
            type="email"
            name="email"
            :value="old('email')"
            required
        />

        <!-- Password -->
        <x-aura::input.text
            id="password"
            type="password"
            name="password"
            required
        />

        <!-- Remember Me -->
        <label for="remember_me">
            <input id="remember_me" type="checkbox" name="remember">
            <span>{{ __('Remember me') }}</span>
        </label>
    </form>
</x-dynamic-component>
```

### Registration View

The registration view (`resources/views/vendor/aura/auth/register.blade.php`):

```blade
<x-aura::layout.guest>
    <form method="POST" action="{{ route('aura.register') }}">
        @csrf
        <!-- Name -->
        <x-aura::input.text
            id="name"
            type="text"
            name="name"
            required
        />

        <!-- Email -->
        <x-aura::input.text
            id="email"
            type="email"
            name="email"
            required
        />

        <!-- Password -->
        <x-aura::input.text
            id="password"
            type="password"
            name="password"
            required
        />
    </form>
</x-aura::layout.guest>
```

<a name="two-factor-authentication"></a>
## Two-Factor Authentication

### Enabling 2FA

Two-factor authentication can be enabled in the configuration:

```php
'2fa' => true,
```

### 2FA Features

- Time-based one-time passwords (TOTP)
- QR code for authenticator apps
- Recovery codes
- Remember device option

### 2FA Setup Process

1. User enables 2FA in profile settings
2. System generates secret key
3. User scans QR code with authenticator app
4. User confirms setup with code
5. Recovery codes are provided

```php
Route::post('/user/two-factor-authentication', [
    TwoFactorAuthenticationController::class,
    'store'
])->middleware(['auth', 'password.confirm']);
```

<a name="team-based-authentication"></a>
## Team-Based Authentication

When teams are enabled, authentication includes team-specific features:

### Team Registration

```php
if(config('aura.teams')) {
    Route::get('register/{team}/{teamInvitation}', [
        InvitationRegisterUserController::class,
        'create'
    ])->name('invitation.register');
}
```

### Team Invitations

```php
Route::get('/team-invitations/{invitation}', [
    TeamInvitationController::class,
    'accept'
])->middleware(['signed']);
```

<a name="customization"></a>
## Customization

### Custom Authentication Logic

Extend or modify authentication behavior in your `AuthServiceProvider`:

```php
class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Custom login view
        Fortify::loginView(function () {
            return view('custom.auth.login');
        });

        // Custom password reset
        ResetPassword::createUrlUsing(function ($user, $token) {
            return 'custom/reset-password?token='.$token;
        });
    }
}
```

### Custom Views

Publish and customize authentication views:

```bash
php artisan vendor:publish --tag=aura-auth-views
```


### Authentication Methods

```php
// User authentication
auth()->check();                    // Check if user is authenticated
auth()->user();                     // Get authenticated user
auth()->logout();                   // Log out user

// Team context
auth()->user()->currentTeam;        // Get current team
auth()->user()->switchTeam($team);  // Switch teams

// 2FA
auth()->user()->twoFactorEnabled(); // Check if 2FA is enabled
auth()->user()->enableTwoFactor();  // Enable 2FA
```
