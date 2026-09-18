# Widgets

Resource widgets display values and charts above a resource's index table. Each widget is a Livewire component, and the row shares a date-range selector. Define the widgets in the resource's static `getWidgets()` method.

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

Resources have no widgets by default. If you do not need any, leave the generated method unchanged:

~~~php
public static function getWidgets(): array
{
    return [];
}
~~~

No separate registration step is needed. The index reads the definitions through `Resource::widgets()` and renders them in the shared widget container. This method returns the definitions unchanged as a collection, or `null` when none are defined.

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

All built-in widget classes use the `Aura\Base\Widgets` namespace.

| Class | Output | Data |
| --- | --- | --- |
| ValueWidget | One value, a previous-period value, and a percentage change, or a goal progress bar | count, sum, avg, min, or max |
| Sparkline | Compact area chart | Daily values for the current and previous periods |
| SparklineArea | Area sparkline view | The Sparkline calculation |
| SparklineBar | Bar sparkline view | The Sparkline calculation |
| Bar | Bar chart with axes | The Sparkline calculation |
| Pie | Pie chart | Values grouped by a field, or one Total value without a column |
| Donut | Donut chart | Values grouped by a field, or one Total value without a column |

The area sparkline, bar sparkline, and bar chart extend `Sparkline`. They share its calculations and date grouping, but use different views.

### ValueWidget

The value widget compares the selected period with the preceding period of equal length. It shows the current value and percentage change. Set `previous` to `false` to hide the comparison.

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

By default, the widget counts records and ignores the selected column. To calculate a sum, average, minimum, or maximum, set `method` to `sum`, `avg`, `min`, or `max` and choose a `column`. This can be a physical table field or a meta field. The widget casts numeric meta values to signed integers before calculating the result.

To filter the records, set `queryScope` to the name of an Eloquent scope on the resource. The value widget applies the scope if it exists. Its legacy query path ignores a missing scope.

Set a numeric `goal` to display progress toward a target:

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

All sparkline widgets and the bar chart group records by day using the resource's `created_at` column. Days without records have a value of zero.

These widgets return data for both the current and previous periods. The bar chart only displays the previous period when its definition contains a `previous` key:

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

By default, these widgets count records per day. They also support sums, averages, minimums, and maximums for numeric physical fields on registered resources. Meta fields support these calculations through the legacy query path. If the method or column cannot be used, the widgets fall back to counting records. This includes physical fields that are not supported numeric fields.

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

The grouping date cannot be changed by passing a date column. Built-in sparklines always group by `created_at`.

### Pie and Donut

Pie and donut charts group records by the selected `column` and count the records in each group. Without a column, they return a single count labelled Total. They also support sums, averages, minimums, and maximums of numeric values. You can use physical fields or meta fields, subject to the resource's field and storage configuration.

~~~php
[
    'name' => 'Orders by status',
    'slug' => 'orders-by-status',
    'type' => Donut::class,
    'column' => 'status',
    'style' => ['width' => '50'],
],
~~~

The charts display the current period's grouped values. They also calculate and cache values for the preceding period.

## Date ranges

The widget row shares one date selection. When it mounts, the container reads the resource's public `widgetSettings` property. The default range is the last 30 days, using `30d`. The default configuration includes these ranges:

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

When the selection changes, the container sends the start and end dates in a `dateFilterUpdated` event. Built-in widgets listen for this event. The date selector omits `all`, although the component accepts it as a default value.

Use a bounded date range with built-in widgets. The `all` setting is not currently reliable. It sets both dates to `null`, which the value, pie, and donut widgets pass through Carbon parsing and date-bounded queries. Sparklines instead fall back to 30 days when the dates are null.

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

Use the `widgets_before` and `widgets_after` injection points to add markup around the widget row. These affect the resource index, not the default dashboard.

A custom definition can use its fully qualified component class directly. If you need a Livewire alias, the built-in container uses `aura::widgets`. Its widget aliases are `aura::widgets.value-widget`, `aura::widgets.sparkline-area`, `aura::widgets.sparkline-bar`, `aura::widgets.bar`, `aura::widgets.pie`, and `aura::widgets.donut`.

## Build a custom widget

To build a custom widget, extend `Aura\Base\Widgets\Widget`. Calculate a value for the supplied dates, return a view, and listen for `dateFilterUpdated` to handle date changes.

The base class provides the following properties and methods:

| Purpose | Members |
| --- | --- |
| Widget definition and resource | `widget`, `model` |
| Date range | `start`, `end` |
| Loading state | `loaded`, `isCached` |
| Cache settings | `cacheKey`, `cacheDuration` |
| Formatting and lifecycle | `format()`, `loadWidget()`, `mount()` |

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

The example uses the same cache properties as the built-in cached widgets. If the resource allows the `all` date range, handle null start and end dates before parsing them.

## Discovery and registration

Configure widget discovery in `config/aura-settings.php`:

~~~php
'widgets' => [
    'namespace' => 'App\\Aura\\Widgets',
    'path' => app_path('Aura/Widgets'),
    'register' => [],
],
~~~

When the application boots, Aura scans the configured directory and maps the PHP file paths to class names in the configured namespace. It adds these classes to a global widget registry.

To work with this registry directly, use `Aura::getAppWidgets()` to scan for classes, `Aura::registerWidgets()` to add classes, and `Aura::getWidgets()` to retrieve the registered list.

Resource index pages use the definitions returned by the resource's `getWidgets()` method, independently of this registry. The default dashboard uses neither source.

The current discovery code does not read the configuration's `register` array. To add classes to the registry, register them explicitly in a service provider:

~~~php
use Aura\Base\Facades\Aura;

Aura::registerWidgets([
    \App\Widgets\OpenTicketsWidget::class,
]);
~~~

Classes referenced directly in a resource's widget definitions do not need global registration.

## Resource widgets and the default dashboard

Resource widgets appear on the resource index, in a shared container above its table.

The default dashboard calculates its own content. It takes the first four accessible application resources and displays their total counts, 30-day counts, and small SVG sparklines. It also loads recent records and media. It does not read resource widget definitions or render the shared widget container.

To customize the dashboard, replace its component through the `components.dashboard` setting in `config/aura.php`. The default is `Aura\Base\Livewire\Dashboard`. The package has no dashboard widget registration API, so a custom dashboard must define its own Livewire state, queries, and view.

## Caching

Value, pie, and donut widgets cache their calculated values using Laravel's `cache()->remember()`. Set `cache.duration` to override the default duration of 60. Aura passes this value to Laravel's cache API unchanged.

The base cache key is the MD5 hash of the current team ID, resource type, widget slug, start date, and end date. Including the resource type allows two resources to reuse a slug without sharing cached values.

Sparklines and bar charts do not use the base cache for their calculations. Custom widgets can use the `cacheKey` and `cacheDuration` properties to cache their own values.

## Focused source and tests

The widget implementation is in src/Widgets. The resource integration is in src/Resource.php, src/Traits/Concerns/AuraResourceConfiguration.php, resources/views/livewire/resource/index.blade.php, and resources/views/components/widgets/index.blade.php. Dashboard behavior is in src/Livewire/Dashboard.php and resources/views/livewire/dashboard.blade.php. Discovery and Livewire aliases are registered in src/Aura.php and src/AuraServiceProvider.php.

The focused widget tests are:

- tests/Feature/Widgets/ValueWidgetTest.php
- tests/Feature/Widgets/SparklineTest.php
- tests/Feature/Widgets/CustomTableWidgetTest.php
