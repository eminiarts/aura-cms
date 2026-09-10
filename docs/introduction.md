# Introduction

Aura CMS adds an admin panel to a Laravel application from PHP Resource classes. A Resource is a content type managed by Aura. Define it by extending `Aura\Base\Resource` and returning its Fields from `getFields()`. Aura uses that definition for navigation, forms, tables, validation, and policy checks.

![Aura CMS dashboard](/images/docs/introduction/dashboard.png)

Start with [Installation](/docs/installation) to publish Aura's configuration, migrations, and assets, run the setup commands, and create the first administrator. Then return here to define your first Resource.

## Define a Resource

Generate a Resource class after installation:

```bash
php artisan aura:resource Article
```

With the default `aura-settings.paths.resources` values, the command creates `app/Aura/Resources/Article.php`. Aura discovers Resource classes in that configured directory and registers them with the admin panel.

A minimal Resource with a title and content field looks like this:

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

`$type` identifies the Resource in Aura's shared storage, and `$slug` determines its admin URL segment. The `aura.path` setting defaults to `admin`, so this Resource is available at `/admin/article`. Open that URL to manage Article records.

Each item returned by `getFields()` is a Field definition array. The `type` value names the Field class, `name` is its label, `slug` is its key, and `validation` contains its validation rules. Common Field classes include `Text`, `Textarea`, `Number`, `Select`, `Date`, `Image`, `File`, `Wysiwyg`, and relationship Fields such as `BelongsTo` and `HasMany`. See [Fields](/docs/fields) for the available Field types and options.

![Resource index](/images/docs/introduction/resource-index.png)

## Default storage

By default, a Resource uses Aura's shared `posts` and `meta` tables. The Resource class uses the shared `posts` table when `$customTable` is `false` and stores additional input Fields in `meta` when `$usesMeta` is `true`.

- `posts` stores core columns such as `title`, `content`, `type`, `status`, `slug`, `user_id`, `parent_id`, `order`, timestamps, and soft-delete data. The `team_id` column exists when teams are enabled.
- `meta` stores other Field values as polymorphic rows with `metable_type`, `metable_id`, `key`, and `value`.

Adding a Field that is stored in `meta` does not require a migration. For a Resource that needs its own columns and indexes, use [custom tables](/docs/custom-tables).

## Teams and authorization

Teams are optional and enabled by default. In teams mode, ordinary Resource queries are scoped to the current Team, and Aura registers the Team and Team Invitation Resources. In teams-off mode, Aura omits the teams table and team-specific columns from the installation schema. Choose the mode during installation by following [the teams-off installation steps](/docs/installation#without-teams). Changing the setting after the schema is installed requires a migration.

Roles and permissions control admin access. Resource policies authorize actions such as viewing, creating, updating, and deleting records. See [Teams](/docs/teams) and [Roles & Permissions](/docs/roles-permissions).

## Built-in resources

Aura registers `User`, `Role`, `Permission`, `Option`, and `Attachment`. Teams mode also registers `Team` and `TeamInvitation`. These Resources use the same Resource and Field system as application Resources. Replace their classes through `config/aura.php` when an application needs custom behavior.

## Next steps

- [Installation](/docs/installation): install Aura in a Laravel application.
- [Quick Start](/docs/quick-start): create the first Resource and record.
- [Resources](/docs/resources): configure Resource behavior and storage.
- [Fields](/docs/fields): configure Fields and relationships.
