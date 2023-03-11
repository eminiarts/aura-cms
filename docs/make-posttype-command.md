The `aura:posttype` command is a Laravel command provided by the `Eminiarts\Aura\Commands\MakePosttype` class. This command allows you to generate a new Aura Posttype.

Usage
-----

To use the `aura:posttype` command, run the following command:

bash

```bash
php artisan aura:posttype {name}
```

The `{name}` argument is the name of the Posttype you want to create.

Command Options
---------------

There are no additional command options available for this command.

Command Output
--------------

If successful, the `aura:posttype` command will create a new Aura Posttype with the name you specified. The following files will be generated in your application:

*   `app/Aura/Resources/PostName.php`: This is the main Posttype class file.
*   `resources/views/aurapanel/posttypes/post-name.blade.php`: This is the blade view file for the posttype.

Command Examples
----------------

Here is an example of how to use the `aura:posttype` command:

bash

```bash
php artisan aura:posttype News
```

This will create a new Aura Posttype with the name `News`.

bash

```bash
php artisan aura:posttype BlogPost
```

This will create a new Aura Posttype with the name `BlogPost`.