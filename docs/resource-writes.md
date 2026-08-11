# Resource writes

Aura's field-aware write boundary is `Aura\Base\ResourcePersistence\ResourceWriter`.
Use it from forms, API controllers, commands, and import jobs whenever input is
expressed as Resource field slugs.

```php
use App\Aura\Resources\Contact;
use Aura\Base\ResourcePersistence\ResourceWriter;

$contact = app(ResourceWriter::class)->create(
    new Contact,
    ['first_name' => 'Ada', 'email' => 'ada@example.test'],
);

$contact = app(ResourceWriter::class)->update(
    $contact,
    ['first_name' => 'Augusta', 'email' => 'ada@example.test'],
);
```

The writer resolves the current create or edit field declaration, evaluates
write-time conditional visibility, derives protected slug fields, validates the
declared rules, and rejects unknown, hidden, disabled, inactive, or otherwise
non-writable keys. It separates physical and meta storage through the Resource
storage contract. Ownership, team, inheritance, primary-key, and timestamp
columns are never accepted merely because a client submitted them.

`createGlobal()`, `promoteToGlobal()`, and `moveGlobalToTeam()` compose the same
field pipeline with Aura's existing authorized scope-transition APIs. They do
not turn browser-supplied team or owner identifiers into trusted context.

## Transactions and events

Validation completes before persistence starts. Physical fields, relationship
hooks, and meta upserts then run on the Resource's writer connection inside one
transaction. Ordinary meta values use one database upsert keyed by
`metable_type`, `metable_id`, and `key`; relationship field hooks retain their
field-specific persistence behavior.

With normal Eloquent events enabled, the order is:

1. Field validation and normalization.
2. Physical row save.
3. Relationship hooks and batched meta upsert.
4. Native `metaSaved` callback.
5. CORE-16 lifecycle snapshot and after-commit event publication.

Laravel's `withoutEvents()` intentionally suppresses native and Aura lifecycle
events. Call `saveWithFields()` inside that scope when field normalization and
meta persistence are still required:

```php
use Aura\Base\Contracts\FieldValueContext;

Contact::withoutEvents(fn () => app(ResourceWriter::class)->saveWithFields(
    $contact,
    ['first_name' => 'Quiet update'],
    FieldValueContext::Edit,
));
```

This explicit API does not recreate or manually dispatch suppressed events.

## Background and import callers

The writer has no implicit system bypass. A queued caller must restore its
recorded actor, current team, and Resource connection before calling the normal
authorized methods. Trusted installers and catalog synchronizers may continue
to use Resource methods explicitly named `ForSystem`; client-controlled import
columns must not be routed through those methods.
