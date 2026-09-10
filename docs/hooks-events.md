# Hooks and events

You can customize Aura by filtering navigation, adding markup to views, and responding to changes in resources and fields. Aura supports standard Eloquent events alongside its own application and Livewire events. This page explains when each extension point runs and what your callback should return.

## HookManager filters

Filters let you change a value before Aura uses it. Each callback receives the current value as its only argument and must return the value to pass to the next callback. Callbacks run in registration order.

The hook manager is available through the `hook_manager` container singleton, an instance of `Aura\Base\HookManager`. Register a callback with `addHook($name, $callback)`, which returns nothing. Run the callbacks with `applyHooks($name, $value)`, which returns their final value.

Registering a filter does not run it. Your code must apply the filter by name. Aura already does this for the `navigation` filter.

Register a filter from a service provider. The provider must be registered in the application:

~~~php
<?php

namespace App\Providers;

use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        app('hook_manager')->addHook('navigation', function (Collection $items): Collection {
            return $items
                ->map(function (array $item): array {
                    if (($item['type'] ?? null) === 'Article') {
                        $item['name'] = 'News';

                        return $item;
                    }

                    return $item;
                })
                ->values();
        });
    }
}
~~~

Navigation filters receive a collection and should return a collection. If you return `null` or a different type, later callbacks and Aura receive that value instead.

## Navigation hooks

Aura builds the sidebar navigation by collecting items and applying the navigation filters. It then sorts the result, evaluates conditional items, and groups the items for display. You can retrieve this navigation with `Aura::navigation()`.

Each resource item is an array with keys such as `name`, `icon`, `route`, `group`, `sort`, and `type`.

Use `Navigation::add()` to append items. Its optional authorization callback runs when you register the item. If it returns a falsy value, Aura does not add the item:

~~~php
use Aura\Base\Navigation\Navigation;

Navigation::add([
    [
        'name' => 'Reports',
        'icon' => "<x-aura::icon icon='dashboard' />",
        'route' => 'reports.index',
        'group' => 'Analytics',
        'sort' => 100,
    ],
], fn (): bool => auth()->check() && auth()->user()->isSuperAdmin());
~~~

To clear the navigation, register a filter with `Navigation::clear()`. Filters registered after it can add items again:

~~~php
Navigation::clear();
~~~

You can rewrite or remove entries through the same filter chain:

~~~php
use Illuminate\Support\Collection;

app('hook_manager')->addHook('navigation', function (Collection $navigation): Collection {
    return $navigation
        ->reject(fn (array $item): bool => ($item['type'] ?? null) === 'Attachment')
        ->values();
});
~~~

Aura caches the grouped navigation for one hour. The cache key includes the authenticated user, current team, and registered resource list. Register filters from a provider's `boot()` method so they exist before the first navigation lookup.

## View injection hooks

View injection hooks let you add markup at specific locations in Aura's pages. Register a callback with the Aura facade and return the markup to insert:

~~~php
<?php

namespace App\Providers;

use Aura\Base\Facades\Aura;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Aura::registerInjectView(
            'table_before',
            fn (): string => Blade::render(
                '<div class="mb-4 rounded bg-blue-50 p-3">Reports are updated hourly.</div>'
            ),
        );
    }
}
~~~

Aura joins the returned markup in registration order. Each callback must return a string or a value that can be converted to one. You may type-hint container dependencies because Aura invokes the callback through Laravel's `app()->call()` method.

The package provides these injection locations:

| Name | Location |
| --- | --- |
| `table_before`, `table_after` | Before and after the resource table |
| `table_before_{Type}`, `table_after_{Type}` | Before and after a table for a resource type, such as `table_before_Article` |
| `header_before`, `header_after` | Before and after the table header |
| `breadcrumbs_before`, `breadcrumbs_after` | Before and after breadcrumbs |
| `index_before` | At the top of the resource index |
| `widgets_before`, `widgets_after` | Before and after the index widgets |
| `profile_before_header`, `profile_after_header` | Before and after the profile header |
| `post_edit_breadcrumbs_before`, `post_edit_breadcrumbs_after` | Before and after edit-screen breadcrumbs |
| `post_edit_title_before`, `post_edit_title_after` | Before and after the edit-screen title |

The `{Type}` part uses the resource's `getType()` value.

## Eloquent events on resources

Aura resources are Eloquent models, so you can listen for the standard model events when a record is retrieved, created, updated, saved, deleted, restored, or replicated. Both the before and after events are available for creating, updating, saving, deleting, and restoring records.

If a resource overrides `booted()`, call `parent::booted()` so Aura keeps its global scopes and saved listener:

~~~php
namespace App\Aura\Resources;

use Aura\Base\Resource;
use Illuminate\Support\Str;

class Article extends Resource
{
    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (Article $article): void {
            if (blank($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
        });

        static::saved(function (Article $article): void {
            cache()->forget('article.'.$article->getKey());
        });
    }
}
~~~

Each callback receives the resource instance. Eloquent does not pass an Aura event object to these callbacks.

### The `metaSaved` model event

Use the `metaSaved` model event when you need to run code after Aura has processed queued field values, called the fields' `saved()` methods, and written any meta values. This processing happens during Eloquent's saved event.

The event fires whenever a resource is saved, even when no meta row is written. Every resource declares the array of queued meta fields that this processing checks. Register a listener as a model event:

~~~php
protected static function booted(): void
{
    parent::booted();

    static::registerModelEvent('metaSaved', function (Article $article): void {
        // Meta-backed fields and field saved() methods have run.
    });
}
~~~

`metaSaved` is a model event name. There is no `Aura\Base\Events\MetaSaved` class.

## Field lifecycle methods

A field class can customize how Aura reads, transforms, and saves its values. Define the methods you need. Aura checks whether each optional method exists before calling it.

| Method | Signature | When Aura calls it | Return value |
| --- | --- | --- | --- |
| `get` | `get($class, $value, $field = null)` | While resolving a field or meta value | The resolved value |
| `set` | `set($post, $field, $value)` | During `saving`, after a field definition's `set` closure | The value passed to the next step |
| `saving` | `saving($post, $field, $value)` | During `saving`, after `set` | Return a replacement resource, or a falsy value to keep the current one |
| `shouldSkip` | `shouldSkip($post, $field)` | During `saving`, after `saving` | `true` skips storage for this field |
| `saved` | `saved($post, $field, $value)` | During Aura's `saved` processing | Aura ignores this method's return value |

The getter only runs when Aura resolves a field or meta value. An Eloquent attribute or relationship may take precedence, so the getter does not intercept every attribute read.

For example, this field stores cents and exposes a decimal value:

~~~php
namespace App\Aura\Fields;

use Aura\Base\Fields\Field;

class Price extends Field
{
    public function get($class, $value, $field = null): float
    {
        return (float) $value / 100;
    }

    public function set($post, $field, $value): int
    {
        return (int) round((float) $value * 100);
    }
}
~~~

Aura caches resolved field classes by resource class and field slug. If `shouldSkip()` uses state set by `set()`, clear that state when `shouldSkip()` returns:

~~~php
protected bool $skip = false;

public function set($post, $field, $value)
{
    $this->skip = blank($value);

    return $value;
}

public function shouldSkip($post, $field): bool
{
    $skip = $this->skip;
    $this->skip = false;

    return $skip;
}
~~~

A field definition can also provide a `set` closure. Aura calls it before the field class's `set()` method:

~~~php
use Illuminate\Support\Str;

[
    'name' => 'Slug',
    'slug' => 'slug',
    'type' => 'Aura\\Base\\Fields\\Text',
    'set' => static fn ($post, $field, $value): string => Str::slug((string) $value),
]
~~~

## Resource field methods

You can also define field getters and setters on the resource itself. Aura converts the field slug to StudlyCase to find the method. For example, the `full_name` field uses `getFullNameField($value)` and `setFullNameField($value)`.

Aura calls the getter when resolving a dynamic field value. Tables also check for it before using the field's display callback. Return the value you want to display:

~~~php
public function getFullNameField($value): string
{
    return trim($this->first_name.' '.$this->last_name);
}
~~~

Define a setter with `set{Slug}Field($value)` when the resource needs to handle a submitted value itself. Aura allows keys with these setters in submissions from the create and edit forms.

For resources that store fields in posts and meta, the setter runs at a different point depending on whether the key has a field definition. A declared field queues its value during saving and calls the setter during saved-field processing. An undeclared key in the `fields` payload calls its setter during saving and skips normal field storage.

Custom-table resources without meta write declared input fields directly to their table columns. They do not use this queue.

The setter owns persistence for its value. Aura ignores its return value:

~~~php
public function setTranslationsField($value): void
{
    // Store the payload in the application's translation store.
}
~~~

## Relation fields

Relationship fields provide separate methods for defining the relationship, reading its values, and saving submitted values. The built-in tags, advanced select, and roles fields follow this contract.

If your field returns `true` from `isRelation()`, it must define `relationship($model, $field)`. Aura calls this method when you access the relationship through `$resource->{slug}()`. Define `getRelation($model, $field)` to supply values when Aura reads the field. Use `saved($post, $field, $value)` to persist submitted relationship values.

## Aura application events

Aura provides two application event classes that you can listen for through Laravel's event dispatcher. These listeners receive event objects, unlike the Eloquent model listeners described above.

### `SaveFields`

The resource editor emits `Aura\Base\Events\SaveFields` after saving changes to a resource's field definition through `saveFields()`. The event constructor accepts the new definitions, the previous definitions, and the resource:

~~~php
public function __construct(array $fields, $oldFields, $model)
~~~

The event exposes:

- `fields`: the field definition array submitted to the editor.
- `oldFields`: the previous mapped field definitions.
- `model`: the resource being edited.

Register a listener in a service provider or the application's event provider:

~~~php
use Aura\Base\Events\SaveFields;
use Illuminate\Support\Facades\Event;

Event::listen(SaveFields::class, function (SaveFields $event): void {
    $resource = $event->model;

    // $event->fields, $event->oldFields, and $resource are available here.
});
~~~

Aura registers its own database migration listener according to `config('aura.features.custom_tables_for_resources')`:

| Config value | Listener |
| --- | --- |
| `'multiple'` | `Aura\Base\Listeners\CreateDatabaseMigration` |
| `true` or `'single'` | `Aura\Base\Listeners\ModifyDatabaseMigration` |
| Any other value | No package migration listener |

The migration listeners only change the schema for resources that use a custom table. This event concerns changes to field definitions in the resource editor. It does not fire when you save a record.

### `LoggedIn`

`Aura\Base\Events\LoggedIn` carries the authenticated user in its public `user` property:

~~~php
use Aura\Base\Events\LoggedIn;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

Event::listen(LoggedIn::class, function (LoggedIn $event): void {
    Log::info('Aura login completed', [
        'user_id' => $event->user->getAuthIdentifier(),
    ]);
});
~~~

Aura emits the login event after authentication completes:

- After the normal password login succeeds and the session regenerates.
- After Fortify completes a two-factor login with a valid one-time password or recovery code.

Opening the two-factor challenge with a valid password does not emit the event. Neither does submitting an invalid one-time password.

The current package has no `LoginChanged` event class or method. Listen for `LoggedIn` when you need the successful Aura login signal.

## Livewire component events

Aura's built-in components use the following Livewire events. Event names are case-sensitive.

| Event | Payload | Used by |
| --- | --- | --- |
| `refreshTable` | None | Table refreshes after filters, actions, and resource changes |
| `resourceCreated` | One array with `for`, `resource`, and `title` | Resource create modal notifies fields such as `AdvancedSelect` |
| `openModal` | `component`, `arguments`, and `modalAttributes`, as positional arguments or one object payload | `Modals` opens a component |
| `closeModal` | Optional modal id | `Modals` closes one modal or all modals |
| `openSlideOver` | Named `target` and `parameters` values | Slide-over view activates the matching component |
| `saveField` | One array with `slug` and `value` | Resource editor saves an existing field definition |
| `deleteField` | One array with `slug` and `value` | Resource editor deletes a field definition |
| `refreshResourceEditor` | None | Resource editor emits it after adding a field |
| `updateField` | Named `data` array with `slug` and `value` | Media fields, profile, and media manager update a field |
| `fieldUpdated` | One array with `slug` and `value` | Media field consumers react to an update |
| `selectedMediaUpdated` | One array with `slug` and `value` | Media field and profile consumers react to selected media |
| `notify` | Named `message` and `type` values | The layout toast component shows a message |
| `dateFilterUpdated` | Positional `start` and `end` values. Both are `null` for the `all` range. | Chart widgets update their date range |

For example, a Livewire component can listen for a resource-created payload:

~~~php
use Livewire\Attributes\On;

#[On('resourceCreated')]
public function resourceCreated($data): void
{
    $this->selected = $data['resource'];
}
~~~

The package dispatches `openSlideOver` with `target` and `parameters`. It dispatches `openModal` with the modal component and arguments. `modalOpened` and `slideOverOpened` are not package event names.

## Resource save order

A resource save follows Laravel's Eloquent event order:

1. `saving` fires. Aura fills missing base values. Resources using the posts and meta field pipeline pack input fields into a temporary payload, apply the field `set` closure, `set()`, `saving()`, and `shouldSkip()` methods, then remove that payload. Custom-table resources without meta write declared input fields directly to their table columns.
2. `creating` fires for a new resource, or `updating` fires for an existing resource.
3. Eloquent writes the row.
4. `created` fires for a new resource, or `updated` fires for an existing resource.
5. `saved` fires. Aura processes queued field values, calls field `saved()` methods, fires `metaSaved`, and clears the fields cache.

Do not rely on the order in which separate application listeners run for the same Eloquent event. Choose an event that fires after the processing your code depends on.

## Related

- [Creating Fields](/docs/creating-fields) for custom field classes and lifecycle methods
- [Resources](/docs/resources) for the resource contract and Eloquent behavior
- [Plugins](/docs/plugins) for service-provider registration
- [Livewire Components](/docs/livewire-components) for built-in component details
