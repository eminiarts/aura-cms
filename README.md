![Aura CMS](/resources/public/img/aura.png)

# Aura CMS

**A content management system for Laravel developers.** Define your content types as PHP classes — Aura generates the admin panel around them: forms, tables, search, media, and permissions. Built on the TALL stack (Tailwind CSS, Alpine.js, Laravel, Livewire).

![Aura CMS Screenshot](/resources/public/img/screenshot.png)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/eminiarts/aura-cms.svg?style=flat-square)](https://packagist.org/packages/eminiarts/aura-cms)
[![Tests](https://img.shields.io/github/actions/workflow/status/eminiarts/aura-cms/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/eminiarts/aura-cms/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/eminiarts/aura-cms.svg?style=flat-square)](https://packagist.org/packages/eminiarts/aura-cms)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.md)

## How it works

A resource is an Eloquent model that declares its fields. This class is a complete, working content type:

```php
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class Article extends Resource
{
    public static string $type = 'Article';

    public static ?string $slug = 'article';

    public static function getFields()
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Text',
                'name' => 'Title',
                'slug' => 'title',
                'validation' => 'required|max:255',
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Wysiwyg',
                'name' => 'Content',
                'slug' => 'content',
            ],
            [
                'type' => 'Aura\\Base\\Fields\\BelongsTo',
                'name' => 'Author',
                'slug' => 'author_id',
                'resource' => 'Aura\\Base\\Resources\\User',
            ],
        ];
    }
}
```

Drop it in `app/Aura/Resources` and `/admin/article` serves a full CRUD interface — index table with sorting, filtering and search, create/edit forms with validation, and policy-based permissions. No migration needed: fields are stored as meta by default, and you can move a resource to its own table when it grows.

## Features

- **42 field types** — text, dates, media, relationships, repeaters, tabs and panels — plus your own via `php artisan aura:field`
- **Table views** — list, grid, and kanban, with saved filters and per-user column settings
- **Media manager** — drag-and-drop uploads with automatic thumbnails
- **Roles & permissions** — per-resource permissions enforced by policies
- **Teams** — optional multi-tenancy with automatic scoping of every resource
- **Global search** — Cmd+K across all resources, with bookmarks and recent pages
- **Visual resource editor** — edit fields in the browser during local development; changes are written back to your PHP classes
- **Plugins** — package resources, fields, and pages with `php artisan aura:plugin`

## Requirements

- PHP 8.4+
- Laravel 12 or 13
- Livewire 4

## Installation

```bash
composer require eminiarts/aura-cms:1.0.0-beta.3
php artisan aura:install
```

The interactive installer publishes and configures Aura, connects your application user model, runs the package migrations, publishes the frontend assets, and creates the first administrator as a Global Admin by default. Then log in and open `/admin`. The [installation guide](docs/installation.md) also documents the non-interactive options and every underlying command for scripted or customized installations.

Aura 1.0 is a fresh baseline rather than an automated upgrade from 0.x. Existing 0.x applications should read [UPGRADING.md](UPGRADING.md) before changing constraints.

## Documentation

Full documentation at **[aura-cms.com/docs](https://aura-cms.com/docs)** — from the [15-minute quick start](https://aura-cms.com/docs/quick-start) to the [complete field reference](https://aura-cms.com/docs/fields).

Working with an AI assistant? The docs are LLM-friendly: [aura-cms.com/llms.txt](https://aura-cms.com/llms.txt) indexes every page, and every page is available as raw markdown by appending `.md` to its URL.

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

Please follow the private reporting process in [SECURITY.md](SECURITY.md).

## Credits

Built by [Emini Arts](https://eminiarts.ch) and [all contributors](../../contributors). Aura CMS runs production applications for agencies and startups.

## License

MIT. See [LICENSE](LICENSE.md).
