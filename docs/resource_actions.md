# Resource Actions

Here's how you can use the `$actions` property in the `Post` model in two different ways:

1.  Simple method with strings:

php

```php
public array $actions = [
    'createMissingPermissions' => 'Create Missing Permissions',
    'delete' => 'Delete',
];
```

In this method, the `$actions` property is an array where each key represents an action and its corresponding value is the label to be displayed. When you use this method, the `Post` model will display the labels as strings in the dropdown menu.

2.  Advanced usage with icon and label:

php

```php
public array $actions = [
    'createMissingPermissions' => [
        'label' => 'Create Missing Permissions',
        'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 8L15 8M15 8C15 9.65686 16.3431 11 18 11C19.6569 11 21 9.65685 21 8C21 6.34315 19.6569 5 18 5C16.3431 5 15 6.34315 15 8ZM9 16L21 16M9 16C9 17.6569 7.65685 19 6 19C4.34315 19 3 17.6569 3 16C3 14.3431 4.34315 13 6 13C7.65685 13 9 14.3431 9 16Z" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    ],
    'delete' => [
        'label' => 'Delete',
        'icon' => 'delete-icon-class',
        'class' => 'hover:text-red-700 text-red-500 font-bold',
    ],
];
```

In this method, the `$actions` property is an array where each key represents an action and its corresponding value is an array with two keys: `label` and `icon`. The `label` key represents the label to be displayed, and the `icon` key represents the class name of the icon to be displayed next to the label. When you use this method, the `Post` model will display the labels as strings and the icons as HTML elements in the dropdown menu.

Here's an example of how you can use the `$actions` property in the `Post` model:

php

```php
class Post extends Model
{
    public array $actions = [
        'publish' => [
            'label' => 'Publish',
            'icon-view' => 'icons.publish',
        ],
        'unpublish' => [
            'label' => 'Unpublish',
            'icon-view' => 'icons.unpublish',
        ],
        'delete' => [
            'label' => 'Delete'
            'class' => 'hover:text-red-700 text-red-500 font-bold',
        ],
    ];

    public function publish()
    {
        // ...
    }

    public function unpublish()
    {
        // ...
    }

    public function delete()
    {
        // ...
    }
}
```

In this example, there are three actions defined: `publish`, `unpublish`, and `delete`. The `publish` and `unpublish` actions are defined with an array containing `label` and `icon` keys, while the `delete` action is defined with a string value. When you use this `$actions` property in the `Post` model, you will see a dropdown menu with the actions and their labels/icons (if defined).

Note that the `$actions` property is an optional property in the `Post` model, and you can define it or leave it undefined depending on your needs. If it's undefined, the dropdown menu will not be displayed.

## Properties

Here's a breakdown of the possible properties you can use in the `$actions` property:

1.  `label`: The label to be displayed for the action. This property is required.
    
2.  `icon`: The class name of the icon to be displayed next to the label. You can use any valid HTML markup for icons in this property.
    
3.  `icon-view`: The path to a Blade view that contains the HTML markup for the icon to be displayed next to the label. This property is an alternative to the `icon` property, and can be used when you want to display complex or dynamic icons.
    
4.  `class`: The CSS class or classes to be applied to the action element. This property is optional.
    

You can use any combination of these properties, depending on your needs. For example, if you want to display an icon with a label and apply a custom CSS class to the action element, you can define your `$actions` property like this:

php

```php
public array $actions = [
    'create' => [
        'label' => 'Create Post',
        'icon' => '<svg ...>',
        'class' => 'text-green-500 hover:text-green-700',
    ],
];
```

In this example, the `$actions` property defines an action called "create" with a label "Create Post". It also specifies an icon with the class "fa fa-plus", which will be displayed next to the label. Finally, it applies a custom CSS class to the action element with the class "text-green-500 hover:text-green-700".

Note that the `icon` and `icon-view` properties are mutually exclusive: you can only use one of them for each action.