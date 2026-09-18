# Introduction

Aura CMS adds an admin panel to your Laravel application. You define each content type, such as an article or a product, in a resource class. The resource describes its fields and validation rules. Aura uses that definition to build the forms, tables, and navigation for managing your content.

![Aura CMS dashboard](/images/docs/introduction/dashboard.png)

Start with [Installation](/docs/installation) to set up Aura and create your first administrator. Then return here to define a resource.

## Define a resource

After installation, generate an Article resource:

```bash
php artisan aura:resource Article
```

By default, this creates `app/Aura/Resources/Article.php`. Aura discovers resources in that directory and adds them to the admin panel.

Open the file and define the article's title and content fields:

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

The resource's type identifies articles in Aura's shared storage. Its slug sets the last part of the admin URL. With the default admin path, open `/admin/article` to create your first article.

The `getFields()` method defines the fields in the form. Each definition chooses an input type, label, storage key, and validation rules. Here, the title is required and limited to 255 characters. The content is optional.

Aura also supports numbers, dates, file uploads, rich text, and relationships between records. See [Fields](/docs/fields) for the available types and their options.

![Resource index](/images/docs/introduction/resource-index.png)

## Default storage

You can start adding content without creating a database table for each resource. By default, Aura stores records in a shared posts table. It has columns for common values such as the title, content, publication status, and timestamps.

Additional field values are stored separately in the meta table. For example, adding a subtitle field to the Article resource does not require a migration.

If your resource needs dedicated columns and indexes, use a [custom table](/docs/custom-tables). The [resource guide](/docs/resources#storage) explains both storage options.

## Teams and authorization

Teams let you separate records and access by group. They are enabled by default, and ordinary resource queries return records for the current team. If your application does not need teams, follow [the teams-off installation steps](/docs/installation#without-teams). Choose this during installation, since changing it later requires a database migration.

Roles and permissions control what each user can do. Aura checks resource policies before allowing actions such as viewing, creating, or deleting a record. See [Teams](/docs/teams) and [Roles and permissions](/docs/roles-permissions) for details.

## Built-in resources

Aura includes resources for users, roles, permissions, stored options, and uploaded files. When teams are enabled, it also includes teams and invitations.

These use the same resource and field system as your application. You can replace their classes in `config/aura.php` when you need custom behavior.

## Next steps

- [Installation](/docs/installation): install Aura in a Laravel application.
- [Quick start](/docs/quick-start): create your first resource and record.
- [Resources](/docs/resources): configure resource behavior and storage.
- [Fields](/docs/fields): configure fields and relationships.
