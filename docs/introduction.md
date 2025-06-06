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

<a name="who-should-use-aura-cms"></a>
## Who Should Use Aura CMS?

**Aura CMS** is designed to cater to a wide range of users:

- **Developers**: Those looking for a CMS that provides flexibility and control, with the ability to customize and extend functionality through code.
- **Businesses and Agencies**: Organizations seeking a scalable and customizable platform for web projects, without being locked into proprietary systems.
- **Content Creators**: Users who need an intuitive interface for managing content without delving into code.
- **Educational Institutions**: Schools and universities that require a reliable CMS for their websites and portals.
- **Community Projects**: Open-source enthusiasts and community-driven projects can benefit from Aura CMS's collaborative features.

---

<a name="getting-started"></a>
## Getting Started

To start using Aura CMS:

1. **Installation**: Follow the installation guide to set up Aura CMS on your local or production environment.
2. **Configuration**: Customize your settings in the `config/aura.php` file to suit your application's needs.
3. **Explore the Features**: Familiarize yourself with the key features, such as resource management, media handling, and user authentication.
4. **Create Resources**: Define your custom resources and fields to model your application's data.
5. **Extend Functionality**: Install plugins or develop your own to add new features.

For detailed instructions, refer to the [Getting Started Guide](getting-started.md).

---

<a name="extending-aura-cms-with-plugins"></a>
## Extending Aura CMS with Plugins

One of the strengths of Aura CMS is its extensibility through plugins:

- **Aura Pro**: Access a collection of premium plugins and features by subscribing to Aura Pro.
- **Community Plugins**: Explore plugins developed by the community to enhance your application's functionality.
- **Custom Plugins**: Develop your own plugins tailored to your specific requirements using the provided scaffolding commands.

To learn more about creating and managing plugins, see the [Plugins Documentation](plugins.md).

---

<a name="understanding-the-documentation"></a>
## Understanding the Documentation

This documentation is organized to help you find information quickly:

- **Guides**: Step-by-step tutorials on common tasks and features.
- **Reference**: Detailed information on classes, methods, and configuration options.
- **How-To Articles**: Solutions to specific problems or use cases.
- **Best Practices**: Recommendations for effectively using Aura CMS.

We recommend starting with the [Table of Contents](summary.md) to navigate through the topics.

---

<a name="community-and-support"></a>
## Community and Support

Join the Aura CMS community to collaborate, get support, and contribute:

- **GitHub Repository**: Report issues, contribute code, and access the source at [github.com/aura-cms/aura](https://github.com/aura-cms/aura).
- **Community Forum**: Engage with other users and developers in discussions (link to forum).
- **Documentation**: Access the latest documentation online (link to documentation).

---

<a name="contributing-to-aura-cms"></a>
## Contributing to Aura CMS

Aura CMS is an open-source project, and contributions are welcome:

- **Bug Reports**: Submit issues on GitHub to help improve the project.
- **Pull Requests**: Contribute code enhancements, bug fixes, or documentation updates.
- **Feature Requests**: Suggest new features or improvements.
- **Translations**: Help translate Aura CMS into different languages.

Before contributing, please read the [Contribution Guidelines](contributing.md).

---

<a name="license"></a>
## License

Aura CMS is released under the [MIT License](https://opensource.org/licenses/MIT). This means you are free to use, modify, and distribute the software as long as you include the original license.

---

Thank you for choosing Aura CMS. We hope it empowers you to build amazing web applications with ease and flexibility. If you have any questions or need assistance, don't hesitate to reach out to the community or consult the documentation.

---

<a name="next-steps"></a>
## Next Steps

- Proceed to the [Getting Started Guide](getting-started.md) to set up Aura CMS.
- Learn about [Authentication & Authorization](authentication.md) to manage users and permissions.
- Explore the [Media Management](media-management.md) features to handle your media files.

---

Happy coding!
