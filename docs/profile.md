# User Profile & Account Management

Aura CMS provides a comprehensive user profile and account management system that allows users to manage their personal information, security settings, and preferences. Built with Laravel Fortify and enhanced with Livewire components, it offers a modern, reactive user experience.

## Table of Contents

- [Overview](#overview)
- [Profile Information](#profile-information)
- [Security Settings](#security-settings)
- [Two-Factor Authentication](#two-factor-authentication)
- [Browser Sessions](#browser-sessions)
- [Account Deletion](#account-deletion)
- [User Preferences](#user-preferences)
- [Profile Customization](#profile-customization)
- [API Tokens](#api-tokens)
- [Activity Log](#activity-log)
- [Best Practices](#best-practices)

## Overview

The user profile system in Aura CMS includes:
- **Personal Information**: Name, email, avatar, bio
- **Security**: Password management, 2FA, session control
- **Preferences**: UI settings, notifications, language
- **Activity Tracking**: Login history, actions log
- **API Access**: Personal access tokens for API usage

## Profile Information

### Basic Profile Management

The profile page is accessible at `/admin/profile` and includes:

```php
namespace App\Http\Livewire\Profile;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;

class UpdateProfileInformation extends Component
{
    use WithFileUploads;

    public $name;
    public $email;
    public $phone;
    public $bio;
    public $avatar;
    public $currentAvatar;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users,email',
        'phone' => 'nullable|string|max:20',
        'bio' => 'nullable|string|max:500',
        'avatar' => 'nullable|image|max:2048',
    ];

    public function mount()
    {
        $user = Auth::user();
        
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->bio = $user->bio;
        $this->currentAvatar = $user->avatar_url;
    }

    public function updateProfile()
    {
        $this->validate([
            'email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
        ]);

        $user = Auth::user();
        
        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'bio' => $this->bio,
        ]);

        if ($this->avatar) {
            $path = $this->avatar->store('avatars', 'public');
            $user->update(['avatar' => $path]);
            $this->currentAvatar = $user->avatar_url;
        }

        $this->emit('saved');
        $this->emit('refresh-navigation-menu');
    }

    public function deleteAvatar()
    {
        Auth::user()->update(['avatar' => null]);
        $this->currentAvatar = null;
        $this->emit('saved');
    }

    public function render()
    {
        return view('livewire.profile.update-profile-information');
    }
}
```

### Profile View

```blade
{{-- resources/views/livewire/profile/update-profile-information.blade.php --}}
<x-aura::form-section submit="updateProfile">
    <x-slot name="title">
        Profile Information
    </x-slot>

    <x-slot name="description">
        Update your account's profile information and email address.
    </x-slot>

    <x-slot name="form">
        <!-- Profile Photo -->
        <div class="col-span-6 sm:col-span-4">
            <x-aura::label for="photo" value="Photo" />

            <div class="mt-2 flex items-center gap-x-3">
                @if ($currentAvatar)
                    <img class="h-20 w-20 rounded-full object-cover" 
                         src="{{ $currentAvatar }}" 
                         alt="{{ $name }}">
                @else
                    <div class="h-20 w-20 rounded-full bg-gray-200 flex items-center justify-center">
                        <span class="text-2xl font-medium text-gray-600">
                            {{ substr($name, 0, 1) }}
                        </span>
                    </div>
                @endif

                <div class="flex gap-x-3">
                    <x-aura::secondary-button wire:click="$refs.avatarInput.click()">
                        Change
                    </x-aura::secondary-button>
                    
                    @if ($currentAvatar)
                        <x-aura::danger-button wire:click="deleteAvatar">
                            Remove
                        </x-aura::danger-button>
                    @endif
                </div>

                <input type="file" 
                       class="hidden" 
                       wire:model="avatar"
                       x-ref="avatarInput"
                       accept="image/*" />
            </div>

            <x-aura::input-error for="avatar" class="mt-2" />
        </div>

        <!-- Name -->
        <div class="col-span-6 sm:col-span-4">
            <x-aura::label for="name" value="Name" />
            <x-aura::input id="name" 
                          type="text" 
                          class="mt-1 block w-full" 
                          wire:model.defer="name" 
                          required />
            <x-aura::input-error for="name" class="mt-2" />
        </div>

        <!-- Email -->
        <div class="col-span-6 sm:col-span-4">
            <x-aura::label for="email" value="Email" />
            <x-aura::input id="email" 
                          type="email" 
                          class="mt-1 block w-full" 
                          wire:model.defer="email" 
                          required />
            <x-aura::input-error for="email" class="mt-2" />
        </div>

        <!-- Phone -->
        <div class="col-span-6 sm:col-span-4">
            <x-aura::label for="phone" value="Phone" />
            <x-aura::input id="phone" 
                          type="tel" 
                          class="mt-1 block w-full" 
                          wire:model.defer="phone" />
            <x-aura::input-error for="phone" class="mt-2" />
        </div>

        <!-- Bio -->
        <div class="col-span-6 sm:col-span-4">
            <x-aura::label for="bio" value="Bio" />
            <x-aura::textarea id="bio" 
                             class="mt-1 block w-full" 
                             wire:model.defer="bio" 
                             rows="4" />
            <x-aura::input-error for="bio" class="mt-2" />
            <p class="mt-2 text-sm text-gray-500">
                Brief description for your profile. Max 500 characters.
            </p>
        </div>
    </x-slot>

    <x-slot name="actions">
        <x-aura::action-message class="mr-3" on="saved">
            Saved.
        </x-aura::action-message>

        <x-aura::button>
            Save
        </x-aura::button>
    </x-slot>
</x-aura::form-section>
```

### Extended Profile Fields

Add custom fields to user profiles:

```php
// Migration
Schema::table('users', function (Blueprint $table) {
    $table->string('job_title')->nullable();
    $table->string('company')->nullable();
    $table->string('website')->nullable();
    $table->json('social_links')->nullable();
    $table->string('timezone')->default('UTC');
    $table->string('language')->default('en');
    $table->date('date_of_birth')->nullable();
});

// User model
class User extends Authenticatable
{
    protected $fillable = [
        'name', 'email', 'password', 'avatar',
        'job_title', 'company', 'website',
        'social_links', 'timezone', 'language',
        'date_of_birth', 'bio', 'phone',
    ];

    protected $casts = [
        'social_links' => 'array',
        'date_of_birth' => 'date',
    ];

    public function getAvatarUrlAttribute()
    {
        return $this->avatar 
            ? Storage::url($this->avatar) 
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->name);
    }
}
```

## Security Settings

### Password Update

```php
namespace App\Http\Livewire\Profile;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Rules\Password;

class UpdatePassword extends Component
{
    public $current_password = '';
    public $password = '';
    public $password_confirmation = '';

    protected function rules()
    {
        return [
            'current_password' => 'required|string|current_password',
            'password' => ['required', 'string', new Password, 'confirmed'],
        ];
    }

    public function updatePassword()
    {
        $this->validate();

        Auth::user()->update([
            'password' => Hash::make($this->password),
        ]);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        $this->emit('saved');

        // Optional: Logout other devices
        // Auth::logoutOtherDevices($this->password);
    }

    public function render()
    {
        return view('livewire.profile.update-password');
    }
}
```

### Password Requirements

Configure password requirements in `config/fortify.php`:

```php
'features' => [
    Features::updatePasswords([
        'requireUppercase' => true,
        'requireNumeric' => true,
        'requireSpecialCharacter' => true,
        'minLength' => 8,
    ]),
],
```

## Two-Factor Authentication

### Enable 2FA Component

```php
namespace App\Http\Livewire\Profile;

use Livewire\Component;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;

class TwoFactorAuthentication extends Component
{
    public $showingQrCode = false;
    public $showingRecoveryCodes = false;
    public $code;

    public function enableTwoFactorAuthentication(EnableTwoFactorAuthentication $enable)
    {
        $enable(Auth::user());

        $this->showingQrCode = true;
        $this->showingRecoveryCodes = true;
    }

    public function confirmTwoFactorAuthentication()
    {
        $this->validate([
            'code' => 'required|string',
        ]);

        Auth::user()->confirmTwoFactorAuth($this->code);

        $this->showingQrCode = false;
        $this->emit('saved');
    }

    public function showRecoveryCodes()
    {
        $this->showingRecoveryCodes = true;
    }

    public function regenerateRecoveryCodes()
    {
        Auth::user()->regenerateRecoveryCodes();
        
        $this->showingRecoveryCodes = true;
    }

    public function disableTwoFactorAuthentication(DisableTwoFactorAuthentication $disable)
    {
        $disable(Auth::user());

        $this->showingQrCode = false;
        $this->showingRecoveryCodes = false;
    }

    public function render()
    {
        return view('livewire.profile.two-factor-authentication');
    }
}
```

### 2FA View

```blade
<div>
    @if (! Auth::user()->two_factor_secret)
        {{-- 2FA Not Enabled --}}
        <x-aura::confirms-password wire:then="enableTwoFactorAuthentication">
            <x-aura::button type="button" wire:loading.attr="disabled">
                Enable Two-Factor Authentication
            </x-aura::button>
        </x-aura::confirms-password>
    @else
        {{-- 2FA Enabled --}}
        @if ($showingQrCode)
            {{-- Show QR Code --}}
            <div class="mt-4">
                <p class="font-semibold">
                    Scan the following QR code using your authenticator application:
                </p>

                <div class="mt-4 p-2 inline-block bg-white">
                    {!! Auth::user()->twoFactorQrCodeSvg() !!}
                </div>

                <p class="mt-4">Or enter this code manually:</p>
                <p class="mt-2 font-mono text-sm bg-gray-100 p-2 rounded">
                    {{ decrypt(Auth::user()->two_factor_secret) }}
                </p>

                <div class="mt-4">
                    <x-aura::label for="code" value="Code" />
                    <x-aura::input id="code" 
                                  type="text" 
                                  name="code" 
                                  class="block mt-1 w-1/2" 
                                  inputmode="numeric" 
                                  autofocus 
                                  autocomplete="one-time-code"
                                  wire:model.defer="code"
                                  wire:keydown.enter="confirmTwoFactorAuthentication" />
                    <x-aura::input-error for="code" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-aura::button wire:click="confirmTwoFactorAuthentication">
                        Confirm
                    </x-aura::button>
                </div>
            </div>
        @endif

        @if ($showingRecoveryCodes)
            {{-- Show Recovery Codes --}}
            <div class="mt-4">
                <p class="font-semibold">
                    Store these recovery codes in a secure location:
                </p>

                <div class="grid gap-1 max-w-xl mt-4 px-4 py-4 font-mono text-sm bg-gray-100 rounded-lg">
                    @foreach (json_decode(decrypt(Auth::user()->two_factor_recovery_codes), true) as $code)
                        <div>{{ $code }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-4 flex items-center space-x-3">
            @if (! $showingRecoveryCodes)
                <x-aura::secondary-button wire:click="showRecoveryCodes">
                    Show Recovery Codes
                </x-aura::secondary-button>
            @else
                <x-aura::secondary-button wire:click="regenerateRecoveryCodes">
                    Regenerate Recovery Codes
                </x-aura::secondary-button>
            @endif

            <x-aura::confirms-password wire:then="disableTwoFactorAuthentication">
                <x-aura::danger-button wire:loading.attr="disabled">
                    Disable Two-Factor Authentication
                </x-aura::danger-button>
            </x-aura::confirms-password>
        </div>
    @endif
</div>
```

## Browser Sessions

### Manage Browser Sessions

```php
namespace App\Http\Livewire\Profile;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Jenssegers\Agent\Agent;

class BrowserSessions extends Component
{
    public $password = '';

    public function getSessionsProperty()
    {
        if (config('session.driver') !== 'database') {
            return collect();
        }

        return DB::table('sessions')
            ->where('user_id', Auth::user()->getAuthIdentifier())
            ->orderBy('last_activity', 'desc')
            ->get()->map(function ($session) {
                $agent = new Agent();
                $agent->setUserAgent($session->user_agent);

                return (object) [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'is_current_device' => $session->id === request()->session()->getId(),
                    'agent' => [
                        'is_desktop' => $agent->isDesktop(),
                        'platform' => $agent->platform(),
                        'browser' => $agent->browser(),
                    ],
                    'last_active' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                ];
            });
    }

    public function logoutOtherBrowserSessions()
    {
        $this->validate([
            'password' => 'required|string|current_password',
        ]);

        Auth::user()->logoutOtherDevices($this->password);

        $this->reset('password');

        $this->emit('loggedOut');
    }

    public function logoutSingleSession($sessionId)
    {
        if (config('session.driver') === 'database') {
            DB::table('sessions')
                ->where('id', $sessionId)
                ->where('user_id', Auth::user()->getAuthIdentifier())
                ->delete();
        }

        $this->emit('loggedOut');
    }

    public function render()
    {
        return view('livewire.profile.browser-sessions');
    }
}
```

## Account Deletion

### Delete Account Component

```php
namespace App\Http\Livewire\Profile;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\Contracts\DeletesUsers;

class DeleteAccount extends Component
{
    public $password = '';
    public $confirmingUserDeletion = false;

    public function confirmUserDeletion()
    {
        $this->resetErrorBag();
        $this->password = '';
        $this->confirmingUserDeletion = true;
    }

    public function deleteUser(DeletesUsers $deleter)
    {
        $this->validate([
            'password' => 'required|string|current_password',
        ]);

        $user = Auth::user();

        // Perform any cleanup tasks
        $this->cleanupUserData($user);

        $deleter->delete($user);

        Auth::logout();

        return redirect('/');
    }

    protected function cleanupUserData($user)
    {
        // Delete user's uploaded files
        Storage::deleteDirectory('users/' . $user->id);

        // Anonymize user's content (optional)
        Post::where('author_id', $user->id)->update([
            'author_id' => null,
            'author_name' => 'Deleted User',
        ]);

        // Cancel subscriptions
        if ($user->subscribed()) {
            $user->subscription()->cancel();
        }

        // Log account deletion
        activity()
            ->causedBy($user)
            ->withProperties(['reason' => 'User requested'])
            ->log('Account deleted');
    }

    public function render()
    {
        return view('livewire.profile.delete-account');
    }
}
```

## User Preferences

### Preferences Management

```php
namespace App\Http\Livewire\Profile;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class UserPreferences extends Component
{
    public $theme = 'light';
    public $language = 'en';
    public $timezone = 'UTC';
    public $dateFormat = 'Y-m-d';
    public $timeFormat = '24h';
    public $firstDayOfWeek = 'monday';
    
    // Notification preferences
    public $emailNotifications = true;
    public $browserNotifications = false;
    public $smsNotifications = false;
    
    // Privacy preferences
    public $showProfile = true;
    public $showEmail = false;
    public $showActivity = true;

    protected $rules = [
        'theme' => 'required|in:light,dark,auto',
        'language' => 'required|string',
        'timezone' => 'required|timezone',
        'dateFormat' => 'required|string',
        'timeFormat' => 'required|in:12h,24h',
        'firstDayOfWeek' => 'required|in:sunday,monday',
    ];

    public function mount()
    {
        $user = Auth::user();
        $preferences = $user->preferences ?? [];

        $this->theme = $preferences['theme'] ?? 'light';
        $this->language = $preferences['language'] ?? 'en';
        $this->timezone = $preferences['timezone'] ?? 'UTC';
        $this->dateFormat = $preferences['dateFormat'] ?? 'Y-m-d';
        $this->timeFormat = $preferences['timeFormat'] ?? '24h';
        $this->firstDayOfWeek = $preferences['firstDayOfWeek'] ?? 'monday';
        
        $this->emailNotifications = $preferences['notifications']['email'] ?? true;
        $this->browserNotifications = $preferences['notifications']['browser'] ?? false;
        $this->smsNotifications = $preferences['notifications']['sms'] ?? false;
        
        $this->showProfile = $preferences['privacy']['showProfile'] ?? true;
        $this->showEmail = $preferences['privacy']['showEmail'] ?? false;
        $this->showActivity = $preferences['privacy']['showActivity'] ?? true;
    }

    public function updatePreferences()
    {
        $this->validate();

        $preferences = [
            'theme' => $this->theme,
            'language' => $this->language,
            'timezone' => $this->timezone,
            'dateFormat' => $this->dateFormat,
            'timeFormat' => $this->timeFormat,
            'firstDayOfWeek' => $this->firstDayOfWeek,
            'notifications' => [
                'email' => $this->emailNotifications,
                'browser' => $this->browserNotifications,
                'sms' => $this->smsNotifications,
            ],
            'privacy' => [
                'showProfile' => $this->showProfile,
                'showEmail' => $this->showEmail,
                'showActivity' => $this->showActivity,
            ],
        ];

        Auth::user()->update(['preferences' => $preferences]);

        // Update session
        session(['theme' => $this->theme]);
        session(['locale' => $this->language]);

        $this->emit('preferencesUpdated');
        $this->emit('saved');
    }

    public function render()
    {
        return view('livewire.profile.user-preferences');
    }
}
```

### Preferences View

```blade
<x-aura::form-section submit="updatePreferences">
    <x-slot name="title">
        Preferences
    </x-slot>

    <x-slot name="description">
        Customize your experience and manage your preferences.
    </x-slot>

    <x-slot name="form">
        {{-- Appearance --}}
        <div class="col-span-6">
            <h3 class="text-lg font-medium text-gray-900">Appearance</h3>
        </div>

        <div class="col-span-6 sm:col-span-4">
            <x-aura::label for="theme" value="Theme" />
            <x-aura::select id="theme" wire:model="theme" class="mt-1 block w-full">
                <option value="light">Light</option>
                <option value="dark">Dark</option>
                <option value="auto">Auto (System)</option>
            </x-aura::select>
        </div>

        {{-- Localization --}}
        <div class="col-span-6 mt-6">
            <h3 class="text-lg font-medium text-gray-900">Localization</h3>
        </div>

        <div class="col-span-6 sm:col-span-4">
            <x-aura::label for="language" value="Language" />
            <x-aura::select id="language" wire:model="language" class="mt-1 block w-full">
                <option value="en">English</option>
                <option value="es">Español</option>
                <option value="fr">Français</option>
                <option value="de">Deutsch</option>
            </x-aura::select>
        </div>

        <div class="col-span-6 sm:col-span-4">
            <x-aura::label for="timezone" value="Timezone" />
            <x-aura::select id="timezone" wire:model="timezone" class="mt-1 block w-full">
                @foreach(timezone_identifiers_list() as $tz)
                    <option value="{{ $tz }}">{{ $tz }}</option>
                @endforeach
            </x-aura::select>
        </div>

        {{-- Notifications --}}
        <div class="col-span-6 mt-6">
            <h3 class="text-lg font-medium text-gray-900">Notifications</h3>
        </div>

        <div class="col-span-6">
            <div class="space-y-4">
                <x-aura::toggle wire:model="emailNotifications">
                    <x-slot name="label">Email Notifications</x-slot>
                    <x-slot name="description">Receive notifications via email</x-slot>
                </x-aura::toggle>

                <x-aura::toggle wire:model="browserNotifications">
                    <x-slot name="label">Browser Notifications</x-slot>
                    <x-slot name="description">Receive push notifications in your browser</x-slot>
                </x-aura::toggle>

                <x-aura::toggle wire:model="smsNotifications">
                    <x-slot name="label">SMS Notifications</x-slot>
                    <x-slot name="description">Receive text message notifications</x-slot>
                </x-aura::toggle>
            </div>
        </div>

        {{-- Privacy --}}
        <div class="col-span-6 mt-6">
            <h3 class="text-lg font-medium text-gray-900">Privacy</h3>
        </div>

        <div class="col-span-6">
            <div class="space-y-4">
                <x-aura::toggle wire:model="showProfile">
                    <x-slot name="label">Public Profile</x-slot>
                    <x-slot name="description">Make your profile visible to other users</x-slot>
                </x-aura::toggle>

                <x-aura::toggle wire:model="showEmail">
                    <x-slot name="label">Show Email</x-slot>
                    <x-slot name="description">Display your email on your public profile</x-slot>
                </x-aura::toggle>

                <x-aura::toggle wire:model="showActivity">
                    <x-slot name="label">Activity Status</x-slot>
                    <x-slot name="description">Show when you're online</x-slot>
                </x-aura::toggle>
            </div>
        </div>
    </x-slot>

    <x-slot name="actions">
        <x-aura::action-message class="mr-3" on="saved">
            Saved.
        </x-aura::action-message>

        <x-aura::button>
            Save Preferences
        </x-aura::button>
    </x-slot>
</x-aura::form-section>
```

## Profile Customization

### Custom Profile Sections

Add custom sections to user profiles:

```php
// Register custom profile sections
class ProfileServiceProvider extends ServiceProvider
{
    public function boot()
    {
        ProfileManager::registerSection('social', [
            'title' => 'Social Media',
            'component' => 'profile.social-links',
            'order' => 50,
        ]);

        ProfileManager::registerSection('developer', [
            'title' => 'Developer Settings',
            'component' => 'profile.developer-settings',
            'order' => 60,
            'permission' => 'access-developer-settings',
        ]);
    }
}
```

### Social Links Component

```php
namespace App\Http\Livewire\Profile;

use Livewire\Component;

class SocialLinks extends Component
{
    public $twitter = '';
    public $github = '';
    public $linkedin = '';
    public $website = '';

    protected $rules = [
        'twitter' => 'nullable|string|max:255',
        'github' => 'nullable|string|max:255',
        'linkedin' => 'nullable|string|max:255',
        'website' => 'nullable|url|max:255',
    ];

    public function mount()
    {
        $links = Auth::user()->social_links ?? [];
        
        $this->twitter = $links['twitter'] ?? '';
        $this->github = $links['github'] ?? '';
        $this->linkedin = $links['linkedin'] ?? '';
        $this->website = $links['website'] ?? '';
    }

    public function updateSocialLinks()
    {
        $this->validate();

        Auth::user()->update([
            'social_links' => [
                'twitter' => $this->twitter,
                'github' => $this->github,
                'linkedin' => $this->linkedin,
                'website' => $this->website,
            ],
        ]);

        $this->emit('saved');
    }

    public function render()
    {
        return view('livewire.profile.social-links');
    }
}
```

## API Tokens

### Personal Access Tokens

```php
namespace App\Http\Livewire\Profile;

use Livewire\Component;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokens extends Component
{
    public $showingTokenValue = false;
    public $tokenValue;
    public $tokenName = '';
    public $tokenAbilities = ['read'];

    protected $rules = [
        'tokenName' => 'required|string|max:255',
        'tokenAbilities' => 'required|array|min:1',
    ];

    public function createToken()
    {
        $this->validate();

        $token = Auth::user()->createToken(
            $this->tokenName,
            $this->tokenAbilities
        );

        $this->tokenValue = $token->plainTextToken;
        $this->showingTokenValue = true;
        $this->reset(['tokenName', 'tokenAbilities']);
    }

    public function deleteToken($tokenId)
    {
        Auth::user()->tokens()->where('id', $tokenId)->delete();
        $this->emit('tokenDeleted');
    }

    public function render()
    {
        return view('livewire.profile.api-tokens', [
            'tokens' => Auth::user()->tokens,
        ]);
    }
}
```

## Activity Log

### User Activity Tracking

```php
namespace App\Http\Livewire\Profile;

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

class ActivityLog extends Component
{
    use WithPagination;

    public $filter = 'all';
    public $dateRange = '7days';

    public function getActivityProperty()
    {
        $query = Activity::where('causer_id', Auth::id())
            ->where('causer_type', User::class);

        // Apply filters
        if ($this->filter !== 'all') {
            $query->where('log_name', $this->filter);
        }

        // Apply date range
        $startDate = match($this->dateRange) {
            '24hours' => now()->subDay(),
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            'all' => null,
        };

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        return $query->latest()->paginate(20);
    }

    public function render()
    {
        return view('livewire.profile.activity-log', [
            'activities' => $this->activity,
        ]);
    }
}
```

### Activity Log View

```blade
<div>
    <div class="mb-4 flex justify-between">
        <div class="flex space-x-4">
            <x-aura::select wire:model="filter">
                <option value="all">All Activities</option>
                <option value="auth">Authentication</option>
                <option value="resource">Resources</option>
                <option value="settings">Settings</option>
            </x-aura::select>

            <x-aura::select wire:model="dateRange">
                <option value="24hours">Last 24 Hours</option>
                <option value="7days">Last 7 Days</option>
                <option value="30days">Last 30 Days</option>
                <option value="all">All Time</option>
            </x-aura::select>
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <ul class="divide-y divide-gray-200">
            @foreach($activities as $activity)
                <li class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                {{ $activity->description }}
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ $activity->created_at->diffForHumans() }}
                                @if($activity->properties['ip'] ?? false)
                                    " IP: {{ $activity->properties['ip'] }}
                                @endif
                            </p>
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ $activity->log_name }}
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>

    {{ $activities->links() }}
</div>
```

## Best Practices

### 1. Security First

```php
// Always confirm password for sensitive actions
public function performSensitiveAction()
{
    $this->authorize('update', Auth::user());
    
    // Use Laravel's built-in password confirmation
    $this->ensurePasswordIsConfirmed();
    
    // Perform action
}

// Rate limit profile updates
Route::middleware(['throttle:profile'])->group(function () {
    Route::post('/profile/update', [ProfileController::class, 'update']);
});
```

### 2. Data Validation

```php
// Comprehensive validation rules
protected function rules()
{
    return [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . Auth::id(),
        'phone' => ['nullable', 'string', 'regex:/^([0-9\s\-\+\(\)]*)$/', 'min:10'],
        'website' => 'nullable|url',
        'bio' => 'nullable|string|max:500|not_regex:/<[^>]*>/', // No HTML
    ];
}
```

### 3. Privacy Controls

```php
// Implement granular privacy settings
class PrivacySettings
{
    public static function canViewProfile($user, $viewer = null)
    {
        if (!$viewer) {
            return $user->preferences['privacy']['showProfile'] ?? true;
        }

        if ($viewer->id === $user->id) {
            return true;
        }

        if ($user->preferences['privacy']['showProfile'] ?? true) {
            return true;
        }

        return $viewer->can('view-private-profiles');
    }
}
```

### 4. Activity Logging

```php
// Log all profile changes
class ProfileObserver
{
    public function updated(User $user)
    {
        $changes = $user->getChanges();
        unset($changes['updated_at']);

        if (!empty($changes)) {
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties(['changes' => $changes])
                ->log('Profile updated');
        }
    }
}
```

### 5. Testing Profile Features

```php
class ProfileTest extends TestCase
{
    public function test_user_can_update_profile()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(UpdateProfileInformation::class)
            ->set('name', 'New Name')
            ->set('email', 'newemail@example.com')
            ->call('updateProfile')
            ->assertEmitted('saved');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'newemail@example.com',
        ]);
    }

    public function test_user_cannot_use_taken_email()
    {
        $existingUser = User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(UpdateProfileInformation::class)
            ->set('email', 'taken@example.com')
            ->call('updateProfile')
            ->assertHasErrors(['email']);
    }
}
```

## Troubleshooting

### Common Issues

1. **Avatar upload failing**
   - Check file permissions on storage directory
   - Verify upload_max_filesize in PHP configuration
   - Ensure storage disk is properly configured

2. **2FA QR code not showing**
   - Install required packages: `composer require bacon/bacon-qr-code`
   - Check if GD or Imagick extension is installed

3. **Session management not working**
   - Ensure session driver is set to 'database'
   - Run session table migration
   - Check session configuration

### Debug Commands

```bash
# Clear user sessions
php artisan session:clear user@example.com

# Reset 2FA for user
php artisan user:reset-2fa user@example.com

# Export user data
php artisan user:export user@example.com
```

---

For more information on authentication and security, see the [Authentication Guide](authentication.md).