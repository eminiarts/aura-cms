CreateAuraPlugin Command
========================

The `CreateAuraPlugin` command is a Laravel Artisan command that allows you to create a new Aura plugin with a single command. The command will create a new plugin folder and copy over the necessary files and configurations based on the plugin type that you choose. It will also update the `composer.json` file to autoload the plugin correctly.

Usage
-----

To create a new Aura plugin, run the following command:

css

```css
php artisan aura:plugin {name}
```

where `{name}` is the name of your plugin in the format `vendor/name`. For example, to create a plugin with the name `mycompany/mymodule`, you would run:

bash

```bash
php artisan aura:plugin mycompany/mymodule
```

Available Options
-----------------

When you run the `aura:plugin` command, you will be prompted to choose the type of plugin you want to create. The available options are:

*   Complete plugin
*   Posttype plugin
*   Field plugin
*   Widget plugin

Choose the option that best suits your needs.

Examples
--------

Here are some examples of how to use the `CreateAuraPlugin` command:

bash

```bash
php artisan aura:plugin mycompany/mymodule
```

This will create a new plugin called `mymodule` in the `plugins/mycompany` folder, and will copy over the necessary files and configurations for a complete plugin.

bash

```bash
php artisan aura:plugin mycompany/mymodule --type=posttype
```

This will create a new plugin called `mymodule` in the `plugins/mycompany` folder, and will copy over the necessary files and configurations for a posttype plugin.

Updating Composer Autoloading
-----------------------------

The `CreateAuraPlugin` command will update the `composer.json` file to autoload the plugin correctly. The autoloader entry is added to the `autoload.psr-4` section of the `composer.json` file, and uses the plugin's namespace as the key and the plugin's source directory as the value.

Append Service Provider
-----------------------

The `CreateAuraPlugin` command also gives the option to append the `{Name}ServiceProvider` to `config/app.php` file.

Replacing Placeholders
----------------------

Before copying files to the new plugin directory, the `CreateAuraPlugin` command will replace all placeholders throughout all the files. The placeholders will be replaced with the correct values that you specified when running the command.

Conclusion
----------

With the `CreateAuraPlugin` command, you can easily create a new Aura plugin without having to manually create the necessary files and configurations. This can save you a lot of time and effort when building your Laravel application with Aura CMS.