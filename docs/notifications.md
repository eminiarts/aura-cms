# Notifications in Aura CMS

Aura CMS provides two notification systems: **toast notifications** for instant user feedback and a **notification center** for persistent database notifications. These are built on Laravel's notification system and enhanced with Livewire and Alpine.js.

## Table of Contents

- [Overview](#overview)
- [Toast Notifications](#toast-notifications)
- [Notification Center](#notification-center)
- [Configuration](#configuration)
- [Creating Custom Notifications](#creating-custom-notifications)
- [Team Invitations](#team-invitations)
- [Best Practices](#best-practices)

## Overview

Aura CMS includes two types of notifications:

1. **Toast Notifications**: Temporary popup messages that appear in the top-right corner and auto-dismiss after 3 seconds. Used for instant feedback like "Saved successfully" or "Error occurred".

2. **Notification Center**: A slide-over panel accessible from the navigation that displays persistent database notifications with read/unread status.

## Toast Notifications

Toast notifications provide instant feedback to users. They're implemented using Alpine.js and are triggered via Livewire events.

### Using Toast Notifications in Livewire Components

Livewire components that use the `WithLivewireHelpers` trait have access to the `notify()` method:

```php
namespace App\Livewire;

use Livewire\Component;
use Aura\Base\Traits\WithLivewireHelpers;

class MyComponent extends Component
{
    use WithLivewireHelpers;

    public function save()
    {
        // Save logic...
        
        // Show success notification
        $this->notify('Successfully saved!');
        
        // Show error notification
        $this->notify('Something went wrong.', 'error');
    }
}
```

### Notification Types

The `notify()` method accepts two parameters:

```php
public function notify($message, $type = 'success')
```

| Type | Description | Icon |
|------|-------------|------|
| `success` | Success messages (default) | Green checkmark |
| `error` | Error messages | Red warning triangle |

### How It Works

The `notify()` method dispatches a browser event:

```php
$this->dispatchBrowserEvent('notify', [
    'message' => $message,
    'type' => $type,
]);
```

The `<x-aura::notification>` component listens for this event and displays the toast:

```blade
{{-- Automatically included in the main layout --}}
<x-aura::notification/>
```

### Toast Features

- **Auto-dismiss**: Toasts disappear after 3 seconds
- **Pause on hover**: Timer pauses when you hover over the notification
- **Progress indicator**: Visual progress bar shows remaining time
- **Manual dismiss**: Click the X button to dismiss immediately
- **Stacking**: Multiple notifications stack vertically

### Triggering Toasts from JavaScript

You can also trigger toast notifications directly from JavaScript/Alpine.js:

```javascript
// Dispatch the notify event
window.dispatchEvent(new CustomEvent('notify', {
    detail: {
        message: 'Operation completed!',
        type: 'success'
    }
}));
```

## Notification Center

The notification center displays persistent database notifications using Laravel's built-in notification system.

### Accessing the Notification Center

When enabled, a bell icon appears in the sidebar navigation. Clicking it opens a slide-over panel with two tabs:

- **Unread**: New notifications that haven't been read
- **Read**: Previously viewed notifications

### The Notifications Component

The notification center is powered by the `Aura\Base\Livewire\Notifications` component:

```php
// Registered as: aura::notifications
Livewire::component('aura::notifications', Notifications::class);
```

Key features:
- Displays unread and read notifications in separate tabs
- Shows notification icon based on resource type
- Links to the related resource
- "Mark all as read" functionality

### Notification Data Structure

For notifications to display correctly in the notification center, they should include:

```php
public function toDatabase($notifiable)
{
    return [
        'type' => 'App\\Resources\\Post',  // Resource class for icon
        'id' => $this->resource->id,        // Resource ID for linking
        'message' => 'Your post was published',
    ];
}
```

### Marking Notifications as Read

Users can mark all notifications as read with one click:

```php
// In Notifications.php
public function markAllAsRead()
{
    auth()->user()->unreadNotifications()->update(['read_at' => now()]);
}
```

## Configuration

Enable or disable the notification center in `config/aura.php`:

```php
'features' => [
    'notifications' => true,  // Enable/disable notification center
    // ... other features
],
```

When disabled:
- The bell icon is hidden from the navigation
- The notifications slide-over component is not loaded
- Toast notifications still work (they're always enabled)

## Creating Custom Notifications

### Using Laravel Notifications

Aura CMS users have the `Notifiable` trait, so you can use Laravel's notification system:

```php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ArticlePublished extends Notification implements ShouldQueue
{
    use Queueable;

    protected $article;

    public function __construct($article)
    {
        $this->article = $article;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your article has been published!')
            ->line('Congratulations! Your article "' . $this->article->title . '" is now live.')
            ->action('View Article', url('/articles/' . $this->article->slug))
            ->line('Thank you for contributing!');
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => get_class($this->article),
            'id' => $this->article->id,
            'message' => 'Your article "' . $this->article->title . '" was published',
        ];
    }
}
```

### Sending Notifications

```php
use App\Notifications\ArticlePublished;

// Send to a single user
$user->notify(new ArticlePublished($article));

// Send to multiple users
Notification::send($users, new ArticlePublished($article));
```

### Creating Notifications via Artisan

```bash
php artisan make:notification OrderShipped
```

## Team Invitations

Aura CMS includes a built-in team invitation email (`Aura\Base\Mail\TeamInvitation`):

```php
use Aura\Base\Mail\TeamInvitation;
use Illuminate\Support\Facades\Mail;

Mail::to($email)->send(new TeamInvitation($invitation));
```

The invitation email:
- Uses a markdown template (`aura::emails.team-invitation`)
- Includes a signed URL for secure registration/acceptance
- Handles both new users and existing users

## Best Practices

### 1. Use Appropriate Notification Types

```php
// For instant feedback (form saves, quick actions)
$this->notify('Settings saved!');

// For errors that need attention
$this->notify('Failed to save. Please try again.', 'error');

// For persistent notifications the user needs to see later
$user->notify(new ImportantSystemNotification($data));
```

### 2. Keep Toast Messages Concise

Toast messages should be short and clear since they auto-dismiss:

```php
// Good
$this->notify('Saved successfully!');

// Too long - user may not read it in time
$this->notify('Your changes have been saved successfully to the database and all related records have been updated.');
```

### 3. Queue Heavy Notifications

For notifications that send emails or perform complex operations:

```php
class HeavyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $queue = 'notifications';
    public $tries = 3;
}
```

### 4. Include Action Links in Database Notifications

Always provide a way for users to navigate to the relevant resource:

```php
public function toDatabase($notifiable)
{
    return [
        'type' => get_class($this->resource),
        'id' => $this->resource->id,  // Used to generate edit link
        'message' => 'Notification message here',
    ];
}
```

### 5. Test Notifications

```php
use Illuminate\Support\Facades\Notification;

public function test_user_receives_notification()
{
    Notification::fake();

    $user = User::factory()->create();
    $article = Article::factory()->create();

    $article->publish();

    Notification::assertSentTo(
        $user,
        ArticlePublished::class,
        function ($notification) use ($article) {
            return $notification->article->id === $article->id;
        }
    );
}
```

## Troubleshooting

### Toast Notifications Not Showing

1. Ensure the component is included in your layout:
   ```blade
   <x-aura::notification/>
   ```

2. Check that Alpine.js is loaded properly

3. Verify the event is being dispatched:
   ```php
   $this->dispatchBrowserEvent('notify', [...]);
   ```

### Notification Center Not Visible

1. Check the feature is enabled:
   ```php
   // config/aura.php
   'features' => [
       'notifications' => true,
   ],
   ```

2. Ensure the component is loaded:
   ```blade
   @if(config('aura.features.notifications'))
       <livewire:aura::notifications/>
   @endif
   ```

### Database Notifications Not Appearing

1. Ensure the notifications table exists:
   ```bash
   php artisan notifications:table
   php artisan migrate
   ```

2. Verify the User model uses the Notifiable trait:
   ```php
   use Illuminate\Notifications\Notifiable;
   
   class User extends Authenticatable
   {
       use Notifiable;
   }
   ```

---

For more information on Laravel's notification system, see the [Laravel documentation](https://laravel.com/docs/notifications).
