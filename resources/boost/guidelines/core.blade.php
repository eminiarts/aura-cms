## Aura CMS

Aura resources extend `Aura\Base\Resource`, which is an Eloquent model. Define the admin form and field configuration in `public static function getFields(): array`. Follow nearby application resources before introducing a new pattern.

Generate a resource with `php artisan aura:resource Article`. The default discovery directory is `app/Aura/Resources`, with namespace `App\Aura\Resources`. Check `config/aura-settings.php` under `paths.resources` for application overrides. Class namespaces must match the configured path. Discovered resources do not need manual registration.

Fields are plain arrays. Use the installed field classes and their supported options.

@verbatim
```php
public static function getFields(): array
{
    return [
        [
            'type' => 'Aura\\Base\\Fields\\Text',
            'name' => 'Title',
            'slug' => 'title',
            'validation' => 'required|max:255',
        ],
    ];
}
```
@endverbatim

### Storage and queries

Check the resource's storage settings before changing a query or migration. By default, resources share the `posts` table and use meta storage. Base fillable attributes such as `title`, `status`, and `content` are table columns. Other input fields use the `meta` table. A field slug does not necessarily name a SQL column.

Use Eloquent `where()` for table columns and Aura's `whereMeta()` for meta fields, for example `Article::whereMeta('subtitle', 'like', 'Aura%')->get()`. Consult `docs/meta-fields.md` for JSON values and other meta query helpers.

`php artisan aura:resource Article --custom` generates a dedicated-table resource with `$customTable = true` and `$usesMeta = false`. Every input field then needs a column. Setting `$customTable` alone does not disable meta storage. Layout fields such as panels and tabs are not columns.

After defining fields, generate a migration with `php artisan aura:create-resource-migration 'App\Aura\Resources\Article'`. Review it before running migrations. The generator can reuse an existing create-table migration, so inspect its changes when working with an existing table.

### Relationships and access

Inspect the chosen relationship field's configuration and the resource's storage mode before adding Eloquent relations or foreign keys. Relationship fields are not interchangeable with ordinary column-backed Eloquent relationships.

Preserve Aura policies, resource permissions, and team scopes in custom queries and Livewire actions. Check access with the intended role and current team. A successful super-admin request does not prove ordinary users have the correct permissions. Hiding a field or action is not authorization.

### Package references

Read the installed package's `docs/creating-resources.md`, `docs/custom-tables.md`, `docs/meta-fields.md`, and `docs/roles-permissions.md` for details. Check `src/Fields/` for field options and `src/Commands/` for command signatures. These paths are relative to `vendor/eminiarts/aura-cms` in a Composer application.
