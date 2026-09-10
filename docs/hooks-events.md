# Hooks and events

Aura provides extension points for navigation filters, markup injection, Eloquent resource events, field lifecycle methods, named application events, and Livewire component events. This page describes the names, arguments, and return values used by the current package.

## HookManager filters

`Aura\Base\HookManager` is registered as the `hook_manager` container singleton. `addHook($name, $callback)` stores a callback and returns nothing. `applyHooks($name, $value)` passes one current value to each callback in registration order and returns the final value.

Each callback receives the current value as its only argument. It must return the value that the next callback receives. A registered name has no effect until code calls `applyHooks()` for that name. Aura calls the manager itself for the `navigation` name.

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

The callback returns a `Collection` because Aura passes a collection to the navigation hook. Returning `null` or another value changes what later callbacks and Aura receive.

## Navigation hooks

`Aura::navigation()` builds a collection of navigation item arrays, applies the `navigation` filters, sorts the result, evaluates conditional items, and groups it for the sidebar. Resource items use keys such as `name`, `icon`, `route`, `group`, `sort`, and `type`.

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

Use `Navigation::clear()` to add a filter that starts the navigation collection empty. Later navigation filters can append items again:

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

Aura caches the grouped navigation for 3,600 seconds. The cache key includes the authenticated user, current team, and registered resource list. Register filters from a provider's `boot()` method so they exist before the first navigation lookup.

## View injection hooks

Register an injection callback with the Aura facade:

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

Aura resolves each callback with `app()->call()` and concatenates the returned values in registration order. Return a string or another value that can be converted to a string. The callback may type-hint container dependencies.

These injection names are emitted by the package:

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

A resource extends `Aura\Base\Resource`, which extends Laravel's Eloquent `Model`. Standard Eloquent model events therefore apply, including `retrieved`, `creating`, `created`, `updating`, `updated`, `saving`, `saved`, `deleting`, `deleted`, `restoring`, `restored`, and `replicating`.

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

These callbacks receive the resource instance. They are Eloquent model events, not instances of an Aura event class.

### The `metaSaved` model event

Aura also registers the `metaSaved` model event string. During the Eloquent `saved` event, Aura processes queued field values, calls any field `saved()` methods, writes meta values when applicable, and then fires `metaSaved`.

`metaFields` is declared on every `Resource` as an array, so `metaSaved` runs for Resource saves even when no meta row is written. Register it as a model event:

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

Field classes can define these methods. Aura checks the optional methods with `method_exists()`.

| Method | Signature | When Aura calls it | Return value |
| --- | --- | --- | --- |
| `get` | `get($class, $value, $field = null)` | While resolving a field or meta value | The resolved value |
| `set` | `set($post, $field, $value)` | During `saving`, after a field definition's `set` closure | The value passed to the next step |
| `saving` | `saving($post, $field, $value)` | During `saving`, after `set` | Return a replacement resource, or a falsy value to keep the current one |
| `shouldSkip` | `shouldSkip($post, $field)` | During `saving`, after `saving` | `true` skips storage for this field |
| `saved` | `saved($post, $field, $value)` | During Aura's `saved` processing | Aura ignores this method's return value |

The `get()` method is used while Aura resolves field and meta values. It does not intercept every Eloquent attribute read. A real Eloquent attribute or relation can win before field-meta resolution.

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

Aura derives the method name from a field slug with StudlyCase. For example, a `full_name` field maps to `getFullNameField($value)` and `setFullNameField($value)`.

Aura checks `get{Slug}Field()` when it resolves a dynamic field value. The table display path also checks this method before a field's display callback. Return the value to display:

~~~php
public function getFullNameField($value): string
{
    return trim($this->first_name.' '.$this->last_name);
}
~~~

A resource can define `set{Slug}Field($value)` for a submitted payload that it owns. Aura includes keys with one of these methods in the create and edit payload allowlist. In the posts and meta field pipeline, a declared field queues the value during `saving` and invokes the method during its `saved` field processing. An undeclared key that reaches the `fields` payload is passed to the method during `saving` and skips normal field storage. Custom-table resources without meta write their declared input fields directly to table columns and do not use this queue.

The setter owns persistence for its value. Aura ignores its return value:

~~~php
public function setTranslationsField($value): void
{
    // Store the payload in the application's translation store.
}
~~~

## Relation fields

A field whose `isRelation()` method returns `true` must provide `relationship($model, $field)`. Aura delegates `$resource->{slug}()` calls to that method. `getRelation($model, $field)` supplies values when Aura reads the field, and `saved($post, $field, $value)` can persist submitted relation values. The built-in `Tags`, `AdvancedSelect`, and `Roles` fields use this contract.

## Aura application events

Aura currently defines two application event classes. Listen for them through Laravel's event dispatcher. They are distinct from Eloquent model event names such as `saving`, `saved`, and `metaSaved`.

### `SaveFields`

`Aura\Base\Events\SaveFields` is emitted by the resource editor's `saveFields()` method after it writes the resource field definition. The constructor is:

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

Those listeners return without changing a schema when the resource does not use a custom table. `SaveFields` is a field-definition event from the resource editor, not a record-save event.

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

Aura emits `LoggedIn` in both completed authentication paths:

- After the normal password login succeeds and the session regenerates.
- After Fortify completes a two-factor login with a valid one-time password or recovery code.

A valid password that only opens the two-factor challenge does not emit `LoggedIn` yet. An invalid one-time password does not emit it.

The current package has no `LoginChanged` event class or method. Listen for `LoggedIn` when you need the successful Aura login signal.

## Livewire component events

These are Livewire events emitted or consumed by Aura's built-in components. Event names are case-sensitive.

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

The relative order of separate application listeners on the same Eloquent event is not a contract. Use the event boundary that matches your requirement.

## Related

- [Creating Fields](/docs/creating-fields) for custom field classes and lifecycle methods
- [Resources](/docs/resources) for the resource contract and Eloquent behavior
- [Plugins](/docs/plugins) for service-provider registration
- [Livewire Components](/docs/livewire-components) for built-in component details
