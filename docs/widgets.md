# Widgets in Aura CMS

Aura CMS provides a powerful and flexible widget system that allows you to display various types of data visualizations and metrics on your dashboard. This guide covers everything you need to know about working with widgets in Aura CMS.

## Table of Contents

- [Overview](#overview)
- [Available Widget Types](#available-widget-types)
- [Widget Configuration](#widget-configuration)
- [Creating Widgets](#creating-widgets)
- [Customizing Widgets](#customizing-widgets)
- [Widget Caching](#widget-caching)

## Overview

Widgets in Aura CMS are Livewire components that provide real-time data visualization capabilities. They can be used to display various metrics, charts, and data representations on your dashboard or resource pages.

## Available Widget Types

Aura CMS includes several built-in widget types:

1. **Value Widget**
   - Displays single numeric values with comparison to previous period
   - Supports various calculation methods (count, sum, avg, min, max)
   - Example:
   ```php
   [
       'type' => \Aura\Base\Widgets\ValueWidget::class,
       'name' => 'Total Posts',
       'column' => 'id',
       'method' => 'count'
   ]
   ```

2. **Bar Chart**
   - Visualizes data in vertical bars
   - Supports multiple series comparison
   - Example:
   ```php
   [
       'type' => \Aura\Base\Widgets\Bar::class,
       'name' => 'Monthly Posts',
       'column' => 'created_at',
       'method' => 'count'
   ]
   ```

3. **Pie Chart**
   - Shows data as segments of a circle
   - Perfect for showing proportions
   - Example:
   ```php
   [
       'type' => \Aura\Base\Widgets\Pie::class,
       'name' => 'Category Distribution',
       'taxonomy' => 'categories'
   ]
   ```

4. **Donut Chart**
   - Similar to pie chart but with a hollow center
   - Example:
   ```php
   [
       'type' => \Aura\Base\Widgets\Donut::class,
       'name' => 'Status Distribution',
       'column' => 'status'
   ]
   ```

5. **Sparkline Charts**
   - Compact line or bar charts showing trends
   - Available variants:
     - SparklineBar
     - SparklineArea
   - Example:
   ```php
   [
       'type' => \Aura\Base\Widgets\SparklineBar::class,
       'name' => 'Daily Trends',
       'column' => 'views'
   ]
   ```

## Widget Configuration

Each widget can be configured with various options:

```php
[
    'type' => \Aura\Base\Widgets\ValueWidget::class,
    'name' => 'Widget Title',
    'slug' => 'unique-widget-slug',
    'column' => 'field_name',
    'method' => 'count', // count, sum, avg, min, max
    'cache' => [
        'duration' => 60 // Cache duration in minutes
    ],
    'style' => [
        'width' => 50 // Width in percentage
    ]
]
```

## Creating Widgets

To add widgets to your resource:

```php
use Aura\Base\Resource;

class Post extends Resource
{
    public function widgets()
    {
        return [
            [
                'type' => \Aura\Base\Widgets\ValueWidget::class,
                'name' => 'Total Posts',
                'slug' => 'total-posts',
                'column' => 'id',
                'method' => 'count',
                'style' => [
                    'width' => 25
                ]
            ],
            [
                'type' => \Aura\Base\Widgets\SparklineBar::class,
                'name' => 'Posts per Day',
                'slug' => 'posts-per-day',
                'column' => 'created_at',
                'style' => [
                    'width' => 75
                ]
            ]
        ];
    }
}
```

## Customizing Widgets

You can customize widgets in several ways:

1. **Extend Base Widget Classes**
```php
use Aura\Base\Widgets\Widget;

class CustomWidget extends Widget
{
    public function getValue($start, $end)
    {
        // Custom logic to get widget value
        return $this->model->whereBetween('created_at', [$start, $end])->count();
    }

    public function render()
    {
        return view('widgets.custom');
    }
}
```

2. **Custom Views**
```php
// resources/views/widgets/custom.blade.php
<div class="aura-card">
    <div class="p-2">
        <h3 class="text-sm font-semibold">{{ $widget['name'] }}</h3>
        <div class="mt-4">
            {{ $this->values['current'] }}
        </div>
    </div>
</div>
```

## Widget Caching

Widgets support automatic caching to improve performance:

```php
[
    'type' => \Aura\Base\Widgets\ValueWidget::class,
    'name' => 'Cached Widget',
    'cache' => [
        'duration' => 60, // Cache for 60 minutes
    ]
]
```

The cache key is automatically generated based on:
- Current team ID
- Widget slug
- Date range (start and end dates)
