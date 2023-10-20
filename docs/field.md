# Field


## Display Value

## Custom Method on the Model

// If there is a get{key}Field() method, it will be used to get the value.

```php
public function getCustomField()
{
    return 'custom value';
}
```

## Display Value on the Field

You can customize the display value of the field by setting the `display` method on the Field.

```php
[
    //...
    'display' => function($value) {
        return 'custom value';
    },
]
```


## Display View

In the Field, you can use the `display` method to display the field. The `display` method will return the field's value.

```php
[
    //...
     'display_view' => 'admin.resource_name.field_name',
]
```
