`php artisan aura:field {name}`
------------

The `aura:field` command generates a new Aura field class, along with a view and an edit file for the field. The generated field class will be saved in the `App\Aura\Fields` namespace.

### Usage

sh

```sh
php artisan aura:field <name>
```

### Arguments


| Argument | Description |
| --- | --- |
| `name` | The name of the field class to be generated. |


### Options

None.

### Example

sh

```sh
php artisan aura:field MyField
```

This command will generate the following files:

*   `app/Aura/Fields/MyField.php`
*   `resources/views/components/fields/myfield-view.blade.php`
*   `resources/views/components/fields/myfield.blade.php`

### Generated Files

#### `app/Aura/Fields/MyField.php`

php

```php
<?php

namespace App\Aura\Fields;

use Eminiarts\Aura\Field;

class MyField extends Field
{
    //
}
```

#### `resources/views/components/fields/myfield-view.blade.php`

html

```html
<!-- This is the view file for the MyField field. -->
```

#### `resources/views/components/fields/myfield.blade.php`

html

```html
@include('components.fields.myfield-view', ['name' => $name, 'value' => $value])
```

### Options

None.

### Example

sh

```sh
php artisan aura:field MyField
```

This command will generate the following files:

*   `app/Aura/Fields/MyField.php`
*   `resources/views/components/fields/myfield-view.blade.php`
*   `resources/views/components/fields/myfield.blade.php`