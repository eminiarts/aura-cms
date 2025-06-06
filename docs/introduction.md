# Introduction to Aura CMS

> 📹 **Video Placeholder**: A comprehensive walkthrough of Aura CMS, showcasing the admin interface, resource creation, field management, and live editing capabilities using the TALL stack

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
- [Demo Applications](#demo-applications)
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

> 📹 **Video Placeholder**: Quick 2-minute demo showing the creation of a blog resource with custom fields, setting up relationships, and publishing content – all without writing code

---

<a name="why-choose-aura-cms"></a>
## Why Choose Aura CMS?

### For Laravel Developers

```php
// This is how simple it is to create a resource
namespace App\Aura\Resources;

use Aura\Base\Resource;
use Aura\Base\Fields\{ID, Text, Wysiwyg, BelongsTo, Date, Boolean};

class Article extends Resource
{
    public static string $model = \App\Models\Post::class;
    
    public function fields()
    {
        return [
            ID::make('ID'),
            Text::make('Title')->rules('required|max:255'),
            Wysiwyg::make('Content')->rules('required'),
            BelongsTo::make('Author')->resource('User'),
            Date::make('Published At')->rules('required'),
            Boolean::make('Featured')->default(false),
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
3. **Modern Stack**: Built on Laravel 10+, Livewire 3, and Tailwind CSS 3
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
│  • BaseResource     │  • 40+ Field Types│  • Table         │
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

### 🎨 Field Types (40+)
- **Basic**: Text, Textarea, Number, Email, Password, Hidden
- **Selections**: Select, Radio, Checkbox, Boolean, Advanced Select
- **Dates**: Date, Time, DateTime with timezone support
- **Rich Content**: Wysiwyg, Markdown, Code Editor
- **Media**: Image, File with drag-and-drop upload
- **Relationships**: BelongsTo, HasMany, BelongsToMany
- **Advanced**: Repeater, Group, JSON, Tags, Slug
- **Layout**: Tabs, Panels, Heading, Divider

### 👥 Team Management (Optional)
```php
// Team-scoped resources out of the box
class Project extends Resource
{
    use TeamScoped;
    
    public static string $model = Project::class;
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
| **Custom Fields** | ✅ 40+ types | ✅ Limited | ✅ Good | ✅ Good | ✅ Limited |
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
'teams' => [
    'enabled' => true,
    'model' => \App\Models\Team::class,
]

// All resources are automatically team-scoped
class Customer extends Resource
{
    use TeamScoped;
    
    public function fields()
    {
        return [
            // Customer fields...
        ];
    }
}
```

### 2. E-commerce Product Catalog
```php
class Product extends Resource
{
    public static bool $customTable = true; // Use products table
    
    public function fields()
    {
        return [
            Text::make('Name')->rules('required'),
            Number::make('Price')->rules('required|numeric|min:0'),
            Image::make('Featured Image'),
            HasMany::make('Variants'),
            BelongsToMany::make('Categories'),
            Repeater::make('Specifications')->fields([
                Text::make('Key'),
                Text::make('Value'),
            ]),
        ];
    }
}
```

### 3. Content Publishing Platform
```php
class Article extends Resource
{
    public function fields()
    {
        return [
            Text::make('Title')->rules('required'),
            Slug::make('Slug')->from('Title'),
            Wysiwyg::make('Content'),
            Tags::make('Tags'),
            Date::make('Publish Date'),
            Select::make('Status')->options([
                'draft' => 'Draft',
                'published' => 'Published',
                'scheduled' => 'Scheduled',
            ]),
        ];
    }
}
```

### 4. Learning Management System
```php
class Course extends Resource
{
    public function fields()
    {
        return [
            Text::make('Title'),
            Textarea::make('Description'),
            BelongsTo::make('Instructor')->resource('User'),
            HasMany::make('Lessons'),
            BelongsToMany::make('Students')->resource('User'),
            Json::make('Curriculum'),
            Number::make('Price'),
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

# Run the interactive installer
php artisan aura:install

# Create your first resource
php artisan aura:resource Article

# Start the development server
php artisan serve
```

Visit `http://localhost:8000/admin` and start building!

> 📹 **Video Placeholder**: Screen recording of the 5-minute quick start process, from installation to creating the first resource

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

<a name="demo-applications"></a>
## Demo Applications

### Official Demos

1. **Blog Platform Demo**
   - URL: [demo.aura-cms.com/blog](https://demo.aura-cms.com/blog)
   - Features: Articles, Categories, Tags, Comments
   - [Source Code](https://github.com/aura-cms/demo-blog)

2. **E-commerce Demo**
   - URL: [demo.aura-cms.com/shop](https://demo.aura-cms.com/shop)
   - Features: Products, Orders, Customers, Inventory
   - [Source Code](https://github.com/aura-cms/demo-ecommerce)

3. **Multi-tenant SaaS Demo**
   - URL: [demo.aura-cms.com/saas](https://demo.aura-cms.com/saas)
   - Features: Teams, Projects, Tasks, Billing
   - [Source Code](https://github.com/aura-cms/demo-saas)

### Community Showcases

- **Digital Agency Portfolio**: Built with Aura CMS
- **Online Learning Platform**: 10k+ students
- **Property Management System**: Multi-tenant solution
- **Restaurant Chain Manager**: 50+ locations

> Submit your Aura CMS project to be featured!

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
