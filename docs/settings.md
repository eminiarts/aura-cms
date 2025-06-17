# Settings in Aura CMS

Aura CMS provides a flexible settings system that allows you to manage both system-wide configurations and user-specific preferences. The settings system is built on Laravel's configuration system and enhanced with a database-backed settings store for runtime modifications.

## Table of Contents

- [Overview](#overview)
- [System Settings](#system-settings)
- [User Settings](#user-settings)
- [Settings UI](#settings-ui)
- [Configuration Files](#configuration-files)
- [Database Settings](#database-settings)
- [Settings API](#settings-api)
- [Extending Settings](#extending-settings)
- [Caching](#caching)
- [Best Practices](#best-practices)

## Overview

Aura CMS settings are organized into three layers:
1. **Configuration Files**: Static settings in `config/` directory
2. **Database Settings**: Dynamic settings stored in database
3. **User Preferences**: Per-user customizations

## System Settings

### Core Configuration

The main configuration file is located at `config/aura.php`:

```php
return [
    // Application Settings
    'name' => env('AURA_NAME', 'Aura CMS'),
    'version' => '1.0.0',
    'url' => env('APP_URL', 'http://localhost'),
    
    // Feature Flags
    'features' => [
        'teams' => env('AURA_TEAMS', false),
        'api' => env('AURA_API', true),
        'media_library' => true,
        'global_search' => true,
        'notifications' => true,
        'two_factor_auth' => true,
        'custom_tables_for_resources' => false,
        'resource_editor' => true,
    ],
    
    // Authentication
    'auth' => [
        'login_type' => env('AURA_LOGIN_TYPE', 'email'), // email, username, both
        'registration' => env('AURA_REGISTRATION', true),
        'password_reset' => true,
        'email_verification' => false,
        'session_lifetime' => 120, // minutes
    ],
    
    // Teams Configuration
    'teams' => [
        'enabled' => env('AURA_TEAMS', false),
        'model' => \App\Models\Team::class,
        'foreign_key' => 'team_id',
        'user_model' => \App\Models\User::class,
        'membership_model' => \App\Models\Membership::class,
        'create_personal_team' => true,
        'invitations' => true,
        'invitation_expiry' => 7, // days
    ],
    
    // Media Settings
    'media' => [
        'disk' => env('AURA_MEDIA_DISK', 'public'),
        'path' => 'media',
        'max_file_size' => 10240, // KB
        'allowed_extensions' => [
            'images' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'],
            'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
            'videos' => ['mp4', 'avi', 'mov', 'wmv'],
        ],
        'image_driver' => 'gd', // gd or imagick
        'thumbnails' => [
            'small' => [150, 150],
            'medium' => [300, 300],
            'large' => [600, 600],
        ],
    ],
    
    // Resource Settings
    'resources' => [
        'per_page' => 25,
        'search_throttle' => 300, // milliseconds
        'cache_ttl' => 3600, // seconds
        'soft_deletes' => true,
        'versioning' => false,
    ],
    
    // UI Settings
    'ui' => [
        'theme' => 'default',
        'dark_mode' => true,
        'sidebar_position' => 'left',
        'sidebar_collapsed' => false,
        'breadcrumbs' => true,
        'animations' => true,
    ],
    
    // API Settings
    'api' => [
        'enabled' => env('AURA_API_ENABLED', true),
        'prefix' => 'api',
        'version' => 'v1',
        'rate_limit' => 60, // requests per minute
        'authentication' => 'sanctum', // sanctum or passport
    ],
];
```

### Environment Variables

Key environment variables for Aura CMS:

```env
# Aura CMS Settings
AURA_NAME="My Aura Site"
AURA_TEAMS=false
AURA_LOGIN_TYPE=email
AURA_REGISTRATION=true
AURA_API_ENABLED=true
AURA_MEDIA_DISK=public

# Feature Flags
AURA_FEATURE_RESOURCE_EDITOR=true
AURA_FEATURE_CUSTOM_TABLES=false
AURA_FEATURE_FLOWS=false

# Performance
AURA_CACHE_ENABLED=true
AURA_CACHE_DRIVER=redis
AURA_QUEUE_CONNECTION=redis
```

## User Settings

### User Preferences Model

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    protected $fillable = ['user_id', 'key', 'value'];
    
    protected $casts = [
        'value' => 'json',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### User Settings Trait

```php
namespace App\Traits;

trait HasSettings
{
    public function settings()
    {
        return $this->hasMany(UserSetting::class);
    }
    
    public function getSetting($key, $default = null)
    {
        $setting = $this->settings()->where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
    
    public function setSetting($key, $value)
    {
        return $this->settings()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
    
    public function deleteSetting($key)
    {
        return $this->settings()->where('key', $key)->delete();
    }
    
    public function getSettingsAttribute()
    {
        return $this->settings()->pluck('value', 'key');
    }
}
```

### Common User Settings

```php
// UI Preferences
$user->setSetting('theme', 'dark');
$user->setSetting('sidebar_collapsed', true);
$user->setSetting('language', 'en');
$user->setSetting('timezone', 'America/New_York');

// Notification Preferences
$user->setSetting('notifications', [
    'email' => true,
    'browser' => false,
    'slack' => false,
    'types' => [
        'resource_created' => true,
        'resource_updated' => false,
        'comments' => true,
    ]
]);

// Table Preferences
$user->setSetting('table_preferences', [
    'articles' => [
        'per_page' => 50,
        'columns' => ['title', 'author', 'status', 'created_at'],
        'sort' => ['created_at', 'desc'],
    ]
]);
```

## Settings UI

### Settings Page Component

```php
namespace App\Http\Livewire;

use Livewire\Component;
use Aura\Base\Settings\SettingsManager;

class Settings extends Component
{
    public $activeTab = 'general';
    
    // General Settings
    public $siteName;
    public $siteUrl;
    public $siteDescription;
    public $adminEmail;
    
    // Features
    public $enableTeams;
    public $enableApi;
    public $enableRegistration;
    public $enableTwoFactor;
    
    // Media Settings
    public $mediaDisk;
    public $maxFileSize;
    public $allowedExtensions;
    
    // Mail Settings
    public $mailDriver;
    public $mailHost;
    public $mailPort;
    public $mailUsername;
    public $mailFromAddress;
    public $mailFromName;
    
    protected $rules = [
        'siteName' => 'required|string|max:255',
        'siteUrl' => 'required|url',
        'adminEmail' => 'required|email',
        'maxFileSize' => 'required|integer|min:1',
    ];
    
    public function mount()
    {
        $this->loadSettings();
    }
    
    public function loadSettings()
    {
        $settings = SettingsManager::all();
        
        // General
        $this->siteName = $settings->get('site.name', config('app.name'));
        $this->siteUrl = $settings->get('site.url', config('app.url'));
        $this->siteDescription = $settings->get('site.description');
        $this->adminEmail = $settings->get('site.admin_email');
        
        // Features
        $this->enableTeams = $settings->get('features.teams', false);
        $this->enableApi = $settings->get('features.api', true);
        $this->enableRegistration = $settings->get('features.registration', true);
        $this->enableTwoFactor = $settings->get('features.two_factor', true);
        
        // Media
        $this->mediaDisk = $settings->get('media.disk', 'public');
        $this->maxFileSize = $settings->get('media.max_file_size', 10240);
        $this->allowedExtensions = $settings->get('media.allowed_extensions', []);
        
        // Mail
        $this->mailDriver = config('mail.default');
        $this->mailHost = config('mail.mailers.smtp.host');
        $this->mailPort = config('mail.mailers.smtp.port');
        $this->mailUsername = config('mail.mailers.smtp.username');
        $this->mailFromAddress = config('mail.from.address');
        $this->mailFromName = config('mail.from.name');
    }
    
    public function saveGeneral()
    {
        $this->validate([
            'siteName' => 'required|string|max:255',
            'siteUrl' => 'required|url',
            'adminEmail' => 'required|email',
        ]);
        
        SettingsManager::set('site.name', $this->siteName);
        SettingsManager::set('site.url', $this->siteUrl);
        SettingsManager::set('site.description', $this->siteDescription);
        SettingsManager::set('site.admin_email', $this->adminEmail);
        
        $this->notify('General settings saved successfully!');
    }
    
    public function saveFeatures()
    {
        SettingsManager::set('features.teams', $this->enableTeams);
        SettingsManager::set('features.api', $this->enableApi);
        SettingsManager::set('features.registration', $this->enableRegistration);
        SettingsManager::set('features.two_factor', $this->enableTwoFactor);
        
        // Update config cache
        $this->updateConfigCache();
        
        $this->notify('Feature settings saved successfully!');
    }
    
    public function saveMail()
    {
        $this->validate([
            'mailFromAddress' => 'required|email',
            'mailFromName' => 'required|string',
        ]);
        
        // Update .env file
        $this->updateEnvironmentFile([
            'MAIL_MAILER' => $this->mailDriver,
            'MAIL_HOST' => $this->mailHost,
            'MAIL_PORT' => $this->mailPort,
            'MAIL_USERNAME' => $this->mailUsername,
            'MAIL_FROM_ADDRESS' => $this->mailFromAddress,
            'MAIL_FROM_NAME' => $this->mailFromName,
        ]);
        
        $this->notify('Mail settings saved. Please restart your application for changes to take effect.');
    }
    
    protected function updateConfigCache()
    {
        Artisan::call('config:cache');
        Artisan::call('cache:clear');
    }
    
    protected function updateEnvironmentFile($data)
    {
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);
        
        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";
            
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }
        
        file_put_contents($envPath, $envContent);
    }
    
    public function render()
    {
        return view('livewire.settings');
    }
}
```

### Settings View

```blade
{{-- resources/views/livewire/settings.blade.php --}}
<div>
    <x-aura::heading>
        System Settings
    </x-aura::heading>

    <div class="mt-6">
        {{-- Tabs --}}
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button wire:click="$set('activeTab', 'general')"
                        class="@if($activeTab === 'general') border-indigo-500 text-indigo-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    General
                </button>
                <button wire:click="$set('activeTab', 'features')"
                        class="@if($activeTab === 'features') border-indigo-500 text-indigo-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Features
                </button>
                <button wire:click="$set('activeTab', 'media')"
                        class="@if($activeTab === 'media') border-indigo-500 text-indigo-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Media
                </button>
                <button wire:click="$set('activeTab', 'mail')"
                        class="@if($activeTab === 'mail') border-indigo-500 text-indigo-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Mail
                </button>
                <button wire:click="$set('activeTab', 'advanced')"
                        class="@if($activeTab === 'advanced') border-indigo-500 text-indigo-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Advanced
                </button>
            </nav>
        </div>

        {{-- Tab Content --}}
        <div class="mt-6">
            @if($activeTab === 'general')
                <form wire:submit.prevent="saveGeneral" class="space-y-6">
                    <x-aura::input.group label="Site Name" for="siteName" required>
                        <x-aura::input.text wire:model="siteName" id="siteName" />
                    </x-aura::input.group>

                    <x-aura::input.group label="Site URL" for="siteUrl" required>
                        <x-aura::input.text wire:model="siteUrl" id="siteUrl" type="url" />
                    </x-aura::input.group>

                    <x-aura::input.group label="Site Description" for="siteDescription">
                        <x-aura::input.textarea wire:model="siteDescription" id="siteDescription" rows="3" />
                    </x-aura::input.group>

                    <x-aura::input.group label="Admin Email" for="adminEmail" required>
                        <x-aura::input.email wire:model="adminEmail" id="adminEmail" />
                    </x-aura::input.group>

                    <div class="flex justify-end">
                        <x-aura::button type="submit">
                            Save General Settings
                        </x-aura::button>
                    </div>
                </form>
            @endif

            @if($activeTab === 'features')
                <form wire:submit.prevent="saveFeatures" class="space-y-6">
                    <div class="space-y-4">
                        <x-aura::input.toggle wire:model="enableTeams" label="Enable Teams">
                            <x-slot name="description">
                                Allow users to create and manage teams for multi-tenant functionality.
                            </x-slot>
                        </x-aura::input.toggle>

                        <x-aura::input.toggle wire:model="enableApi" label="Enable API">
                            <x-slot name="description">
                                Enable REST API endpoints for external integrations.
                            </x-slot>
                        </x-aura::input.toggle>

                        <x-aura::input.toggle wire:model="enableRegistration" label="Enable Registration">
                            <x-slot name="description">
                                Allow new users to register for accounts.
                            </x-slot>
                        </x-aura::input.toggle>

                        <x-aura::input.toggle wire:model="enableTwoFactor" label="Enable Two-Factor Authentication">
                            <x-slot name="description">
                                Allow users to enable two-factor authentication for enhanced security.
                            </x-slot>
                        </x-aura::input.toggle>
                    </div>

                    <div class="flex justify-end">
                        <x-aura::button type="submit">
                            Save Feature Settings
                        </x-aura::button>
                    </div>
                </form>
            @endif

            @if($activeTab === 'media')
                <form wire:submit.prevent="saveMedia" class="space-y-6">
                    <x-aura::input.group label="Storage Disk" for="mediaDisk">
                        <x-aura::input.select wire:model="mediaDisk" id="mediaDisk">
                            <option value="local">Local</option>
                            <option value="public">Public</option>
                            <option value="s3">Amazon S3</option>
                        </x-aura::input.select>
                    </x-aura::input.group>

                    <x-aura::input.group label="Max File Size (KB)" for="maxFileSize">
                        <x-aura::input.number wire:model="maxFileSize" id="maxFileSize" min="1" />
                    </x-aura::input.group>

                    <div class="flex justify-end">
                        <x-aura::button type="submit">
                            Save Media Settings
                        </x-aura::button>
                    </div>
                </form>
            @endif

            @if($activeTab === 'mail')
                <form wire:submit.prevent="saveMail" class="space-y-6">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <x-aura::icon.exclamation class="h-5 w-5 text-yellow-400" />
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">
                                    Application restart required
                                </h3>
                                <p class="mt-2 text-sm text-yellow-700">
                                    Changing mail settings requires restarting your application for changes to take effect.
                                </p>
                            </div>
                        </div>
                    </div>

                    <x-aura::input.group label="Mail Driver" for="mailDriver">
                        <x-aura::input.select wire:model="mailDriver" id="mailDriver">
                            <option value="smtp">SMTP</option>
                            <option value="sendmail">Sendmail</option>
                            <option value="mailgun">Mailgun</option>
                            <option value="ses">Amazon SES</option>
                            <option value="postmark">Postmark</option>
                        </x-aura::input.select>
                    </x-aura::input.group>

                    @if($mailDriver === 'smtp')
                        <x-aura::input.group label="SMTP Host" for="mailHost">
                            <x-aura::input.text wire:model="mailHost" id="mailHost" />
                        </x-aura::input.group>

                        <x-aura::input.group label="SMTP Port" for="mailPort">
                            <x-aura::input.number wire:model="mailPort" id="mailPort" />
                        </x-aura::input.group>

                        <x-aura::input.group label="SMTP Username" for="mailUsername">
                            <x-aura::input.text wire:model="mailUsername" id="mailUsername" />
                        </x-aura::input.group>
                    @endif

                    <x-aura::input.group label="From Address" for="mailFromAddress" required>
                        <x-aura::input.email wire:model="mailFromAddress" id="mailFromAddress" />
                    </x-aura::input.group>

                    <x-aura::input.group label="From Name" for="mailFromName" required>
                        <x-aura::input.text wire:model="mailFromName" id="mailFromName" />
                    </x-aura::input.group>

                    <div class="flex justify-end">
                        <x-aura::button type="submit">
                            Save Mail Settings
                        </x-aura::button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
```

## Database Settings

### Settings Manager

```php
namespace Aura\Base\Settings;

use Illuminate\Support\Facades\Cache;
use App\Models\Setting;

class SettingsManager
{
    protected static $cache = [];
    protected static $cacheKey = 'aura.settings';
    
    public static function get($key, $default = null)
    {
        // Check memory cache first
        if (isset(static::$cache[$key])) {
            return static::$cache[$key];
        }
        
        // Check persistent cache
        $cached = Cache::get(static::$cacheKey);
        if ($cached && isset($cached[$key])) {
            static::$cache[$key] = $cached[$key];
            return $cached[$key];
        }
        
        // Load from database
        $setting = Setting::where('key', $key)->first();
        if ($setting) {
            static::$cache[$key] = $setting->value;
            static::saveCache();
            return $setting->value;
        }
        
        return $default;
    }
    
    public static function set($key, $value)
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
        
        static::$cache[$key] = $value;
        static::saveCache();
        
        return $value;
    }
    
    public static function forget($key)
    {
        Setting::where('key', $key)->delete();
        unset(static::$cache[$key]);
        static::saveCache();
    }
    
    public static function all()
    {
        if (empty(static::$cache)) {
            $settings = Setting::all()->pluck('value', 'key');
            static::$cache = $settings->toArray();
            static::saveCache();
        }
        
        return collect(static::$cache);
    }
    
    public static function flush()
    {
        static::$cache = [];
        Cache::forget(static::$cacheKey);
    }
    
    protected static function saveCache()
    {
        Cache::put(static::$cacheKey, static::$cache, now()->addHours(24));
    }
}
```

### Settings Model

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];
    
    protected $casts = [
        'value' => AsArrayObject::class,
    ];
    
    public function scopeGroup($query, $group)
    {
        return $query->where('key', 'like', $group . '.%');
    }
    
    public static function getGroup($group)
    {
        return static::group($group)
            ->get()
            ->mapWithKeys(function ($setting) use ($group) {
                $key = str_replace($group . '.', '', $setting->key);
                return [$key => $setting->value];
            });
    }
}
```

## Settings API

### Settings Controller

```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Aura\Base\Settings\SettingsManager;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Setting::class);
        
        $settings = SettingsManager::all();
        
        // Filter sensitive settings
        $filtered = $settings->filter(function ($value, $key) {
            return !str_starts_with($key, 'mail.') && 
                   !str_contains($key, 'secret') &&
                   !str_contains($key, 'password');
        });
        
        return response()->json([
            'data' => $filtered,
        ]);
    }
    
    public function show($key)
    {
        $this->authorize('view', Setting::class);
        
        $value = SettingsManager::get($key);
        
        if ($value === null) {
            return response()->json([
                'message' => 'Setting not found',
            ], 404);
        }
        
        return response()->json([
            'key' => $key,
            'value' => $value,
        ]);
    }
    
    public function update(Request $request, $key)
    {
        $this->authorize('update', Setting::class);
        
        $request->validate([
            'value' => 'required',
        ]);
        
        $value = SettingsManager::set($key, $request->value);
        
        return response()->json([
            'key' => $key,
            'value' => $value,
            'message' => 'Setting updated successfully',
        ]);
    }
    
    public function destroy($key)
    {
        $this->authorize('delete', Setting::class);
        
        SettingsManager::forget($key);
        
        return response()->json([
            'message' => 'Setting deleted successfully',
        ]);
    }
}
```

### JavaScript Settings Helper

```javascript
// resources/js/settings.js
window.AuraSettings = {
    cache: {},
    
    get(key, defaultValue = null) {
        if (this.cache[key] !== undefined) {
            return this.cache[key];
        }
        
        // Get from data attributes or API
        const element = document.querySelector(`[data-setting="${key}"]`);
        if (element) {
            const value = element.dataset.value;
            this.cache[key] = JSON.parse(value);
            return this.cache[key];
        }
        
        return defaultValue;
    },
    
    async fetch(key) {
        const response = await fetch(`/api/settings/${key}`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        
        if (response.ok) {
            const data = await response.json();
            this.cache[key] = data.value;
            return data.value;
        }
        
        return null;
    },
    
    async set(key, value) {
        const response = await fetch(`/api/settings/${key}`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ value }),
        });
        
        if (response.ok) {
            this.cache[key] = value;
            return true;
        }
        
        return false;
    },
};
```

## Extending Settings

### Custom Settings Page

```php
namespace App\Http\Livewire\Settings;

use Livewire\Component;

class CustomSettings extends Component
{
    public $settings = [];
    
    protected $rules = [
        'settings.*.value' => 'required',
    ];
    
    public function mount()
    {
        $this->settings = [
            [
                'key' => 'custom.feature_1',
                'label' => 'Feature 1',
                'type' => 'boolean',
                'value' => setting('custom.feature_1', false),
                'description' => 'Enable experimental feature 1',
            ],
            [
                'key' => 'custom.feature_2',
                'label' => 'Feature 2', 
                'type' => 'select',
                'value' => setting('custom.feature_2', 'option1'),
                'options' => [
                    'option1' => 'Option 1',
                    'option2' => 'Option 2',
                    'option3' => 'Option 3',
                ],
            ],
            [
                'key' => 'custom.api_key',
                'label' => 'API Key',
                'type' => 'text',
                'value' => setting('custom.api_key'),
                'masked' => true,
            ],
        ];
    }
    
    public function save()
    {
        $this->validate();
        
        foreach ($this->settings as $setting) {
            SettingsManager::set($setting['key'], $setting['value']);
        }
        
        $this->notify('Settings saved successfully!');
    }
    
    public function render()
    {
        return view('livewire.settings.custom-settings');
    }
}
```

### Settings Service Provider

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Aura\Base\Settings\SettingsManager;

class SettingsServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register settings singleton
        $this->app->singleton('settings', function () {
            return new SettingsManager();
        });
    }
    
    public function boot()
    {
        // Load settings into config
        if ($this->app->runningInConsole()) {
            return;
        }
        
        try {
            $settings = SettingsManager::all();
            
            foreach ($settings as $key => $value) {
                if (str_contains($key, '.')) {
                    config()->set($key, $value);
                }
            }
        } catch (\Exception $e) {
            // Database might not be migrated yet
        }
    }
}
```

## Caching

### Cache Configuration

```php
// config/aura-settings.php
return [
    'cache' => [
        'enabled' => env('AURA_SETTINGS_CACHE', true),
        'ttl' => env('AURA_SETTINGS_CACHE_TTL', 86400), // 24 hours
        'prefix' => 'aura_settings',
    ],
    
    'encryption' => [
        'enabled' => true,
        'keys' => [
            'mail.password',
            'services.*.secret',
            'services.*.key',
        ],
    ],
];
```

### Cache Commands

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Aura\Base\Settings\SettingsManager;

class RefreshSettingsCache extends Command
{
    protected $signature = 'aura:settings:cache';
    protected $description = 'Refresh the settings cache';
    
    public function handle()
    {
        $this->info('Refreshing settings cache...');
        
        SettingsManager::flush();
        $settings = SettingsManager::all();
        
        $this->info("Cached {$settings->count()} settings.");
    }
}
```

## Best Practices

### 1. Use Appropriate Storage

```php
// Use config files for:
// - Static settings that rarely change
// - Settings needed during bootstrap
// - Default values
config(['aura.feature.enabled' => true]);

// Use database settings for:
// - User-configurable settings
// - Dynamic values that change at runtime
// - Feature flags
SettingsManager::set('maintenance.message', 'Site under maintenance');

// Use user preferences for:
// - Personal customizations
// - UI preferences
// - Notification settings
$user->setSetting('theme', 'dark');
```

### 2. Validate Settings

```php
class UpdateSettingsRequest extends FormRequest
{
    public function rules()
    {
        return [
            'site_name' => 'required|string|max:255',
            'items_per_page' => 'required|integer|min:10|max:100',
            'cache_ttl' => 'required|integer|min:0',
            'allowed_ips' => 'nullable|array',
            'allowed_ips.*' => 'ip',
        ];
    }
}
```

### 3. Secure Sensitive Settings

```php
// Encrypt sensitive values
class Setting extends Model
{
    protected $encryptable = [
        'api_keys',
        'passwords',
        'secrets',
    ];
    
    public function setValueAttribute($value)
    {
        if ($this->shouldEncrypt($this->key)) {
            $value = encrypt($value);
        }
        
        $this->attributes['value'] = json_encode($value);
    }
    
    public function getValueAttribute($value)
    {
        $decoded = json_decode($value, true);
        
        if ($this->shouldEncrypt($this->key)) {
            $decoded = decrypt($decoded);
        }
        
        return $decoded;
    }
    
    protected function shouldEncrypt($key)
    {
        foreach ($this->encryptable as $pattern) {
            if (str_contains($key, $pattern)) {
                return true;
            }
        }
        
        return false;
    }
}
```

### 4. Version Control Settings

```php
// Track setting changes
class SettingObserver
{
    public function updated(Setting $setting)
    {
        activity()
            ->performedOn($setting)
            ->withProperties([
                'key' => $setting->key,
                'old_value' => $setting->getOriginal('value'),
                'new_value' => $setting->value,
            ])
            ->log('Setting updated');
    }
}
```

### 5. Test Settings

```php
class SettingsTest extends TestCase
{
    public function test_can_get_and_set_settings()
    {
        SettingsManager::set('test.key', 'value');
        
        $this->assertEquals('value', SettingsManager::get('test.key'));
    }
    
    public function test_returns_default_for_missing_setting()
    {
        $value = SettingsManager::get('missing.key', 'default');
        
        $this->assertEquals('default', $value);
    }
    
    public function test_encrypts_sensitive_settings()
    {
        $setting = Setting::create([
            'key' => 'mail.password',
            'value' => 'secret123',
        ]);
        
        $this->assertNotEquals('secret123', $setting->getRawOriginal('value'));
        $this->assertEquals('secret123', $setting->value);
    }
}
```

## Troubleshooting

### Common Issues

1. **Settings not updating**
   - Clear cache: `php artisan cache:clear`
   - Check permissions on storage directory
   - Verify database connection

2. **Performance issues**
   - Enable caching for settings
   - Use eager loading for user preferences
   - Index the settings table on 'key' column

3. **Missing settings**
   - Run migrations: `php artisan migrate`
   - Check for typos in setting keys
   - Verify default values in config files

### Debug Commands

```bash
# List all settings
php artisan tinker
>>> \Aura\Base\Settings\SettingsManager::all()

# Clear settings cache
php artisan aura:settings:cache

# Export settings
php artisan aura:settings:export

# Import settings
php artisan aura:settings:import settings.json
```

---

For more advanced configuration options, see the [Configuration Guide](configuration.md).