# Plugins

I am working on a Package in Laravel. This is the outline for the functionality i want to build. In Aura CMS, i want to build a Plugin System. Users should be able to run a command to create their own plugins, which could be its own composer packages for Aura CMS.

## Creating a Plugin

I want to be able to run `php artisan aura:plugin` to start a new plugin.

The command should ask if i want to build a complete plugin, a posttype, a field or a widget plugin.

Regardless which option you chosse:
- ask for the {vendor} and package {name}
- create a folder with {name} in app/Aura/Plugins.
- the folder should contain a composer.json (so it could be it's own laravel package) with the {name} as a package title and {vendor} as the package vendor.
- create a `{Name}ServiceProvider.php` class 

Based on the first answer:
If you choose "complete plugin", the following should happen:
- copy files from stubs/plugin/* to app/Aura/Plugins/{name}

If you choose "posttype plugin", the following should happen:
- copy files from stubs/plugin-posttype/* to app/Aura/Plugins/{name}

If you choose "field plugin", the following should happen:
- copy files from stubs/plugin-field/* to app/Aura/Plugins/{name}

If you choose "widget plugin", the following should happen:
- copy files from stubs/plugin-widget/* to app/Aura/Plugins/{name}


## Installing a Plugin

You can customize the Post view by either changing Fields or you can change the Post View alltogether.

## Register your Plugin at the plugins.aura-cms.com

Please upload your package to github and submit your plugin at plugins.aura-cms.com.



# Fields

## Creating a Field

I want to be able to run `php artisan aura:field {name}` to start a new field.

It should create a File in app/Aura/Fields/{name}.php and the corresponding view in resources/views/aura/fields/{name}.php