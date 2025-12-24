# Quick Start Guide: Build a Blog in 15 Minutes

> 📹 **Video Placeholder**: Complete walkthrough of building a fully functional blog with Aura CMS in under 15 minutes

Welcome to Aura CMS! This guide will walk you through building a complete blog application with categories, tags, authors, and media management. By the end of this tutorial, you'll understand the core concepts and be ready to build your own applications.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Creating the Blog Structure](#creating-the-blog-structure)
- [Defining Fields and Relationships](#defining-fields-and-relationships)
- [Customizing the Admin Interface](#customizing-the-admin-interface)
- [Working with Content](#working-with-content)
- [Advanced Features](#advanced-features)
- [Next Steps](#next-steps)

## What We're Building

We'll create a modern blog with:
- 📝 Blog posts with rich text editing
- 📁 Categories for organization
- 🏷️ Tags for flexible taxonomy
- 👤 Author profiles
- 🖼️ Featured images and media gallery
- 🔍 Full-text search
- 📊 Analytics widgets

## Prerequisites

Before starting, ensure you have:
- PHP 8.2+ with required extensions
- Composer installed
- MySQL or PostgreSQL database
- Basic Laravel knowledge

## Installation

### Step 1: Create a New Laravel Project

```bash
# Create new Laravel application
laravel new my-blog
cd my-blog

# Configure your database in .env
DB_CONNECTION=mysql
DB_DATABASE=my_blog
DB_USERNAME=root
DB_PASSWORD=
```

### Step 2: Install Aura CMS

```bash
# Install Aura CMS
composer require eminiarts/aura-cms

# Run the interactive installer
php artisan aura:install
```

The installer will guide you through several options:

1. **Modify aura configuration?** - Recommended for first-time setup
   - Teams: Choose whether to enable multi-tenancy
   - Features: Enable/disable global search, bookmarks, notifications, etc.
   - Theme: Customize colors, sidebar style, and dark mode
2. **Run migrations?** - Yes (creates required database tables)
3. **Create a user?** - Yes (creates your admin account)

### Step 3: Start the Development Server

```bash
php artisan serve
```

Visit `http://localhost:8000/admin` and log in with your admin credentials.

> 📹 **Video Placeholder**: Installation process from start to first login

## Creating the Blog Structure

### Step 1: Create the Category Resource

Categories will organize our blog posts. Let's create them first:

```bash
php artisan aura:resource Category
```

This creates `app/Aura/Resources/Category.php`. Let's customize it:

```php
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class Category extends Resource
{
    public static string $type = 'Category';

    public static ?string $slug = 'categories';

    protected static ?string $group = 'Blog';

    public function getIcon()
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" /></svg>';
    }

    public static function getFields()
    {
        return [
            [
                'name' => 'Name',
                'slug' => 'name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required|max:255',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'searchable' => true,
            ],
            [
                'name' => 'Slug',
                'slug' => 'slug',
                'type' => 'Aura\\Base\\Fields\\Slug',
                'validation' => 'required',
                'on_forms' => true,
                'on_view' => true,
                'from' => 'name',
            ],
            [
                'name' => 'Description',
                'slug' => 'description',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'validation' => 'nullable',
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Color',
                'slug' => 'color',
                'type' => 'Aura\\Base\\Fields\\Color',
                'validation' => 'nullable',
                'on_index' => true,
                'on_forms' => true,
                'default' => '#3B82F6',
            ],
        ];
    }
}
```

### Step 2: Create the Tag Resource

Tags provide flexible content categorization:

```bash
php artisan aura:resource Tag
```

Customize `app/Aura/Resources/Tag.php`:

```php
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class Tag extends Resource
{
    public static string $type = 'Tag';

    public static ?string $slug = 'tags';

    protected static ?string $group = 'Blog';

    public function getIcon()
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" /></svg>';
    }

    public static function getFields()
    {
        return [
            [
                'name' => 'Name',
                'slug' => 'name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required|max:255',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'searchable' => true,
            ],
            [
                'name' => 'Slug',
                'slug' => 'slug',
                'type' => 'Aura\\Base\\Fields\\Slug',
                'validation' => 'required',
                'on_forms' => true,
                'on_view' => true,
                'from' => 'name',
            ],
        ];
    }
}
```

### Step 3: Create the Article Resource

Now for the main blog post resource:

```bash
php artisan aura:resource Article
```

This is where Aura CMS shines. Customize `app/Aura/Resources/Article.php`:

```php
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class Article extends Resource
{
    public static string $type = 'Article';

    public static ?string $slug = 'articles';

    public static $globalSearch = true;

    protected static ?string $group = 'Blog';

    public function getIcon()
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>';
    }
    
    public static function getFields()
    {
        return [
            // Main Content Tab
            [
                'name' => 'Content',
                'slug' => 'content-tab',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'fields' => [
                    [
                        'name' => 'ID',
                        'slug' => 'id',
                        'type' => 'Aura\\Base\\Fields\\ID',
                        'on_index' => true,
                    ],
                    [
                        'name' => 'Title',
                        'slug' => 'title',
                        'type' => 'Aura\\Base\\Fields\\Text',
                        'validation' => 'required|max:255',
                        'on_index' => true,
                        'on_forms' => true,
                        'on_view' => true,
                        'searchable' => true,
                        'style' => [
                            'width' => '66.66',
                        ],
                    ],
                    [
                        'name' => 'Status',
                        'slug' => 'status',
                        'type' => 'Aura\\Base\\Fields\\Status',
                        'validation' => 'required',
                        'on_index' => true,
                        'on_forms' => true,
                        'options' => [
                            'draft' => 'Draft',
                            'published' => 'Published',
                            'scheduled' => 'Scheduled',
                            'archived' => 'Archived',
                        ],
                        'default' => 'draft',
                        'style' => [
                            'width' => '33.33',
                        ],
                    ],
                    [
                        'name' => 'Slug',
                        'slug' => 'slug',
                        'type' => 'Aura\\Base\\Fields\\Slug',
                        'validation' => 'required|unique:posts,slug',
                        'on_forms' => true,
                        'on_view' => true,
                        'from' => 'title',
                    ],
                    [
                        'name' => 'Excerpt',
                        'slug' => 'excerpt',
                        'type' => 'Aura\\Base\\Fields\\Textarea',
                        'validation' => 'nullable|max:500',
                        'on_forms' => true,
                        'on_view' => true,
                        'rows' => 3,
                        'helper' => 'A short summary of your article (max 500 characters)',
                    ],
                    [
                        'name' => 'Content',
                        'slug' => 'content',
                        'type' => 'Aura\\Base\\Fields\\Wysiwyg',
                        'validation' => 'required',
                        'on_forms' => true,
                        'on_view' => true,
                        'searchable' => true,
                    ],
                ],
            ],
            
            // Media & SEO Tab
            [
                'name' => 'Media & SEO',
                'slug' => 'media-seo-tab',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'fields' => [
                    [
                        'name' => 'Featured Image',
                        'slug' => 'featured_image',
                        'type' => 'Aura\\Base\\Fields\\Image',
                        'validation' => 'nullable',
                        'on_index' => true,
                        'on_forms' => true,
                        'on_view' => true,
                    ],
                    [
                        'name' => 'SEO Title',
                        'slug' => 'seo_title',
                        'type' => 'Aura\\Base\\Fields\\Text',
                        'validation' => 'nullable|max:60',
                        'on_forms' => true,
                        'helper' => 'SEO title (max 60 characters)',
                    ],
                    [
                        'name' => 'SEO Description',
                        'slug' => 'seo_description',
                        'type' => 'Aura\\Base\\Fields\\Textarea',
                        'validation' => 'nullable|max:160',
                        'on_forms' => true,
                        'rows' => 3,
                        'helper' => 'SEO meta description (max 160 characters)',
                    ],
                ],
            ],
            
            // Organization Tab
            [
                'name' => 'Organization',
                'slug' => 'organization-tab',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'fields' => [
                    [
                        'name' => 'Publishing Details',
                        'slug' => 'publishing-panel',
                        'type' => 'Aura\\Base\\Fields\\Panel',
                        'fields' => [
                            [
                                'name' => 'Author',
                                'slug' => 'author_id',
                                'type' => 'Aura\\Base\\Fields\\BelongsTo',
                                'validation' => 'required|exists:users,id',
                                'on_index' => true,
                                'on_forms' => true,
                                'on_view' => true,
                                'resource' => 'Aura\\Base\\Resources\\User',
                                'display_field' => 'name',
                                'default' => 'auth.user.id',
                                'style' => [
                                    'width' => '50',
                                ],
                            ],
                            [
                                'name' => 'Published At',
                                'slug' => 'published_at',
                                'type' => 'Aura\\Base\\Fields\\Datetime',
                                'validation' => 'nullable|date',
                                'on_index' => true,
                                'on_forms' => true,
                                'on_view' => true,
                                'default' => 'now',
                                'style' => [
                                    'width' => '50',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Categorization',
                        'slug' => 'categorization-panel',
                        'type' => 'Aura\\Base\\Fields\\Panel',
                        'fields' => [
                            [
                                'name' => 'Category',
                                'slug' => 'category_id',
                                'type' => 'Aura\\Base\\Fields\\BelongsTo',
                                'validation' => 'nullable|exists:posts,id',
                                'on_forms' => true,
                                'on_view' => true,
                                'resource' => 'App\\Aura\\Resources\\Category',
                                'display_field' => 'name',
                                'conditional_logic' => [
                                    [
                                        'field' => 'status',
                                        'operator' => '!=',
                                        'value' => 'draft',
                                    ],
                                ],
                            ],
                            [
                                'name' => 'Tags',
                                'slug' => 'tags',
                                'type' => 'Aura\\Base\\Fields\\Tags',
                                'validation' => 'nullable',
                                'on_forms' => true,
                                'on_view' => true,
                                'resource' => 'App\\Aura\\Resources\\Tag',
                                'create_new' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
    
    // Define bulk actions
    public array $bulkActions = [
        'publish' => 'Publish Selected',
        'archive' => 'Archive Selected',
        'delete' => 'Delete Selected',
    ];
}
```

> 📹 **Video Placeholder**: Creating resources using the CLI and customizing field configurations

## Working with Content

### Creating Your First Article

1. Navigate to the admin panel
2. Click on "Articles" in the sidebar
3. Click "Create Article"
4. Fill in the fields:
   - Title: "Welcome to My Blog"
   - Content: Use the rich text editor
   - Upload a featured image
   - Select a category
   - Add some tags
5. Click "Save"

### Using the Media Manager

Aura CMS includes a powerful media manager:

1. Click the media icon in the WYSIWYG editor
2. Upload images by dragging and dropping
3. Organize media with folders
4. Use the built-in image editor for quick adjustments

### Advanced Content Features

#### Conditional Logic Example

Notice how the Category field only appears when the status is not "draft"? This is conditional logic in action:

```php
'conditional_logic' => [
    [
        'field' => 'status',
        'operator' => '!=',
        'value' => 'draft',
    ],
],
```

#### Custom Validation

Add complex validation rules:

```php
[
    'name' => 'Title',
    'slug' => 'title',
    'type' => 'Aura\\Base\\Fields\\Text',
    'validation' => [
        'required',
        'max:255',
        'unique:posts,title',
        function ($attribute, $value, $fail) {
            if (str_contains(strtolower($value), 'clickbait')) {
                $fail('Please avoid clickbait titles.');
            }
        },
    ],
],
```

## Customizing the Admin Interface

### Adding Dashboard Widgets

Add analytics to your Article resource:

```php
public static function getWidgets(): array
{
    return [
        [
            'name' => 'Total Articles',
            'slug' => 'total_articles',
            'type' => 'Aura\\Base\\Widgets\\ValueWidget',
            'width' => '25',
            'method' => 'count',
            'cache' => 300,
        ],
        [
            'name' => 'Published Articles',
            'slug' => 'published_articles',
            'type' => 'Aura\\Base\\Widgets\\ValueWidget',
            'width' => '25',
            'method' => 'count',
            'queryScope' => 'published', // Uses a scope on your model
            'cache' => 300,
        ],
        [
            'name' => 'Articles by Category',
            'slug' => 'articles_by_category',
            'type' => 'Aura\\Base\\Widgets\\Pie',
            'width' => '50',
            'cache' => 600,
        ],
    ];
}
```

### Custom Table Columns

Customize how articles appear in the index table:

```php
public static function indexTableColumns(): array
{
    return [
        'featured_image' => [
            'label' => '',
            'sortable' => false,
            'class' => 'w-16',
            'view' => function($model) {
                if ($model->featured_image) {
                    return '<img src="'.$model->featured_image.'" class="w-12 h-12 rounded object-cover">';
                }
                return '';
            },
        ],
        'title' => [
            'label' => 'Title',
            'sortable' => true,
            'searchable' => true,
            'class' => 'font-medium',
        ],
        'author.name' => [
            'label' => 'Author',
            'sortable' => true,
        ],
        'status' => [
            'label' => 'Status',
            'sortable' => true,
            'view' => 'aura::fields.status-index',
        ],
        'published_at' => [
            'label' => 'Published',
            'sortable' => true,
            'format' => 'M d, Y',
        ],
    ];
}
```

## Advanced Features

### 1. Global Search Integration

Your articles are automatically searchable with `$globalSearch = true`. Users can press `Cmd+K` (Mac) or `Ctrl+K` (Windows) to search across all content.

### 2. Permissions and Roles

Create editor and writer roles:

```bash
# In your seeder or tinker
$editorRole = Role::create(['name' => 'editor']);
$editorRole->givePermissionTo([
    'view Article',
    'create Article',
    'update Article',
    'delete Article',
    'publish Article', // Custom permission
]);

$writerRole = Role::create(['name' => 'writer']);
$writerRole->givePermissionTo([
    'view Article',
    'create Article',
    'update Article',
]);
```

### 3. Querying Resources

Access your resources using Eloquent:

```php
use App\Models\Post;

// Get all published articles
$articles = Post::where('type', 'Article')
    ->where('status', 'published')
    ->with(['meta'])
    ->latest('published_at')
    ->get();

// Get a single article by slug
$article = Post::where('type', 'Article')
    ->where('slug', 'my-article')
    ->firstOrFail();

// Access field values
echo $article->title;
echo $article->fields['content'];
```

> **Note**: Aura stores resources in the `posts` table with a `type` column for single-table inheritance. Custom meta fields are stored in the `meta` table.

### 4. Custom Filters

Add custom filters to the article index:

```php
public static function filters(): array
{
    return [
        'status' => [
            'label' => 'Status',
            'type' => 'select',
            'options' => [
                '' => 'All',
                'draft' => 'Draft',
                'published' => 'Published',
                'archived' => 'Archived',
            ],
        ],
        'category_id' => [
            'label' => 'Category',
            'type' => 'select',
            'options' => \App\Aura\Resources\Category::pluck('name', 'id')->prepend('All Categories', ''),
        ],
        'date_range' => [
            'label' => 'Date Range',
            'type' => 'date_range',
        ],
    ];
}
```

### 5. Scheduled Publishing

Implement scheduled publishing with a simple command:

```php
// app/Console/Commands/PublishScheduledArticles.php
namespace App\Console\Commands;

use App\Models\Post;
use Carbon\Carbon;

class PublishScheduledArticles extends Command
{
    protected $signature = 'articles:publish-scheduled';
    
    public function handle()
    {
        Post::where('type', 'Article')
            ->where('status', 'scheduled')
            ->where('published_at', '<=', Carbon::now())
            ->update(['status' => 'published']);
            
        $this->info('Scheduled articles published successfully.');
    }
}

// In Kernel.php
$schedule->command('articles:publish-scheduled')->everyMinute();
```

## Common Customizations

### 1. Adding a Blog Homepage

Create a controller to display your blog:

```php
// app/Http/Controllers/BlogController.php
namespace App\Http\Controllers;

use App\Models\Post;

class BlogController extends Controller
{
    public function index()
    {
        $articles = Post::where('type', 'Article')
            ->where('status', 'published')
            ->with(['author', 'category', 'tags'])
            ->latest('published_at')
            ->paginate(10);
            
        return view('blog.index', compact('articles'));
    }
    
    public function show($slug)
    {
        $article = Post::where('type', 'Article')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->with(['author', 'category', 'tags'])
            ->firstOrFail();
            
        return view('blog.show', compact('article'));
    }
}
```

### 2. RSS Feed

Add an RSS feed for your blog:

```php
// routes/web.php
Route::get('/feed', function () {
    $articles = Post::where('type', 'Article')
        ->where('status', 'published')
        ->latest('published_at')
        ->take(20)
        ->get();
        
    return response()->view('feed', compact('articles'))
        ->header('Content-Type', 'application/rss+xml');
});
```

### 3. Comments System

Create a Comment resource and add a relationship field to articles:

```php
// First, create the Comment resource
// php artisan aura:resource Comment

// Then add a HasMany field to your Article resource
[
    'name' => 'Comments',
    'slug' => 'comments',
    'type' => 'Aura\\Base\\Fields\\HasMany',
    'validation' => 'nullable',
    'on_forms' => true,
    'on_view' => true,
    'resource' => 'App\\Aura\\Resources\\Comment',
],
```

## Performance Tips

### 1. Eager Loading

Eager load relationships in your queries:

```php
// In your controller or query
$articles = Post::where('type', 'Article')
    ->with(['meta', 'user'])
    ->get();

// Or define default eager loads in the resource constructor
public function __construct(array $attributes = [])
{
    parent::__construct($attributes);
    $this->with = array_merge($this->with, ['user']);
}
```

### 2. Caching

Use Aura's built-in caching:

```php
// Cache the article count for 5 minutes
cache()->remember('articles.count', 300, function () {
    return Post::where('type', 'Article')->count();
});
```

### 3. Indexing

Add database indexes for better performance:

```php
// In a migration
Schema::table('posts', function (Blueprint $table) {
    $table->index(['type', 'status', 'published_at']);
    $table->index('slug');
});
```

## Troubleshooting

### Common Issues

1. **Fields not showing**: Check `on_forms`, `on_index`, `on_view` settings
2. **Validation errors**: Check your field `validation` rules
3. **Missing relationships**: Ensure related resources exist and use fully qualified class names
4. **Permissions issues**: Check role permissions in database

### Debug Commands

```bash
# Clear Laravel caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Generate resource permissions
php artisan aura:create-resource-permissions

# Create a new resource
php artisan aura:resource MyResource

# Create a custom field
php artisan aura:field MyCustomField
```

## Next Steps

Congratulations! You've built a fully functional blog with Aura CMS. Here's what to explore next:

### 1. Advanced Resources
- 📖 **[Creating Resources](creating-resources.md)** - Deep dive into resource configuration
- 🎨 **[Fields Reference](fields.md)** - Explore all 40+ field types
- 🔧 **[Custom Fields](creating-fields.md)** - Build your own field types

### 2. Customization
- 🎨 **[Themes](themes.md)** - Customize the look and feel
- 🧩 **[Plugins](plugins.md)** - Extend functionality
- 📊 **[Widgets](widgets.md)** - Create custom dashboard widgets

### 3. Advanced Features
- 👥 **[Teams & Multi-tenancy](teams.md)** - Build SaaS applications
- 🔒 **[Roles & Permissions](roles-permissions.md)** - Fine-grained access control
- 🔄 **[Flows](flows.md)** - Automate workflows

### 4. Production
- 🚀 **[Deployment Guide](installation.md#deployment)** - Deploy to production
- ⚡ **[Performance](configuration.md#performance)** - Optimization tips
- 🔒 **[Security](configuration.md#security)** - Best practices

## Community Resources

- **GitHub**: [github.com/eminiarts/aura-cms](https://github.com/eminiarts/aura-cms)
- **Discord**: Join our community for support
- **YouTube**: Video tutorials and tips
- **Blog**: Latest updates and case studies

---

**Happy building with Aura CMS!** 🚀

Remember, this is just the beginning. Aura CMS is incredibly flexible and can be adapted to build any type of content-driven application. Experiment, explore, and enjoy the journey!

> 📹 **Video Placeholder**: Summary and next steps after completing the blog tutorial