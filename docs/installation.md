# Installation

1. run `php artisan aura:install` to install (migrations and config file)
2. run `php artisan aura:publish` to publish assets


## Multitenancy

Teams are enabled by default. If you want to disable teams, set `teams` to `false` in the config file.

`'teams' => false,`


## Customization

You can customize the views by publishing them to your project.

## Migration

3. run `php artisan aura:migrate` to migrate the database
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