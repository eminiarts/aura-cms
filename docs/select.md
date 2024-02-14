## options

`searchable`

Option Groups

```php
return [
    'label' => 'Type',
    'name' => 'Type',
    'type' => 'Aura\\Base\\Fields\\Select',
    'validation' => 'required',
    'slug' => 'type',
     'options' => [
        'Input Fields' => [
            'Aura\\Base\\Fields\\Text' => 'Text',
            'Aura\\Base\\Fields\\Textarea' => 'Textarea',
            'Aura\\Base\\Fields\\Number' => 'Number',
            'Aura\\Base\\Fields\\Email' => 'Email',
            'Aura\\Base\\Fields\\Phone' => 'Phone',
        ],
        'Media Fields' => [
            'Aura\\Base\\Fields\\Image' => 'Image',
            'Aura\\Base\\Fields\\File' => 'File',
        ],
        'Choice Fields' => [
            'Aura\\Base\\Fields\\Select' => 'Select',
            'Aura\\Base\\Fields\\Radio' => 'Radio',
            'Aura\\Base\\Fields\\Checkbox' => 'Checkbox',
            'Aura\\Base\\Fields\\Boolean' => 'Boolean',
        ],
    ],
];
```
