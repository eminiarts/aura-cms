## options

`max`

You can set the max attribute on the image field.

```php
return [
    [
        'name' => 'Image',
        'slug' => 'image',
        'type' => 'Eminiarts\\Aura\\Fields\\Image',
        'validation' => 'array|max:1',
        'max' => '1',
        // ...
    ],
];
```


You can validate the max attribute in the form request.

```php
'validation' => 'array|max:1',
```
