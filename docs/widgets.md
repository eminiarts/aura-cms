# Widgets

Resource widgets are Livewire components that Aura renders above a resource's index table. Define them in the resource's static getWidgets() method. Aura passes the definitions to a shared date-range container, which renders one Livewire component for each definition.

The default dashboard is separate. It calculates its own resource counts and recent activity and does not read resource widget definitions.

## Add widgets to a resource

Use a fully qualified widget class name in each definition:

~~~php
use Aura\Base\Resource;
use Aura\Base\Widgets\SparklineBar;
use Aura\Base\Widgets\ValueWidget;

class Order extends Resource
{
    public static function getWidgets(): array
    {
        return [
            [
                'name' => 'Total orders',
                'slug' => 'total-orders',
                'type' => ValueWidget::class,
                'method' => 'count',
                'style' => ['width' => '33.33'],
            ],
            [
                'name' => 'Orders by day',
                'slug' => 'orders-by-day',
                'type' => SparklineBar::class,
                'style' => ['width' => '66.66'],
            ],
        ];
    }
}
~~~

Aura provides an empty getWidgets() method through the resource configuration trait, so a resource with no widgets can leave the generated method unchanged:

~~~php
public static function getWidgets(): array
{
    return [];
}
~~~

The resource index calls Resource::widgets(). It returns null when getWidgets() is empty, or a collection containing the definition arrays unchanged. The index view then mounts the aura::widgets container. No separate registration step is needed for resource-level widgets.

Use these keys in a definition:

| Key | Used by | Description |
| --- | --- | --- |
| name | All built-in widgets | Header text. |
| slug | All built-in widgets | Identifier used for the Livewire key and the base cache key. Keep it unique within the resource. |
| type | All widgets | The Livewire component class to mount. |
| column | ValueWidget, Sparkline, Bar, Pie, Donut | A table field or meta field used by the calculation. |
| method | ValueWidget, Sparkline, Bar, Pie, Donut | count, sum, avg, min, or max. The default is count for calculations. |
| style.width | The container | Percentage width for the widget wrapper. The wrapper becomes full width below 768 pixels. |
| previous | ValueWidget, Bar | On ValueWidget, false hides the comparison display. On Bar, the presence of the key adds the previous series to the chart. |
| goal | ValueWidget | A numeric target. The view displays the current value as a percentage of the target and draws a progress bar. |
| queryScope | Built-in aggregate widgets | The name of an Eloquent scope method on the resource query. |
| cache.duration | ValueWidget, Pie, Donut, and custom widgets that use the base properties | The value passed to Laravel's cache remember call. The default is 60. |

Unknown keys are ignored by the built-in components. Definitions are PHP arrays. The package does not provide a fluent widget builder.

## Built-in widget classes

All built-in classes are in the Aura\Base\Widgets namespace.

| Class | Output | Data |
| --- | --- | --- |
| ValueWidget | One value, a previous-period value, and a percentage change, or a goal progress bar | count, sum, avg, min, or max |
| Sparkline | Compact area chart | Daily values for the current and previous periods |
| SparklineArea | Area sparkline view | The Sparkline calculation |
| SparklineBar | Bar sparkline view | The Sparkline calculation |
| Bar | Bar chart with axes | The Sparkline calculation |
| Pie | Pie chart | Values grouped by a field, or one Total value without a column |
| Donut | Donut chart | Values grouped by a field, or one Total value without a column |

SparklineArea, SparklineBar, and Bar extend Sparkline. They change the view while sharing its date grouping and calculation code.

### ValueWidget

ValueWidget runs the selected calculation for the current period and for the preceding period of equal length. It displays the current value and the percentage change unless the definition sets previous to false.

~~~php
[
    'name' => 'Average rating',
    'slug' => 'average-rating',
    'type' => ValueWidget::class,
    'method' => 'avg',
    'column' => 'vote_average',
    'style' => ['width' => '33.33'],
],
~~~

count is the default method and ignores column. sum, avg, min, and max aggregate the selected column. The column may be a physical table field or a meta field. Numeric meta values are cast to signed integers before the aggregate is calculated.

queryScope is optional. When it names a scope that exists on the resource, ValueWidget applies that scope to the query. A missing scope is ignored by its legacy query path.

Set a numeric goal to display progress toward a target:

~~~php
[
    'name' => 'Revenue target',
    'slug' => 'revenue-target',
    'type' => ValueWidget::class,
    'method' => 'sum',
    'column' => 'total',
    'goal' => 50000,
    'style' => ['width' => '33.33'],
],
~~~

### Sparkline family

Sparkline, SparklineArea, SparklineBar, and Bar group records by the resource's created_at column. They fill missing dates with zero values.

Sparkline, SparklineArea, and SparklineBar return current and previous series. Bar returns both series as data, but its view renders the previous series only when the definition contains a previous key:

~~~php
[
    'name' => 'Orders by day',
    'slug' => 'orders-by-day',
    'type' => SparklineBar::class,
    'style' => ['width' => '50'],
],
[
    'name' => 'Orders with comparison',
    'slug' => 'orders-with-comparison',
    'type' => Bar::class,
    'previous' => true,
    'style' => ['width' => '50'],
],
~~~

For count, or when no usable aggregate method and column are supplied, the components count rows per day. sum, avg, min, and max are used for numeric physical fields on registered resources and for meta fields through the legacy query path. A non-meta field that is not a supported numeric field falls back to a row count.

~~~php
// Counts rows per day.
[
    'name' => 'Signups by day',
    'slug' => 'signups-by-day',
    'type' => SparklineArea::class,
],

// Aggregates the numeric meta field named score.
[
    'name' => 'Score by day',
    'slug' => 'score-by-day',
    'type' => SparklineArea::class,
    'method' => 'sum',
    'column' => 'score',
],
~~~

Do not pass a date column to select the grouping field. The built-in sparkline queries always group by created_at.

### Pie and Donut

Pie and Donut group records by column. With no column, they return one Total count. With a column, count groups rows by its value. sum, avg, min, and max aggregate numeric values. Physical fields and meta fields are supported, subject to the resource field and storage configuration.

~~~php
[
    'name' => 'Orders by status',
    'slug' => 'orders-by-status',
    'type' => Donut::class,
    'column' => 'status',
    'style' => ['width' => '50'],
],
~~~

The chart views use the current period's grouped values. The components also calculate the preceding period for their cached value payload.

## Date ranges

The Aura\Base\Widgets\Widgets container owns the date selection for a resource's widget row. On mount it reads the resource's public widgetSettings property. The default resource configuration uses 30d and includes these keys:

| Key | Range |
| --- | --- |
| 1d, 7d, 30d, 60d, 90d, 180d, 365d | The selected number of days ending today. |
| ytd | Start of the current year through today. |
| qtd | Start of the current quarter through today. |
| mtd | Start of the current month through today. |
| wtd | Start of the current week through today. |
| last-year | The previous full year. |
| last-quarter | The previous full quarter. |
| last-month | The previous full month. |
| last-week | The previous full week. |
| custom | Initializes to the last 30 days, then shows date inputs in the container. |
| all | Sets start and end to null. |

Configure the default and the labels shown in the select:

~~~php
public array $widgetSettings = [
    'default' => '30d',
    'options' => [
        '7d' => '7 Days',
        '30d' => '30 Days',
        'mtd' => 'Month to date',
        'ytd' => 'Year to date',
        'custom' => 'Custom',
    ],
];
~~~

The container dispatches dateFilterUpdated with start and end when the selection changes. Built-in widgets listen for that event. The shipped select omits the all option from its choices, although all remains a valid default value in the component.

all is not currently a reliable all-time setting for the built-in widgets. Widgets sets both bounds to null, while ValueWidget, Pie, and Donut pass those null values through Carbon parsing and their date-bounded queries. Sparkline supplies a 30-day fallback when its bounds are null. Use a bounded range until null-bound handling is changed.

## How widgets render on an index page

The built-in resource index renders the widget row before the table:

~~~blade
{{ app('aura')::injectView('widgets_before') }}

@if ($widgets = $resource->widgets())
    @livewire('aura::widgets', ['widgets' => $widgets, 'model' => $resource])
@endif

{{ app('aura')::injectView('widgets_after') }}

<livewire:aura::table :model="$resource" :settings="$resource->indexTableSettings()" />
~~~

The container renders each definition with the definition array, the resource instance, and the selected dates:

~~~blade
@livewire($widget['type'], [
    'widget' => $widget,
    'model' => $model,
    'start' => $start,
    'end' => $end,
], key($widget['slug']))
~~~

The widgets_before and widgets_after injection points let an application add markup around the widget row. They do not add widgets to the default dashboard.

The built-in Livewire aliases include aura::widgets, aura::widgets.value-widget, aura::widgets.sparkline-area, aura::widgets.sparkline-bar, aura::widgets.bar, aura::widgets.pie, and aura::widgets.donut. A custom definition can use its fully qualified component class directly.

## Build a custom widget

Extend Aura\Base\Widgets\Widget, compute the value for the dates you receive, return a view, and listen for dateFilterUpdated. The base class provides widget, model, start, end, loaded, isCached, format(), cacheKey, cacheDuration, loadWidget(), and mount().

~~~php
namespace App\Widgets;

use Aura\Base\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

class OpenTicketsWidget extends Widget
{
    public function getValue($start, $end): int
    {
        return $this->model->query()
            ->where('status', 'open')
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    public function getValuesProperty(): array
    {
        $start = $this->start instanceof Carbon ? $this->start : Carbon::parse($this->start);
        $end = $this->end instanceof Carbon ? $this->end : Carbon::parse($this->end);

        return cache()->remember($this->cacheKey, $this->cacheDuration, function () use ($start, $end) {
            return ['current' => $this->format($this->getValue($start, $end))];
        });
    }

    public function render(): View
    {
        return view('widgets.open-tickets');
    }

    #[On('dateFilterUpdated')]
    public function updateDateRange($start, $end): void
    {
        $this->start = $start;
        $this->end = $end;
    }
}
~~~

The view can use the base loading state:

~~~blade
<div class="aura-card" @if (! $isCached) wire:init="loadWidget" @endif>
    @if ($loaded)
        <div class="p-2">
            <span class="text-sm font-semibold">{{ $widget['name'] }}</span>
            <div class="text-4xl font-medium">{{ $this->values['current'] }}</div>
        </div>
    @else
        <div class="p-2" aria-hidden="true">
            <div class="w-16 h-6 bg-gray-200 rounded"></div>
        </div>
    @endif
</div>
~~~

Reference the class in the resource:

~~~php
[
    'name' => 'Open tickets',
    'slug' => 'open-tickets',
    'type' => \App\Widgets\OpenTicketsWidget::class,
    'style' => ['width' => '25'],
],
~~~

The example uses the same cache properties as the built-in cached widgets. If the resource can use the all setting, handle null start and end in a custom widget before parsing them.

## Discovery and registration

Aura publishes widget discovery settings in config/aura-settings.php:

~~~php
'widgets' => [
    'namespace' => 'App\\Aura\\Widgets',
    'path' => app_path('Aura/Widgets'),
    'register' => [],
],
~~~

At boot, Aura scans the configured path, converts PHP file paths to the configured namespace, and registers the discovered class names in its global registry. Aura::getAppWidgets() performs the scan. Aura::registerWidgets() adds classes to the registry, and Aura::getWidgets() returns the registered list.

This registry is separate from a resource's getWidgets() definitions. The built-in resource index consumes the definitions returned by the resource. The default dashboard does not consume either registry.

The published register array is present in the configuration file but is not read by the current discovery code. Register additional classes explicitly from a service provider:

~~~php
use Aura\Base\Facades\Aura;

Aura::registerWidgets([
    \App\Widgets\OpenTicketsWidget::class,
]);
~~~

A class referenced directly in a resource getWidgets() definition does not need global registration.

## Resource widgets and the default dashboard

Resource widgets belong to a resource index. The index view calls resource widgets(), mounts the shared container, and then mounts the resource table.

The default Aura\Base\Livewire\Dashboard component follows a different path. It filters accessible application resources, computes a total count and 30-day count for the first four resources, builds a small inline SVG sparkline, and loads recent resource items and media. It does not call getWidgets(), resource widgets(), or the aura::widgets container.

config/aura.php selects the dashboard component with aura.components.dashboard. Replacing that component is the supported customization point, but the base package does not provide a dashboard widget registration API. A custom dashboard must define its own Livewire state, queries, and view.

## Caching

ValueWidget, Pie, and Donut wrap their value payloads in cache()->remember(). Their default cache duration is 60, and cache.duration overrides it. The supplied duration is passed to Laravel's cache API as-is.

The base cache key is the MD5 hash of the current team ID, resource type, widget slug, start date, and end date. Including the resource type allows two resources to reuse a slug without sharing cached values.

Sparkline, SparklineArea, SparklineBar, and Bar do not wrap their calculations in the base cache. A custom widget can use cacheKey and cacheDuration when it needs caching.

## Focused source and tests

The widget implementation is in src/Widgets. The resource integration is in src/Resource.php, src/Traits/Concerns/AuraResourceConfiguration.php, resources/views/livewire/resource/index.blade.php, and resources/views/components/widgets/index.blade.php. Dashboard behavior is in src/Livewire/Dashboard.php and resources/views/livewire/dashboard.blade.php. Discovery and Livewire aliases are registered in src/Aura.php and src/AuraServiceProvider.php.

The focused tests for parent validation are:

- tests/Feature/Widgets/ValueWidgetTest.php
- tests/Feature/Widgets/SparklineTest.php
- tests/Feature/Widgets/CustomTableWidgetTest.php
