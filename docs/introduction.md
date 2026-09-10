# Introduction

Aura CMS is a Laravel package for building an admin panel from PHP resource classes. A resource is an Eloquent model that declares its fields. Aura uses that definition to provide the admin navigation, CRUD screens, validation, relationships, and authorization for the resource.

![Aura CMS dashboard](/images/docs/introduction/dashboard.png)

## Resources

After [installation](/docs/installation), generate a resource with:

```bash
php artisan aura:resource Article
```

The command creates `app/Aura/Resources/Article.php`. Aura discovers resource classes in the directory configured by `aura-settings.paths.resources` and registers them with the admin panel.

A minimal resource looks like this:

```php
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class Article extends Resource
{
    public static string $type = 'Article';

    public static ?string $slug = 'article';

    public static function getFields(): array
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Text',
                'name' => 'Title',
                'slug' => 'title',
                'validation' => 'required|max:255',
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'name' => 'Content',
                'slug' => 'content',
                'validation' => 'nullable|string',
            ],
        ];
    }
}
```

`$type` identifies the resource and `$slug` determines its admin URL segment. With the default `path` setting, this resource is available at `/admin/article`. Open that URL to create your first article.

Each field is a configuration array. Common field classes include `Text`, `Textarea`, `Number`, `Select`, `Date`, `Image`, `File`, `Wysiwyg`, and relationship fields such as `BelongsTo` and `HasMany`. A field can define validation, defaults, display behavior, conditional logic, and relationship options. See [Fields](/docs/fields) for the available field types.

![Resource index](/images/docs/introduction/resource-index.png)

## Storage

The default `Resource` storage uses two tables:

- `posts` stores the resource's core attributes, such as its type, title, status, slug, owner, and team.
- `meta` stores other field values as key–value rows related to the post.

Adding a field that is stored in `meta` does not require a migration. For resources that need their own columns and indexes, Aura also supports [custom tables](/docs/custom-tables).

## Teams and authorization

Teams are enabled by default. When teams are enabled, Aura scopes resources to the current team and provides team management in the admin panel. Choose the teams setting in the installer before running the migration. Changing it after installation requires a deliberate schema migration.

Aura uses roles and permissions for admin access. The installer creates the built-in role catalog. Resource policies then authorize index, view, create, edit, and delete actions. See [Teams](/docs/teams) and [Roles & Permissions](/docs/roles-permissions).

## Built-in resources

Aura registers `User`, `Role`, `Permission`, `Option`, and `Attachment`. When teams are enabled it also registers `Team` and `TeamInvitation`. These resources use the same resource and field system as application resources and can be replaced through `config/aura.php`.

## Next steps

- [Installation](/docs/installation) — install Aura in a Laravel application
- [Quick Start](/docs/quick-start) — create the first resource and record
- [Resources](/docs/resources) — configure resource behavior
- [Fields](/docs/fields) — configure fields and relationships
