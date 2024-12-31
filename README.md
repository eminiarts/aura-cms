![Aura CMS](path/to/logo.png)

# Aura CMS - The Modern CMS for Laravel Developers

> **Note**: Official documentation and launch will be available in the first week of January 2025. Stay tuned!

[![Latest Version on Packagist](https://img.shields.io/packagist/v/eminiarts/aura-cms.svg?style=flat-square)](https://packagist.org/packages/eminiarts/aura-cms)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/eminiarts/aura-cms/run-tests?label=tests)](https://github.com/eminiarts/aura-cms/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/eminiarts/aura-cms/Fix%20PHP%20code%20style%20issues?label=code%20style)](https://github.com/eminiarts/aura-cms/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/eminiarts/aura-cms.svg?style=flat-square)](https://packagist.org/packages/eminiarts/aura-cms)

Aura CMS is a powerful, flexible content management system built on the TALL stack (Tailwind CSS, Alpine.js, Laravel, and Livewire). It combines the best practices of modern Laravel development with an intuitive admin interface, making it perfect for developers who want to build custom applications quickly without sacrificing flexibility.

## ✨ Features

### 🎯 Core Features
- **Resource System**: Similar to WordPress post types but supercharged with Laravel's Eloquent
- **Dynamic Fields**: Powerful field system with 25+ customizable field types for building complex content structures
- **Team Management**: Built-in multi-tenancy support (optional)
- **Role-Based Access Control**: Comprehensive permissions system
- **Media Management**: Built-in media library with image optimization
- **Theme System**: Customizable themes with dark mode support

### 💡 Developer Experience
- **Visual Resource Editor**: Build your resources and fields visually
- **Custom Fields API**: Create your own field types
- **Plugin System**: Extend functionality with custom plugins
- **Flexible Storage**: Start with posts table, migrate to custom tables when ready
- **TALL Stack**: Leverage the power of Tailwind, Alpine.js, Laravel, and Livewire

### 🚀 User Experience
- **Global Search**: Quick navigation with keyboard shortcuts (⇧⌘K)
- **Bookmarks**: Save frequently accessed pages
- **Recent Pages**: Track last visited pages
- **Customizable Tables**: Sort, filter, and save views
- **Responsive Design**: Works seamlessly on all devices

## 🛠 Installation

```bash
# Create a new Laravel project
laravel new my-project
cd my-project

# Install Aura CMS
composer require eminiarts/aura-cms

# Run the installer
php artisan aura:install
```

The installer will guide you through:
- Publishing configuration files
- Running migrations
- Setting up your first admin user
- Configuring themes and features

## 📚 Documentation

Visit our [documentation](docs/installation.md) for detailed guides on:

- [Getting Started](docs/installation.md)
- [Creating Resources](docs/resource.md)
- [Field Types](docs/fields.md)
- [Relationships](docs/relationships.md)
- [Actions](docs/resource_actions.md)
- [Plugins](docs/plugins.md)
- [Customization](docs/customizing-post-view.md)

## 🧪 Testing

```bash
# Run all tests
composer test

# Run tests with Pest
vendor/bin/pest

# Run tests without teams
vendor/bin/pest -c phpunit-without-teams.xml

# Run tests with coverage
XDEBUG_MODE=coverage vendor/bin/pest --coverage --min=80
```

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## 🔒 Security

If you discover any security-related issues, please email security@eminiarts.com instead of using the issue tracker.

## 📄 License

Aura CMS is open-source software licensed under the [MIT license](LICENSE.md).

## 🙏 Credits

- [Emini Arts](https://github.com/eminiarts)
- [All Contributors](../../contributors)

---

Built with ❤️ by [Emini Arts](https://eminiarts.com)
