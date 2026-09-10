# Notifications

Aura provides two independent notification mechanisms:

- Toasts are short, in-page messages sent through a Livewire event. They are not stored.
- The notification center reads Laravel database notifications from the authenticated user.

Aura does not add push, broadcast, SMS, or other notification channel packages. Configure those channels in the host application and install any package they require. A notification appears in Aura's center only when the `database` channel stores it in the `notifications` table.

## Toast notifications

The default Aura app layout renders `<x-aura::notification />`. It listens for the `notify` event and displays each event as an Alpine toast. The feature flag for the notification center does not affect toasts.

Every Livewire component gets a `notify()` method from the macro registered in `AuraServiceProvider`:

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

The method has the signature `notify($message, $type = 'success')`. Aura styles `success` with a check icon and `error` with a warning icon. Other type values still render a toast, but they have no type-specific icon.

The macro dispatches a Livewire event with this payload:

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

`Aura\Base\Traits\WithLivewireHelpers` also defines `notify()` for components that use the trait. The global macro means that a component does not need the trait to show a toast.

The toast view has fixed behavior. It dismisses each toast after 3 seconds, pauses the timer while the pointer is over the toast, shows a progress bar, supports a close button, and stacks multiple messages. There are no Aura configuration options for these timings or behaviors. A custom layout must include `<x-aura::notification />` for the event to be visible.

## Laravel database notifications

The `Aura\Base\Livewire\Notifications` component is registered as `aura::notifications` and `aura.base.livewire.notifications`. It renders a slide-over with Unread and Read tabs. The default app layout includes it when `aura.features.notifications` is true, which is also the default:

```php
'features' => [
    'notifications' => true,
],
```

Set the flag to `false` to remove the notification center from the layout. Toasts remain available.

Aura's generated migration creates the Laravel `notifications` table when it is missing. The table stores a UUID `id`, the notification class in `type`, the `notifiable` morph, JSON text in `data`, a nullable `read_at` timestamp, and the normal timestamps. The default `Aura\Base\Resources\User` uses Laravel's `Notifiable` trait. If the application uses another user model, that model must extend the Aura user resource or use `Illuminate\Notifications\Notifiable`.

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

The center reads the authenticated user's standard Laravel relations. `getUnreadNotificationsProperty()` returns `$user->unreadNotifications`, and `getNotificationsProperty()` returns `$user->readNotifications`. The component exposes the latter to the Read tab under the `notifications` property. It does not poll for new rows or listen for a push event.

The only built-in read action is `markAllAsRead()`:

```php
auth()->user()->unreadNotifications()->update(['read_at' => now()]);
```

There is no per-notification read action and the method does not dispatch a follow-up event. Laravel's `readNotifications`, `unreadNotifications`, and `read_at` values determine which tab contains a row.

### Opening the notification center

The slide-over key is `notifications`. Its view listens for `openSlideOver` and checks the payload's `target` value:

```javascript
$wire.dispatch('openSlideOver', {
    target: 'notifications',
    parameters: {},
});
```

Use `target`, not `component`, in this event. The current navigation template has an active notifications button only in its branch for a user without a current team. The corresponding button in the current-team branch is commented out. A custom navigation trigger can dispatch the event directly when the center should be available in both cases.

### Database notification payload

The `notifications` table's `type` column contains the notification class. The fields below are values inside the notification's `data` array. Aura's two tab views currently interpret the `type` value differently:

| Data key | Read tab | Unread tab |
| --- | --- | --- |
| `type` | Treated as a resource slug for the icon and edit route. | Treated as a resource class name. Aura checks `class_exists()`, resolves the class, and uses its icon and `getType()` route name. |
| `id` | Used as the resource ID for the edit link when a resource resolves. | Used as the resource ID for the edit link when the class and other fields resolve. |
| `message` | Used as the linked row title and as a fallback title. | Used as the linked row title when `type` and `id` resolve. |
| `title` | Fallback heading when the linked resource is unavailable. | Fallback heading when the linked resource is unavailable. |
| `body` | Secondary text, falling back to the notification class. | Secondary text when the fallback branch renders. |

The current views do not define one consistent `type` contract. A normal resource slug works in the Read tab, while the Unread tab expects a resource class name. Populate `title` and `body` when the notification must remain readable without a resource link. If you populate `type`, `id`, and `message` for a resource link, check both tabs against the resource registration used by the host application.

## Optional Laravel channels

Aura only renders database notifications in the center. A notification whose `via()` method returns `mail`, `broadcast`, or another channel follows Laravel and the host application's channel configuration. It does not become a toast, and it does not appear in the center unless `database` is also included. Aura itself does not provide a push or broadcast client.

## Related documentation

- [Livewire components](/docs/livewire-components)
- [Configuration](/docs/configuration)
- [Authentication](/docs/authentication)
- [Laravel notifications](https://laravel.com/docs/notifications)
