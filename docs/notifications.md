# Notifications in Aura CMS

Aura CMS provides a comprehensive notification system that enables real-time user feedback, system alerts, and communication between users. Built on Laravel's notification system and enhanced with Livewire for real-time updates, it offers both in-app and external notification channels.

## Table of Contents

- [Overview](#overview)
- [Notification Types](#notification-types)
- [Creating Notifications](#creating-notifications)
- [Notification Channels](#notification-channels)
- [Real-time Notifications](#real-time-notifications)
- [UI Components](#ui-components)
- [Email Templates](#email-templates)
- [User Preferences](#user-preferences)
- [Advanced Features](#advanced-features)
- [Best Practices](#best-practices)

## Overview

The notification system in Aura CMS consists of:
- **System Notifications**: Automatic alerts for system events
- **Custom Notifications**: User-defined notifications for specific actions
- **Multi-channel Support**: Database, email, Slack, SMS, and more
- **Real-time Updates**: Live notifications using Livewire
- **Notification Center**: Centralized UI for managing notifications

## Notification Types

### System Notifications

Aura CMS automatically sends notifications for:

```php
// Resource created
$user->notify(new ResourceCreated($resource));

// User invited to team
$user->notify(new TeamInvitation($team));

// Permission changed
$user->notify(new PermissionUpdated($permission));

// Media uploaded
$user->notify(new MediaUploaded($media));
```

### Custom Notifications

Create custom notifications for your application:

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
            ->line('Thank you for contributing to our platform!');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'article_published',
            'article_id' => $this->article->id,
            'title' => $this->article->title,
            'message' => 'Your article has been published',
            'action_url' => '/admin/articles/' . $this->article->id,
        ];
    }
}
```

## Creating Notifications

### Using Artisan Command

```bash
php artisan make:notification OrderShipped
```

### Notification Structure

```php
namespace App\Notifications;

use Aura\Base\Notifications\AuraNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class OrderShipped extends AuraNotification implements ShouldQueue
{
    use Queueable;

    protected $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        // Define channels based on user preferences
        $channels = ['database'];
        
        if ($notifiable->notification_preferences['email'] ?? true) {
            $channels[] = 'mail';
        }
        
        if ($notifiable->notification_preferences['slack'] ?? false) {
            $channels[] = 'slack';
        }
        
        return $channels;
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'order_shipped',
            'icon' => 'truck',
            'color' => 'success',
            'title' => 'Order Shipped',
            'message' => "Order #{$this->order->number} has been shipped",
            'action_text' => 'Track Order',
            'action_url' => route('orders.track', $this->order),
            'data' => [
                'order_id' => $this->order->id,
                'tracking_number' => $this->order->tracking_number,
            ]
        ];
    }

    public function toMail($notifiable)
    {
        return (new AuraMailMessage)
            ->subject('Your Order Has Been Shipped!')
            ->greeting("Hello {$notifiable->name}!")
            ->line("Good news! Your order #{$this->order->number} has been shipped.")
            ->line("Tracking Number: {$this->order->tracking_number}")
            ->action('Track Your Order', route('orders.track', $this->order))
            ->line('Expected delivery: ' . $this->order->expected_delivery->format('M d, Y'))
            ->salutation('Thanks for shopping with us!');
    }
}
```

## Notification Channels

### Database Channel

Store notifications in the database for in-app display:

```php
public function toDatabase($notifiable)
{
    return [
        'type' => 'resource_updated',
        'icon' => 'edit',
        'color' => 'info',
        'title' => 'Resource Updated',
        'message' => "{$this->resource->name} has been updated",
        'action_text' => 'View Changes',
        'action_url' => route('aura.resources.show', $this->resource),
        'data' => [
            'resource_id' => $this->resource->id,
            'changes' => $this->resource->getChanges(),
        ]
    ];
}
```

### Email Channel

Send notifications via email:

```php
public function toMail($notifiable)
{
    return (new MailMessage)
        ->subject('Important Update')
        ->view('emails.notification', [
            'user' => $notifiable,
            'content' => $this->content,
        ])
        ->attach(storage_path('app/reports/monthly.pdf'));
}
```

### Slack Channel

Send notifications to Slack:

```php
public function toSlack($notifiable)
{
    return (new SlackMessage)
        ->from('Aura CMS')
        ->to('#general')
        ->content('New user registration!')
        ->attachment(function ($attachment) use ($notifiable) {
            $attachment->title('User Details')
                ->fields([
                    'Name' => $notifiable->name,
                    'Email' => $notifiable->email,
                    'Registered' => $notifiable->created_at->diffForHumans(),
                ]);
        });
}
```

### SMS Channel (via Nexmo/Twilio)

```php
public function toNexmo($notifiable)
{
    return (new NexmoMessage)
        ->content('Your verification code is: ' . $this->code)
        ->from('AURA');
}
```

## Real-time Notifications

### Livewire Component

Aura CMS includes a real-time notification component:

```php
namespace App\Http\Livewire;

use Livewire\Component;
use Aura\Base\Traits\InteractsWithNotifications;

class NotificationBell extends Component
{
    use InteractsWithNotifications;

    public $unreadCount = 0;
    public $notifications = [];
    public $showDropdown = false;

    protected $listeners = [
        'notificationReceived' => 'refreshNotifications',
        'echo:notifications,.NewNotification' => 'handleNewNotification',
    ];

    public function mount()
    {
        $this->refreshNotifications();
    }

    public function refreshNotifications()
    {
        $this->unreadCount = auth()->user()->unreadNotifications()->count();
        $this->notifications = auth()->user()
            ->notifications()
            ->latest()
            ->take(5)
            ->get();
    }

    public function markAsRead($notificationId)
    {
        $notification = auth()->user()
            ->notifications()
            ->find($notificationId);
            
        if ($notification) {
            $notification->markAsRead();
            $this->refreshNotifications();
            
            if ($notification->data['action_url'] ?? false) {
                return redirect($notification->data['action_url']);
            }
        }
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        $this->refreshNotifications();
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}
```

### Broadcasting Notifications

For real-time updates using Laravel Echo:

```php
// In your notification class
public function toBroadcast($notifiable)
{
    return new BroadcastMessage([
        'type' => 'notification',
        'icon' => 'bell',
        'title' => $this->title,
        'message' => $this->message,
        'timestamp' => now()->toIso8601String(),
    ]);
}
```

## UI Components

### Notification Bell

```blade
{{-- resources/views/livewire/notification-bell.blade.php --}}
<div class="relative" x-data="{ open: false }">
    <button @click="open = !open" class="relative p-2 text-gray-600 hover:text-gray-900">
        <x-aura::icon.notifications />
        @if($unreadCount > 0)
            <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500"></span>
        @endif
    </button>

    <div x-show="open" 
         @click.away="open = false"
         x-transition
         class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg overflow-hidden z-50">
        
        <div class="p-4 bg-gray-50 border-b">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold">Notifications</h3>
                @if($unreadCount > 0)
                    <button wire:click="markAllAsRead" class="text-sm text-blue-600 hover:text-blue-800">
                        Mark all as read
                    </button>
                @endif
            </div>
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse($notifications as $notification)
                <div wire:click="markAsRead('{{ $notification->id }}')" 
                     class="p-4 hover:bg-gray-50 cursor-pointer border-b {{ $notification->read_at ? 'opacity-60' : '' }}">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <x-aura::icon :name="$notification->data['icon'] ?? 'bell'" 
                                         class="w-5 h-5 text-{{ $notification->data['color'] ?? 'gray' }}-500" />
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-900">
                                {{ $notification->data['title'] ?? 'Notification' }}
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ $notification->data['message'] ?? '' }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-gray-500">
                    <x-aura::icon.empty class="w-12 h-12 mx-auto mb-2" />
                    <p>No notifications</p>
                </div>
            @endforelse
        </div>

        <div class="p-4 bg-gray-50 border-t">
            <a href="{{ route('aura.notifications.index') }}" 
               class="block text-center text-sm text-blue-600 hover:text-blue-800">
                View all notifications
            </a>
        </div>
    </div>
</div>
```

### Toast Notifications

Show temporary notifications:

```javascript
// In Alpine.js component
Alpine.store('notifications', {
    items: [],
    
    add(notification) {
        const id = Date.now();
        this.items.push({
            id,
            ...notification
        });
        
        setTimeout(() => {
            this.remove(id);
        }, 5000);
    },
    
    remove(id) {
        this.items = this.items.filter(item => item.id !== id);
    }
});

// Usage
Alpine.store('notifications').add({
    type: 'success',
    title: 'Success!',
    message: 'Your changes have been saved.'
});
```

## Email Templates

### Custom Email Template

```blade
{{-- resources/views/emails/notification.blade.php --}}
@component('mail::message')
# {{ $notification->data['title'] }}

{{ $notification->data['message'] }}

@if($notification->data['action_url'] ?? false)
@component('mail::button', ['url' => $notification->data['action_url']])
{{ $notification->data['action_text'] ?? 'View Details' }}
@endcomponent
@endif

@if($notification->data['additional_info'] ?? false)
@component('mail::panel')
{{ $notification->data['additional_info'] }}
@endcomponent
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent
```

### Customizing Email Notifications

```php
public function toMail($notifiable)
{
    return (new MailMessage)
        ->theme('aura')
        ->subject($this->subject)
        ->markdown('emails.custom-notification', [
            'user' => $notifiable,
            'data' => $this->data,
        ])
        ->withSwiftMessage(function ($message) {
            $message->getHeaders()
                ->addTextHeader('X-Mailgun-Tag', 'notification')
                ->addTextHeader('X-Mailgun-Track', 'yes');
        });
}
```

## User Preferences

### Managing Notification Preferences

```php
// Migration for user preferences
Schema::table('users', function (Blueprint $table) {
    $table->json('notification_preferences')->nullable();
});

// User model
class User extends Authenticatable
{
    protected $casts = [
        'notification_preferences' => 'array',
    ];

    public function getNotificationPreference($channel, $type = null)
    {
        $preferences = $this->notification_preferences ?? [];
        
        if ($type) {
            return $preferences[$channel][$type] ?? true;
        }
        
        return $preferences[$channel] ?? true;
    }

    public function setNotificationPreference($channel, $value, $type = null)
    {
        $preferences = $this->notification_preferences ?? [];
        
        if ($type) {
            $preferences[$channel][$type] = $value;
        } else {
            $preferences[$channel] = $value;
        }
        
        $this->notification_preferences = $preferences;
        $this->save();
    }
}
```

### Preference UI Component

```php
// Livewire component for managing preferences
class NotificationPreferences extends Component
{
    public $emailNotifications = true;
    public $browserNotifications = false;
    public $slackNotifications = false;
    public $notificationTypes = [];

    public function mount()
    {
        $user = auth()->user();
        $preferences = $user->notification_preferences ?? [];
        
        $this->emailNotifications = $preferences['email'] ?? true;
        $this->browserNotifications = $preferences['browser'] ?? false;
        $this->slackNotifications = $preferences['slack'] ?? false;
        
        $this->notificationTypes = [
            'resource_created' => $preferences['types']['resource_created'] ?? true,
            'resource_updated' => $preferences['types']['resource_updated'] ?? true,
            'comment_added' => $preferences['types']['comment_added'] ?? true,
            'mention' => $preferences['types']['mention'] ?? true,
        ];
    }

    public function save()
    {
        auth()->user()->update([
            'notification_preferences' => [
                'email' => $this->emailNotifications,
                'browser' => $this->browserNotifications,
                'slack' => $this->slackNotifications,
                'types' => $this->notificationTypes,
            ]
        ]);

        $this->notify('Notification preferences updated!');
    }

    public function render()
    {
        return view('livewire.notification-preferences');
    }
}
```

## Advanced Features

### Notification Groups

Group related notifications:

```php
class NotificationGroup
{
    public static function groupSimilar($notifications)
    {
        return $notifications->groupBy(function ($notification) {
            return $notification->data['type'] . '_' . 
                   Carbon::parse($notification->created_at)->format('Y-m-d');
        })->map(function ($group) {
            if ($group->count() > 3) {
                return [
                    'type' => 'grouped',
                    'count' => $group->count(),
                    'latest' => $group->first(),
                    'items' => $group->take(3),
                ];
            }
            return $group;
        });
    }
}
```

### Scheduled Notifications

Send notifications at specific times:

```php
// Schedule a notification for later
$user->notify((new InvoiceReminder($invoice))->delay(now()->addDays(3)));

// Or use a custom scheduled notification
class WeeklyDigest extends Notification implements ShouldQueue
{
    public function via($notifiable)
    {
        return $notifiable->wants_weekly_digest ? ['mail'] : [];
    }

    public function toMail($notifiable)
    {
        $activities = $this->getWeeklyActivities($notifiable);
        
        return (new MailMessage)
            ->subject('Your Weekly Digest')
            ->markdown('emails.weekly-digest', [
                'user' => $notifiable,
                'activities' => $activities,
            ]);
    }
}

// In Kernel.php
$schedule->job(new SendWeeklyDigests)->weekly()->mondays()->at('09:00');
```

### Notification Actions

Handle notification actions:

```php
class ApprovalNotification extends Notification
{
    public function toDatabase($notifiable)
    {
        return [
            'type' => 'approval_required',
            'title' => 'Approval Required',
            'message' => 'A new article requires your approval',
            'actions' => [
                [
                    'text' => 'Approve',
                    'url' => route('articles.approve', $this->article),
                    'method' => 'POST',
                    'color' => 'success',
                ],
                [
                    'text' => 'Reject',
                    'url' => route('articles.reject', $this->article),
                    'method' => 'POST',
                    'color' => 'danger',
                ],
            ],
        ];
    }
}
```

### Custom Notification Channels

Create custom channels:

```php
namespace App\Channels;

use Illuminate\Notifications\Notification;

class WebhookChannel
{
    public function send($notifiable, Notification $notification)
    {
        $data = $notification->toWebhook($notifiable);
        
        Http::post($notifiable->webhook_url, [
            'type' => 'notification',
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}

// Register in service provider
public function boot()
{
    Notification::extend('webhook', function ($app) {
        return new WebhookChannel();
    });
}
```

## Best Practices

### 1. Use Queues for Heavy Operations

```php
class DataExportCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    // Specify queue
    public $queue = 'notifications';
    
    // Set tries
    public $tries = 3;
    
    // Set timeout
    public $timeout = 30;
}
```

### 2. Implement Rate Limiting

```php
class NotificationRateLimiter
{
    public static function shouldSend($user, $type)
    {
        $key = "notification:{$user->id}:{$type}";
        $limit = config("notifications.rate_limits.{$type}", 10);
        
        if (Cache::get($key, 0) >= $limit) {
            return false;
        }
        
        Cache::increment($key);
        Cache::expire($key, 3600); // 1 hour
        
        return true;
    }
}
```

### 3. Clean Up Old Notifications

```php
// Command to clean old notifications
class CleanOldNotifications extends Command
{
    protected $signature = 'notifications:clean {--days=30}';
    
    public function handle()
    {
        $days = $this->option('days');
        
        DB::table('notifications')
            ->where('created_at', '<', now()->subDays($days))
            ->where('read_at', '!=', null)
            ->delete();
            
        $this->info("Deleted read notifications older than {$days} days.");
    }
}

// Schedule in Kernel
$schedule->command('notifications:clean')->daily();
```

### 4. Test Notifications

```php
class NotificationTest extends TestCase
{
    public function test_user_receives_article_published_notification()
    {
        Notification::fake();
        
        $user = User::factory()->create();
        $article = Article::factory()->create(['author_id' => $user->id]);
        
        $article->publish();
        
        Notification::assertSentTo(
            $user,
            ArticlePublished::class,
            function ($notification, $channels) use ($article) {
                return $notification->article->id === $article->id &&
                       in_array('mail', $channels) &&
                       in_array('database', $channels);
            }
        );
    }
}
```

### 5. Localization

```php
public function toMail($notifiable)
{
    return (new MailMessage)
        ->subject(__('notifications.order_shipped.subject'))
        ->line(__('notifications.order_shipped.line1', [
            'order' => $this->order->number
        ]))
        ->action(__('notifications.order_shipped.action'), $url)
        ->line(__('notifications.order_shipped.line2'));
}
```

## Troubleshooting

### Common Issues

1. **Notifications not sending**
   - Check queue workers are running
   - Verify notification channels are configured
   - Check user notification preferences

2. **Real-time updates not working**
   - Ensure broadcasting is configured
   - Check WebSocket connection
   - Verify Echo is properly initialized

3. **Email notifications failing**
   - Check mail configuration
   - Verify email templates exist
   - Check mail queue for errors

### Debug Commands

```bash
# Test notification sending
php artisan tinker
>>> $user = User::first();
>>> $user->notify(new TestNotification());

# Process notification queue
php artisan queue:work --queue=notifications

# Clear notification cache
php artisan cache:clear
```

---

For more information on Laravel's notification system, see the [Laravel documentation](https://laravel.com/docs/notifications).