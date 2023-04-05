
Documentation for Widgets
=========================

Widgets are a powerful way to display important metrics, visualizations, or statistics in your Laravel application. This document outlines the different properties you can set for your widgets, the allowed types, and provides examples for each widget type.

Properties
----------

Here are the main properties you can set for each widget:

1.  **name** (string): The display name for the widget.
2.  **slug** (string): A unique identifier for the widget.
3.  **type** (string): The widget class name, which should be a fully-qualified namespace.
4.  **method** (string, optional): The method to be called on the model to retrieve data. Applicable to ValueWidget.
5.  **column** (string, optional): The column name used in the database query. Applicable to ValueWidget (avg, sum), Donut, and Pie widgets.
6.  **cache** (integer, optional): The number of seconds the widget's data should be cached.
7.  **style** (array, optional): An array containing the CSS properties to be applied to the widget. For example, `['width' => '50']` will set the width of the widget to 50%.
8.  **conditional\_logic** (array, optional): An array containing the conditional logic to be applied to the widget. The logic should be defined as a series of key-value pairs.

Allowed Types
-------------

1.  **Eminiarts\\Aura\\Widgets\\ValueWidget**: A simple value widget that displays a single value. Supports count, avg, and sum methods.
2.  **Eminiarts\\Aura\\Widgets\\SparklineBarChart**: A bar chart using the sparkline visualization style.
3.  **Eminiarts\\Aura\\Widgets\\SparklineArea**: An area chart using the sparkline visualization style.
4.  **Eminiarts\\Aura\\Widgets\\Donut**: A donut chart visualization.
5.  **Eminiarts\\Aura\\Widgets\\Pie**: A pie chart visualization.

Examples
--------

### Count - ValueWidget (count)

php

```php
[
    'name' => 'Total Posts Created',
    'slug' => 'total_posts_created',
    'type' => 'Eminiarts\\Aura\\Widgets\\ValueWidget',
    'method' => 'count',
    'cache' => 300,
    'style' => [
        'width' => '33.33',
    ],
    'conditional_logic' => [],
]
```

### Average - ValueWidget (avg)

php

```php
[
    'name' => 'Average Number',
    'slug' => 'average_number',
    'type' => 'Eminiarts\\Aura\\Widgets\\ValueWidget',
    'method' => 'avg',
    'column' => 'number',
    'cache' => 300,
    'style' => [
        'width' => '33.33',
    ],
    'conditional_logic' => [],
]
```

### Sparkline Bar Chart

php

```php
[
    'name' => 'Sparkline Bar Chart',
    'slug' => 'sparkline_bar_chart',
    'type' => 'Eminiarts\\Aura\\Widgets\\SparklineBarChart',
    'cache' => 300,
    'style' => [
        'width' => '50',
    ],
    'conditional_logic' => [],
]
```

### Sparkline Area

php

```php
[
    'name' => 'Sparkline Area',
    'slug' => 'sparkline_area',
    'type' => 'Eminiarts\\Aura\\Widgets\\SparklineArea',
    'cache' => 300,
    'style' => [
        'width' => '50',
    ],
    'conditional_logic' => [],
```



### Donut

php

```php
[
    'name' => 'Donut Chart',
    'slug' => 'donut',
    'type' => 'Eminiarts\\Aura\\Widgets\\Donut',
    'cache' => 300,
    'column' => 'number',
    'style' => [
        'width' => '50',
    ],
    'conditional_logic' => [],
]
```

### Pie

php

```php
[
    'name' => 'Pie Chart',
    'slug' => 'pie',
    'type' => 'Eminiarts\\Aura\\Widgets\\Pie',
    'cache' => 300,
    'column' => 'number',
    'style' => [
        'width' => '50',
    ],
    'conditional_logic' => [],
]
```

By following this documentation, you can create powerful and customizable widgets for your Laravel application. Remember to define the properties as required, choose an appropriate type, and use the examples provided as a guide for creating your own unique widgets.