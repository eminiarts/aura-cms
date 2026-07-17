# Introduction to Aura CMS


Welcome to **Aura CMS** – a modern, developer-first content management system built on the TALL stack (Tailwind CSS, Alpine.js, Laravel, and Livewire). Unlike traditional CMS platforms, Aura CMS is designed specifically for Laravel developers who want the power of a custom application with the convenience of a full-featured CMS.

---

## Table of Contents

- [What is Aura CMS?](#what-is-aura-cms)
- [Why Choose Aura CMS?](#why-choose-aura-cms)
- [Core Architecture](#core-architecture)
- [TALL Stack Integration](#tall-stack-integration)
- [Key Features](#key-features)
- [Aura CMS vs Other Solutions](#aura-cms-vs-other-solutions)
- [Real-World Use Cases](#real-world-use-cases)
- [Getting Started](#getting-started)
- [Built-in Resources](#built-in-resources)
- [Community and Support](#community-and-support)
- [Next Steps](#next-steps)

---

<a name="what-is-aura-cms"></a>
## What is Aura CMS?

Aura CMS is a powerful content management system that brings together the best of modern Laravel development practices with an intuitive, reactive interface powered by Livewire. It's not just another CMS – it's a complete application framework that happens to include CMS functionality.

### The Aura Philosophy

1. **Developer First**: Built by Laravel developers, for Laravel developers
2. **No Black Boxes**: Every component is transparent and extendable
3. **Progressive Enhancement**: Start simple, scale to complex
4. **Real-time by Default**: Powered by Livewire for instant reactivity
5. **Convention with Configuration**: Sensible defaults, infinite customization


---

<a name="why-choose-aura-cms"></a>
## Why Choose Aura CMS?

### For Laravel Developers

```php
// This is how simple it is to create a resource
namespace App\Aura\Resources;

use Aura\Base\Resource;

class Article extends Resource
{
    public static string $type = 'Article';
    
    public static function getFields(): array
    {
        return [
            ['type' => 'Aura\\Base\\Fields\\Text', 'name' => 'Title', 'slug' => 'title', 'validation' => 'required|max:255'],
            ['type' => 'Aura\\Base\\Fields\\Wysiwyg', 'name' => 'Content', 'slug' => 'content', 'validation' => 'required'],
            ['type' => 'Aura\\Base\\Fields\\BelongsTo', 'name' => 'Author', 'slug' => 'author_id', 'resource' => 'Aura\\Base\\Resources\\User'],
            ['type' => 'Aura\\Base\\Fields\\Date', 'name' => 'Published At', 'slug' => 'published_at'],
            ['type' => 'Aura\\Base\\Fields\\Boolean', 'name' => 'Featured', 'slug' => 'featured', 'default' => false],
        ];
    }
}
```

That's it! You now have a fully functional article management system with:
- ✅ CRUD interface with real-time validation
- ✅ Rich text editing
- ✅ User relationship management
- ✅ Automatic form generation
- ✅ Permission handling
- ✅ API endpoints (optional)

### Key Advantages

1. **No Learning Curve**: If you know Laravel, you know Aura CMS
2. **Full Control**: Unlike WordPress or other PHP CMS platforms, you have complete control
3. **Modern Stack**: Built on Laravel 12 or 13, Livewire 4, and Tailwind CSS
4. **Performance**: Optimized queries, lazy loading, and smart caching
5. **Extensible**: Every component can be extended or replaced

---

<a name="core-architecture"></a>
## Core Architecture

Aura CMS follows a modular, service-oriented architecture that will feel familiar to Laravel developers:

```
┌─────────────────────────────────────────────────────────────┐
│                        Aura CMS Core                         │
├─────────────────────┬───────────────────┬──────────────────┤
│   Resource System   │   Field System    │  Livewire Layer  │
├─────────────────────┼───────────────────┼──────────────────┤
│  • BaseResource     │  • 42 Field Types │  • Table         │
│  • Resource Model   │  • Field Pipeline │  • Forms         │
│  • Meta Storage     │  • Conditionals   │  • Modals        │
│  • Custom Tables    │  • Validation     │  • Real-time     │
├─────────────────────┴───────────────────┴──────────────────┤
│                    Laravel Foundation                        │
│         (Eloquent, Routes, Middleware, Events)              │
└─────────────────────────────────────────────────────────────┘
```

### Core Components

1. **Resource System**: Content types that extend Eloquent models
2. **Field System**: Reusable, configurable field components
3. **Pipeline Processing**: Fields are processed through a series of transformations
4. **Livewire Integration**: Real-time UI without writing JavaScript
5. **Permission Layer**: Role-based access control at every level

---

<a name="tall-stack-integration"></a>
## TALL Stack Integration

Aura CMS is built from the ground up on the TALL stack, not retrofitted:

### Tailwind CSS
- Beautiful, responsive admin interface
- Dark mode support out of the box
- Customizable theme system
- Utility-first approach throughout

### Alpine.js
- Lightweight interactivity for UI components
- Seamless integration with Livewire
- No build step required for custom interactions

### Laravel
- Full power of Laravel's ecosystem
- Eloquent ORM with enhancements
- Queue support for heavy operations
- Event-driven architecture

### Livewire
- Real-time form validation
- Instant search and filtering
- Dynamic field conditions
- No page refreshes for CRUD operations

> **Pro Tip**: Every Livewire component in Aura CMS can be extended or replaced with your own implementation

---

<a name="key-features"></a>
## Key Features

### 🚀 Resource System
- **Dynamic Resources**: Define content types with PHP classes
- **Visual Resource Editor**: Create resources through the UI
- **Flexible Storage**: Use shared `posts` table or custom tables
- **Meta Fields**: Store unlimited custom data without migrations
- **Soft Deletes**: Built-in trash functionality
- **Revisions**: Track changes over time (with plugin)

### 🎨 Field Types (42)
- **Basic**: Text, Textarea, Number, Email, Password, Hidden, Phone, Color
- **Selections**: Select, Radio, Checkbox, Boolean, AdvancedSelect, Status
- **Dates**: Date, Time, Datetime
- **Rich Content**: Wysiwyg, Code, Embed
- **Media**: Image, File with drag-and-drop upload
- **Relationships**: BelongsTo, HasMany, HasOne, BelongsToMany, Tags, Roles
- **Advanced**: Repeater, Group, Json, Slug, Permissions
- **Layout**: Tabs, Tab, Panel, Heading, HorizontalLine
- **Display**: View, ViewValue, LivewireComponent, ID

### 👥 Team Management (Optional)
```php
// Team-scoped resources are automatic when teams are enabled
// Resources are automatically filtered by current team
class Project extends Resource
{
    public static string $type = 'Project';
    
    // Team scoping is handled automatically via TeamScope
}
```

### 🔐 Permission System
- Role-based access control (RBAC)
- Resource-level permissions
- Field-level permissions
- Custom permission logic
- Team-based isolation

### 📸 Media Manager
- Drag-and-drop uploads
- Image optimization
- S3/cloud storage support
- Automatic thumbnails
- Media library with search

### 🔍 Global Search
- Keyboard shortcuts (⇧⌘K)
- Search across all resources
- Recent items tracking
- Bookmarkable pages
- Smart suggestions

### 🎯 Developer Experience
- Artisan commands for everything
- Comprehensive test helpers
- IDE autocompletion
- Detailed error messages
- Debug toolbar integration

---

<a name="aura-cms-vs-other-solutions"></a>
## Aura CMS vs Other Solutions

### Comparison with Popular Laravel CMS Options

| Feature | Aura CMS | Laravel Nova | Filament | Statamic | October CMS |
|---------|----------|--------------|----------|----------|-------------|
| **Open Source** | ✅ MIT License | ❌ Paid | ✅ MIT | ❌ Paid | ✅ MIT |
| **TALL Stack** | ✅ Native | ❌ Vue.js | ✅ Livewire | ❌ Vue.js | ❌ jQuery |
| **Visual Resource Builder** | ✅ Built-in | ❌ Code only | ❌ Code only | ✅ Limited | ✅ Backend Builder |
| **Custom Fields** | ✅ 42 types | ✅ Limited | ✅ Good | ✅ Good | ✅ Limited |
| **Multi-tenancy** | ✅ Native | ❌ Manual | ✅ Package | ❌ Manual | ❌ Manual |
| **Meta Storage** | ✅ Built-in | ❌ Manual | ❌ Manual | ✅ Built-in | ✅ Built-in |
| **Plugin System** | ✅ Native | ✅ Tools | ✅ Plugins | ✅ Addons | ✅ Plugins |
| **Learning Curve** | 🟢 Laravel | 🟢 Laravel | 🟡 Moderate | 🔴 Unique | 🔴 Unique |

### When to Choose Aura CMS

✅ **Choose Aura CMS when you need:**
- A truly open-source solution with no licensing fees
- Native TALL stack integration for real-time features
- Flexibility to start simple and scale to complex
- Team/multi-tenant support out of the box
- Visual tools for non-developers on your team

❌ **Consider alternatives when:**
- You need a headless CMS (use Strapi or Directus)
- You're not using Laravel (WordPress, Drupal)
- You prefer Vue.js over Livewire (Laravel Nova)
- You need a static site generator (Statamic can do both)

---

<a name="real-world-use-cases"></a>
## Real-World Use Cases

### 1. Multi-tenant SaaS Platform
```php
// Enable teams in config/aura.php
'teams' => true,

// All resources are automatically team-scoped when teams are enabled
class Customer extends Resource
{
    public static string $type = 'Customer';
    
    public static function getFields(): array
    {
        return [
            ['type' => 'Aura\\Base\\Fields\\Text', 'name' => 'Name', 'slug' => 'name', 'validation' => 'required'],
            ['type' => 'Aura\\Base\\Fields\\Email', 'name' => 'Email', 'slug' => 'email'],
            ['type' => 'Aura\\Base\\Fields\\Phone', 'name' => 'Phone', 'slug' => 'phone'],
        ];
    }
}
```

### 2. E-commerce Product Catalog
```php
class Product extends Resource
{
    public static string $type = 'Product';
    public static ?string $customTable = 'products'; // Use custom products table
    
    public static function getFields(): array
    {
        return [
            ['type' => 'Aura\\Base\\Fields\\Text', 'name' => 'Name', 'slug' => 'name', 'validation' => 'required'],
            ['type' => 'Aura\\Base\\Fields\\Number', 'name' => 'Price', 'slug' => 'price', 'validation' => 'required|numeric|min:0'],
            ['type' => 'Aura\\Base\\Fields\\Image', 'name' => 'Featured Image', 'slug' => 'featured_image'],
            ['type' => 'Aura\\Base\\Fields\\HasMany', 'name' => 'Variants', 'slug' => 'variants', 'resource' => 'App\\Aura\\Resources\\Variant'],
            ['type' => 'Aura\\Base\\Fields\\BelongsToMany', 'name' => 'Categories', 'slug' => 'categories', 'resource' => 'App\\Aura\\Resources\\Category'],
            ['type' => 'Aura\\Base\\Fields\\Repeater', 'name' => 'Specifications', 'slug' => 'specifications', 'fields' => [
                ['type' => 'Aura\\Base\\Fields\\Text', 'name' => 'Key', 'slug' => 'key'],
                ['type' => 'Aura\\Base\\Fields\\Text', 'name' => 'Value', 'slug' => 'value'],
            ]],
        ];
    }
}
```

### 3. Content Publishing Platform
```php
class Article extends Resource
{
    public static string $type = 'Article';
    
    public static function getFields(): array
    {
        return [
            ['type' => 'Aura\\Base\\Fields\\Text', 'name' => 'Title', 'slug' => 'title', 'validation' => 'required'],
            ['type' => 'Aura\\Base\\Fields\\Slug', 'name' => 'Slug', 'slug' => 'slug', 'based_on' => 'title'],
            ['type' => 'Aura\\Base\\Fields\\Wysiwyg', 'name' => 'Content', 'slug' => 'content'],
            ['type' => 'Aura\\Base\\Fields\\Tags', 'name' => 'Tags', 'slug' => 'tags'],
            ['type' => 'Aura\\Base\\Fields\\Date', 'name' => 'Publish Date', 'slug' => 'publish_date'],
            ['type' => 'Aura\\Base\\Fields\\Select', 'name' => 'Status', 'slug' => 'status', 'options' => [
                ['key' => 'draft', 'value' => 'Draft'],
                ['key' => 'published', 'value' => 'Published'],
                ['key' => 'scheduled', 'value' => 'Scheduled'],
            ]],
        ];
    }
}
```

### 4. Learning Management System
```php
class Course extends Resource
{
    public static string $type = 'Course';
    
    public static function getFields(): array
    {
        return [
            ['type' => 'Aura\\Base\\Fields\\Text', 'name' => 'Title', 'slug' => 'title'],
            ['type' => 'Aura\\Base\\Fields\\Textarea', 'name' => 'Description', 'slug' => 'description'],
            ['type' => 'Aura\\Base\\Fields\\BelongsTo', 'name' => 'Instructor', 'slug' => 'instructor_id', 'resource' => 'Aura\\Base\\Resources\\User'],
            ['type' => 'Aura\\Base\\Fields\\HasMany', 'name' => 'Lessons', 'slug' => 'lessons', 'resource' => 'App\\Aura\\Resources\\Lesson'],
            ['type' => 'Aura\\Base\\Fields\\BelongsToMany', 'name' => 'Students', 'slug' => 'students', 'resource' => 'Aura\\Base\\Resources\\User'],
            ['type' => 'Aura\\Base\\Fields\\Json', 'name' => 'Curriculum', 'slug' => 'curriculum'],
            ['type' => 'Aura\\Base\\Fields\\Number', 'name' => 'Price', 'slug' => 'price'],
        ];
    }
}
```

> **Pro Tip**: Each of these examples can be created in minutes using Aura's visual Resource Editor, then customized with code as needed.

---

<a name="getting-started"></a>
## Getting Started

### Quick Start (5 minutes)

```bash
# Create new Laravel project
laravel new my-awesome-cms
cd my-awesome-cms

# Install Aura CMS
composer require eminiarts/aura-cms

# Publish, configure, and initialize Aura
php artisan vendor:publish --tag=aura-config
php artisan aura:install-config
php artisan aura:extend-user-model
php artisan migrate
php artisan aura:publish
php artisan aura:user

# Create your first resource
php artisan aura:resource Article

# Start the development server
php artisan serve
```

Visit `http://localhost:8000/admin` and start building!


### What You Get Out of the Box

1. **Beautiful Admin Panel** at `/admin`
2. **User Management** with roles and permissions
3. **Media Library** with drag-and-drop uploads
4. **Global Search** with keyboard shortcuts
5. **Dark Mode** toggle
6. **Responsive Design** for mobile management
7. **Activity Log** for audit trails
8. **Two-Factor Authentication** (optional)

---

<a name="built-in-resources"></a>
## Built-in Resources

Aura CMS comes with several pre-built resources to get you started quickly:

| Resource | Description |
|----------|-------------|
| **User** | User management with authentication |
| **Team** | Multi-tenant team management |
| **Role** | Role-based access control |
| **Permission** | Granular permission system |
| **Attachment** | Media and file management |
| **Tag** | Content tagging system |
| **Option** | System-wide settings storage |
| **TeamInvitation** | Team invitation workflow |

All built-in resources can be extended or customized to fit your needs.

---

<a name="community-and-support"></a>
## Community and Support

### Get Help

- 📚 **Documentation**: Comprehensive guides and API reference
- 💬 **Discord Community**: [discord.gg/aura-cms](https://discord.gg/aura-cms)
- 🐛 **GitHub Issues**: [github.com/eminiarts/aura-cms](https://github.com/eminiarts/aura-cms)
- 📧 **Premium Support**: Available with Aura Pro subscription

### Resources

- 🎥 **YouTube Channel**: Tutorials and feature walkthroughs
- 📝 **Blog**: Tips, updates, and case studies
- 🎨 **Theme Gallery**: Free and premium themes
- 🔌 **Plugin Directory**: Extend functionality

### Contributing

Aura CMS is open source and we welcome contributions:

```bash
# Fork and clone the repository
git clone https://github.com/your-username/aura-cms.git

# Install dependencies
composer install
npm install

# Run tests
composer test

# Submit a pull request
```

See our [Contributing Guide](https://github.com/eminiarts/aura-cms/blob/main/CONTRIBUTING.md) for details.

---

<a name="next-steps"></a>
## Next Steps

Ready to dive in? Here's your learning path:

1. 📖 **[Installation Guide](installation.md)** - Set up your development environment
2. 🚀 **[Quick Start Tutorial](quick-start.md)** - Build your first Aura CMS application
3. 📚 **[Resources Deep Dive](resources.md)** - Master the resource system
4. 🎨 **[Fields Guide](fields.md)** - Explore all field types and options
5. 🔐 **[Authentication & Permissions](authentication.md)** - Secure your application
6. 🎯 **[Best Practices](best-practices.md)** - Learn from experienced developers

---

**Welcome to the Aura CMS community!** We're excited to see what you'll build. 🚀
