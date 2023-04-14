## options

### max

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

### validation

You can validate the max attribute in the form request.

```php
'validation' => 'array|max:1',
```

Here's an example of the Avatar field.

```php
    [
        'name' => 'Avatar',
        'type' => 'Eminiarts\\Aura\\Fields\\Image',
        'max' => '1',
        'validation' => ['array','max:1',
            function (string $attribute, mixed $value, Closure $fail) {
                // Check if the attachment is an image
                Attachment::find($value)->each(function ($attachment) use ($fail, $attribute) {
                    if (! $attachment->isImage()) {
                        $fail("The {$attribute} is not an image.");
                    }
                });

            }
    ],
```

Because we only save the attachment id in the database, we need to check if the attachment is an image. If it's not, we throw an error.
