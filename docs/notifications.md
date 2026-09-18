# Notifications

Aura supports two kinds of notifications:

- Toasts are short, in-page messages sent through a Livewire event. They are not stored.
- The notification center reads Laravel database notifications from the authenticated user.

Configure push, broadcast, SMS, and other channels in your application, and install any packages they require. Aura does not include these packages. Only notifications stored by Laravel's `database` channel appear in the notification center.

## Toast notifications

The default app layout displays toasts through Alpine whenever it receives a Livewire `notify` event. Toasts work even when the notification center is disabled.

Call `notify()` from any Livewire component to display a toast:

```php
namespace App\Livewire;

use Livewire\Component;

class SavePost extends Component
{
    public function save(): void
    {
        // Persist the post.

        $this->notify('Post saved.');
        $this->notify('Could not save the post.', 'error');
    }
}
```

Pass the message as the first argument and an optional type as the second. The default type is `success`, which displays a check icon. Use `error` for a warning icon. Other values display a toast without a type-specific icon.

The method dispatches the following Livewire event:

```php
$this->dispatch('notify', message: $message, type: $type);
```

You can send the same payload from Alpine or JavaScript:

```javascript
window.dispatchEvent(new CustomEvent('notify', {
    detail: {
        message: 'Copied to clipboard.',
        type: 'success',
    },
}));
```

Aura registers this helper globally through a macro in `AuraServiceProvider`. The `Aura\Base\Traits\WithLivewireHelpers` trait provides the same method, but you do not need to add the trait to show a toast.

Each toast closes after three seconds. Hovering over it pauses the timer, and the close button dismisses it immediately. Toasts show a progress bar and stack when there are multiple messages. These timings and behaviors are fixed and have no Aura configuration options.

If you use a custom layout, include `<x-aura::notification />` to display toasts.

## Laravel database notifications

The notification center opens in a slide-over with Unread and Read tabs. It is enabled in the default app layout. You can control it through the notifications feature flag in your Aura configuration:

```php
'features' => [
    'notifications' => true,
],
```

Set `aura.features.notifications` to `false` to remove the center from the layout. Toasts remain available. For custom layouts, the component class is `Aura\Base\Livewire\Notifications`, registered under both `aura::notifications` and `aura.base.livewire.notifications`.

Aura's generated migration creates Laravel's standard notifications table if it does not exist. Each row stores a UUID, the notification class, a polymorphic recipient reference, JSON data, an optional read timestamp, and creation and update timestamps.

Aura's default user resource already uses Laravel's notification trait. If you use a different user model, extend `Aura\Base\Resources\User` or add the `Illuminate\Notifications\Notifiable` trait.

Send a database notification with Laravel's normal notification class:

```php
namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ArticlePublished extends Notification
{
    public function __construct(private readonly int $articleId)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Article published',
            'body' => "Article {$this->articleId} is ready to review.",
        ];
    }
}

$user->notify(new ArticlePublished($article->getKey()));
```

The center shows the authenticated user's read and unread notifications through Laravel's standard relationships. It does not poll for new notifications or listen for push events.

If you customize the component, its `getUnreadNotificationsProperty()` method returns the user's unread notifications. The `getNotificationsProperty()` method returns read notifications, exposed to the Read tab through the `notifications` property.

The built-in `markAllAsRead()` action marks every unread notification as read:

```php
auth()->user()->unreadNotifications()->update(['read_at' => now()]);
```

Aura does not include an action to mark a single notification as read. Marking all as read does not dispatch a follow-up event. Laravel's read and unread relationships use the `read_at` timestamp to determine which tab shows each notification.

### Opening the notification center

Dispatch an `openSlideOver` event with the target set to `notifications` to open the center:

```javascript
$wire.dispatch('openSlideOver', {
    target: 'notifications',
    parameters: {},
});
```

The event requires the `target` key, not `component`.

The current navigation template only shows a notifications button when the user has no current team. The button for users with a current team is commented out. Add a custom navigation button that dispatches this event if you need the center to be accessible in both cases.

### Database notification payload

Use the notification's data array to supply its heading, body, and optional resource link. These values are separate from the table's `type` column, which stores the notification class.

The two tabs currently interpret the data's resource type differently:

| Data key | Read tab | Unread tab |
| --- | --- | --- |
| `type` | Treated as a resource slug for the icon and edit route. | Treated as a resource class name. Aura checks `class_exists()`, resolves the class, and uses its icon and `getType()` route name. |
| `id` | Used as the resource ID for the edit link when a resource resolves. | Used as the resource ID for the edit link when the class and other fields resolve. |
| `message` | Used as the linked row title and as a fallback title. | Used as the linked row title when `type` and `id` resolve. |
| `title` | Fallback heading when the linked resource is unavailable. | Fallback heading when the linked resource is unavailable. |
| `body` | Secondary text, falling back to the notification class. | Secondary text when the fallback branch renders. |

Because the Read tab expects a resource slug and the Unread tab expects a class name, resource links may behave differently between tabs. Always provide `title` and `body` when a notification needs to remain readable without a link. If you also provide `type`, `id`, and `message` to link a resource, check both tabs with your application's resource registration.

## Optional Laravel channels

You can send notifications through mail, broadcast, or any other channel configured in your application. Choose the channels in the notification's `via()` method, as you would in Laravel. Include `database` if the notification should also appear in Aura's center.

Sending a Laravel notification does not display a toast. Aura also does not provide a client for receiving push or broadcast notifications.

## Related documentation

- [Livewire components](/docs/livewire-components)
- [Configuration](/docs/configuration)
- [Authentication](/docs/authentication)
- [Laravel notifications](https://laravel.com/docs/notifications)
