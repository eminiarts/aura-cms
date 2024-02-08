# Installation

## Composer

Install this package in your Laravel Project. You can start a new project by running 

`composer create-project --prefer-dist laravel/laravel project-name`

Require the package in your project.

`composer require eminiarts/aura-cms`

## License

Aura is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Aura Install

1. run `php artisan aura:install` to install (migrations and config file)
2. run `php artisan aura:publish` to publish assets


## Multitenancy

Teams are enabled by default. If you want to disable teams, set `teams` to `false` in the config file.

`'teams' => false,`


## Customization

You can customize the views by publishing them to your project.

## Migration

3. your User model should extend `php artisan aura:migrate`

```php
use Aura\Base\Models\User as AuraUser;

class User extends AuraUser
{
    //
}
```


4. run `php artisan aura:user` to create a user
5. navigate to `/admin` to log in


## Livewire Components

If you want to use the same layout that Aura uses, you can extend the `aura::components.layout.app` layout.

```php
public function render()
    {
        return view('livewire.my-component')->layout('aura::components.layout.app');
    }
```
