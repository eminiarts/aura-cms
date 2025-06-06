# Widgets & Dashboard

Aura CMS provides a powerful widget system for creating interactive dashboards and data visualizations. Built with Livewire and ApexCharts, widgets offer real-time updates, caching, and extensive customization options.

## Table of Contents

- [Overview](#overview)
- [Widget Architecture](#widget-architecture)
- [Built-in Widgets](#built-in-widgets)
- [Dashboard System](#dashboard-system)
- [Creating Custom Widgets](#creating-custom-widgets)
- [Widget Configuration](#widget-configuration)
- [Data Sources](#data-sources)
- [Caching & Performance](#caching--performance)
- [Widget Layouts](#widget-layouts)
- [Advanced Features](#advanced-features)
- [Best Practices](#best-practices)

## Overview

The widget system provides:
- **Real-time Visualizations**: Live data updates with Livewire
- **Multiple Chart Types**: Value, bar, pie, donut, sparklines
- **Date Range Filtering**: Flexible time period selection
- **Automatic Caching**: Performance optimization
- **Responsive Design**: Mobile-friendly layouts
- **Conditional Display**: Show/hide based on logic
- **Custom Widgets**: Extend base classes

> 📹 **Video Placeholder**: [Overview of the widget system showing different widget types, date filtering, and real-time updates]

## Widget Architecture

### Component Structure

```
Widget System
├── Base Widget Class (Livewire Component)
├── Widget Implementations
│   ├── ValueWidget (Metrics)
│   ├── Bar (Bar Charts)
│   ├── Pie (Pie Charts)
│   ├── Donut (Donut Charts)
│   ├── Sparkline (Line Trends)
│   ├── SparklineBar (Bar Trends)
│   └── SparklineArea (Area Trends)
├── Widget Container (Date Management)
└── Dashboard Component

```

### Base Widget Class

```php
namespace Aura\Base\Widgets;

use Livewire\Component;
use Illuminate\Support\Facades\Cache;

abstract class Widget extends Component
{
    public $widget;
    public $model;
    public $start;
    public $end;
    public $loading = true;
    
    protected $listeners = ['dateFilterUpdated' => 'updateDateRange'];
    
    public function getCacheKeyProperty()
    {
        $team = config('aura.teams') ? 
            auth()->user()->current_team_id : 'no-team';
            
        return $team . '-' . $this->widget['slug'] . '-' . 
               $this->start . '-' . $this->end;
    }
    
    public function getCacheDurationProperty()
    {
        return $this->widget['cache']['duration'] ?? 60;
    }
    
    public function format($value)
    {
        if (is_numeric($value)) {
            return number_format($value, 2);
        }
        return $value;
    }
}
```

## Built-in Widgets

### Value Widget

Displays a single metric with comparison to previous period:

```php
[
    'type' => \Aura\Base\Widgets\ValueWidget::class,
    'name' => 'Total Revenue',
    'slug' => 'total-revenue',
    'column' => 'amount',
    'method' => 'sum',
    'style' => ['width' => 25],
]
```

**Features:**
- Aggregation methods: `count`, `sum`, `avg`, `min`, `max`
- Percentage change calculation
- Previous period comparison
- Number formatting

### Bar Chart

Visualizes data in vertical bars:

```php
[
    'type' => \Aura\Base\Widgets\Bar::class,
    'name' => 'Monthly Sales',
    'slug' => 'monthly-sales',
    'column' => 'created_at',
    'method' => 'count',
    'group_by' => 'month',
    'style' => ['width' => 50],
]
```

**Configuration:**
```javascript
// Chart options
{
    chart: {
        type: 'bar',
        height: 350,
        toolbar: { show: false }
    },
    colors: ['#3B82F6'],
    dataLabels: { enabled: false },
    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', ...]
    }
}
```

### Pie Chart

Shows data distribution:

```php
[
    'type' => \Aura\Base\Widgets\Pie::class,
    'name' => 'Category Distribution',
    'slug' => 'category-distribution',
    'taxonomy' => 'categories',
    'style' => ['width' => 33],
]
```

**Features:**
- Automatic color assignment
- Legend display
- Percentage labels
- Hover effects

### Donut Chart

Similar to pie with hollow center:

```php
[
    'type' => \Aura\Base\Widgets\Donut::class,
    'name' => 'Status Overview',
    'slug' => 'status-overview',
    'column' => 'status',
    'style' => ['width' => 33],
]
```

### Sparkline Charts

Compact trend visualizations:

```php
// Line sparkline
[
    'type' => \Aura\Base\Widgets\Sparkline::class,
    'name' => 'Daily Visitors',
    'slug' => 'daily-visitors',
    'column' => 'visits',
    'style' => ['width' => 25],
]

// Bar sparkline
[
    'type' => \Aura\Base\Widgets\SparklineBar::class,
    'name' => 'Weekly Orders',
    'slug' => 'weekly-orders',
    'column' => 'created_at',
    'method' => 'count',
    'style' => ['width' => 25],
]

// Area sparkline
[
    'type' => \Aura\Base\Widgets\SparklineArea::class,
    'name' => 'Revenue Trend',
    'slug' => 'revenue-trend',
    'column' => 'amount',
    'method' => 'sum',
    'style' => ['width' => 25],
]
```

> 📹 **Video Placeholder**: [Demonstration of each widget type with real data and configuration options]

## Dashboard System

### Default Dashboard

The dashboard component provides a customizable landing page:

```php
namespace Aura\Base\Livewire;

class Dashboard extends Component
{
    public function render()
    {
        return view('aura::livewire.dashboard')
            ->layout('aura::components.layout.app');
    }
}
```

### Dashboard Components

```blade
{{-- resources/views/livewire/dashboard.blade.php --}}
<div class="grid grid-cols-12 gap-6">
    {{-- Breadcrumbs --}}
    <x-aura::dashboard.breadcrumbs cols="full" />
    
    {{-- Welcome message --}}
    <x-aura::dashboard.welcome cols="full" />
    
    {{-- Documentation links --}}
    <x-aura::dashboard.docs cols="6"/>
    
    {{-- Quick actions --}}
    <x-aura::dashboard.quick-actions cols="6"/>
    
    {{-- Custom widgets --}}
    @foreach($widgets as $widget)
        <div class="col-span-{{ $widget['cols'] ?? 12 }}">
            @livewire($widget['component'], $widget['params'] ?? [])
        </div>
    @endforeach
</div>
```

### Custom Dashboard

Create a custom dashboard:

```php
namespace App\Livewire;

use Livewire\Component;
use Aura\Base\Facades\Aura;

class CustomDashboard extends Component
{
    public function getWidgetsProperty()
    {
        return collect(Aura::getResources())
            ->filter(fn($resource) => $resource::hasWidgets())
            ->flatMap(fn($resource) => $resource::getWidgets())
            ->take(8);
    }
    
    public function getStatsProperty()
    {
        return [
            'total_users' => User::count(),
            'total_revenue' => Order::sum('total'),
            'pending_orders' => Order::pending()->count(),
            'active_products' => Product::active()->count(),
        ];
    }
    
    public function render()
    {
        return view('livewire.custom-dashboard', [
            'widgets' => $this->widgets,
            'stats' => $this->stats,
        ]);
    }
}
```

Dashboard view:

```blade
{{-- resources/views/livewire/custom-dashboard.blade.php --}}
<div>
    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">Total Users</h3>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_users']) }}</p>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">Total Revenue</h3>
            <p class="text-3xl font-bold text-green-600">${{ number_format($stats['total_revenue'], 2) }}</p>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">Pending Orders</h3>
            <p class="text-3xl font-bold text-yellow-600">{{ $stats['pending_orders'] }}</p>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500">Active Products</h3>
            <p class="text-3xl font-bold text-blue-600">{{ $stats['active_products'] }}</p>
        </div>
    </div>
    
    {{-- Widget Grid --}}
    <div class="grid grid-cols-12 gap-6">
        @foreach($widgets as $widget)
            <div class="col-span-12 lg:col-span-{{ $widget['style']['width'] ?? 100 }}">
                @livewire('aura::widget', [
                    'widget' => $widget,
                    'model' => app($widget['resource']),
                ])
            </div>
        @endforeach
    </div>
</div>
```

## Creating Custom Widgets

### Basic Custom Widget

```php
namespace App\Widgets;

use Aura\Base\Widgets\Widget;
use App\Models\Order;

class RevenueWidget extends Widget
{
    public function getValue($start, $end)
    {
        return Order::whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->sum('total');
    }
    
    public function getChartDataProperty()
    {
        $data = Order::selectRaw('DATE(created_at) as date, SUM(total) as revenue')
            ->whereBetween('created_at', [$this->start, $this->end])
            ->where('status', 'completed')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        return [
            'labels' => $data->pluck('date')->map(fn($date) => 
                Carbon::parse($date)->format('M d')
            ),
            'values' => $data->pluck('revenue'),
        ];
    }
    
    public function render()
    {
        return view('widgets.revenue', [
            'total' => $this->getValue($this->start, $this->end),
            'chartData' => $this->chartData,
        ]);
    }
}
```

Widget view:

```blade
{{-- resources/views/widgets/revenue.blade.php --}}
<div class="bg-white rounded-lg shadow">
    <div class="p-6">
        <h3 class="text-lg font-semibold text-gray-900">{{ $widget['name'] }}</h3>
        
        <div class="mt-4">
            <p class="text-3xl font-bold text-green-600">
                ${{ number_format($total, 2) }}
            </p>
        </div>
        
        <div class="mt-6" wire:ignore>
            <div id="revenue-chart-{{ $widget['slug'] }}"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const options = {
        series: [{
            name: 'Revenue',
            data: @json($chartData['values'])
        }],
        chart: {
            type: 'area',
            height: 200,
            sparkline: { enabled: true }
        },
        stroke: { curve: 'smooth', width: 2 },
        fill: { 
            type: 'gradient',
            gradient: { opacityFrom: 0.5, opacityTo: 0 }
        },
        colors: ['#10B981']
    };
    
    const chart = new ApexCharts(
        document.querySelector("#revenue-chart-{{ $widget['slug'] }}"), 
        options
    );
    chart.render();
    
    // Update on Livewire refresh
    Livewire.on('widgetUpdated', () => {
        chart.updateSeries([{
            data: @json($chartData['values'])
        }]);
    });
});
</script>
@endpush
```

### Advanced Widget with Filters

```php
namespace App\Widgets;

use Aura\Base\Widgets\Widget;

class ProductPerformance extends Widget
{
    public $category = 'all';
    public $metric = 'revenue';
    
    protected $queryString = ['category', 'metric'];
    
    public function updatedCategory()
    {
        $this->emit('widgetUpdated');
    }
    
    public function getDataProperty()
    {
        $query = Product::with(['orders' => function ($q) {
            $q->whereBetween('created_at', [$this->start, $this->end]);
        }]);
        
        if ($this->category !== 'all') {
            $query->where('category_id', $this->category);
        }
        
        return $query->get()->map(function ($product) {
            return [
                'name' => $product->name,
                'value' => $this->calculateMetric($product),
            ];
        })->sortByDesc('value')->take(10);
    }
    
    protected function calculateMetric($product)
    {
        return match($this->metric) {
            'revenue' => $product->orders->sum('pivot.price'),
            'quantity' => $product->orders->sum('pivot.quantity'),
            'orders' => $product->orders->count(),
            default => 0,
        };
    }
    
    public function render()
    {
        return view('widgets.product-performance', [
            'data' => $this->data,
            'categories' => Category::pluck('name', 'id'),
        ]);
    }
}
```

## Widget Configuration

### Resource Widget Registration

```php
namespace App\Aura\Resources;

use Aura\Base\Resource;

class Product extends Resource
{
    public static function getWidgets()
    {
        return [
            [
                'type' => \Aura\Base\Widgets\ValueWidget::class,
                'name' => 'Total Products',
                'slug' => 'total-products',
                'column' => 'id',
                'method' => 'count',
                'style' => ['width' => 25],
                'cache' => ['duration' => 120],
            ],
            [
                'type' => \Aura\Base\Widgets\ValueWidget::class,
                'name' => 'Average Price',
                'slug' => 'average-price',
                'column' => 'price',
                'method' => 'avg',
                'style' => ['width' => 25],
                'format' => 'currency',
            ],
            [
                'type' => \Aura\Base\Widgets\Bar::class,
                'name' => 'Products by Category',
                'slug' => 'products-by-category',
                'group_by' => 'category_id',
                'style' => ['width' => 50],
            ],
            [
                'type' => \App\Widgets\ProductPerformance::class,
                'name' => 'Top Performing Products',
                'slug' => 'top-products',
                'style' => ['width' => 100],
            ],
        ];
    }
}
```

### Widget Options

```php
[
    'type' => Widget::class,           // Widget class
    'name' => 'Widget Title',          // Display name
    'slug' => 'unique-slug',           // Unique identifier
    'column' => 'database_column',     // Column to aggregate
    'method' => 'count',               // Aggregation method
    'group_by' => 'category_id',       // Grouping column
    'taxonomy' => 'categories',        // For taxonomy widgets
    'queryScope' => 'published',       // Model scope
    'format' => 'currency',            // Value formatting
    'cache' => [
        'duration' => 60,              // Minutes
        'tags' => ['products'],        // Cache tags
    ],
    'style' => [
        'width' => 50,                 // Width percentage
        'height' => 300,               // Fixed height
        'class' => 'custom-widget',    // CSS classes
    ],
    'conditional_logic' => [           // Display conditions
        [
            'field' => 'user.role',
            'operator' => '==',
            'value' => 'admin',
        ],
    ],
]
```

### Conditional Display

```php
public static function getWidgets()
{
    $widgets = [
        [
            'type' => ValueWidget::class,
            'name' => 'Admin Only Widget',
            'slug' => 'admin-widget',
            'conditional_logic' => [
                [
                    'field' => 'user.hasRole',
                    'operator' => '==',
                    'value' => 'admin',
                ],
            ],
        ],
    ];
    
    // Filter based on user permissions
    return collect($widgets)->filter(function ($widget) {
        if (!isset($widget['conditional_logic'])) {
            return true;
        }
        
        return app(ConditionalLogic::class)
            ->evaluate($widget['conditional_logic']);
    })->toArray();
}
```

## Data Sources

### Database Queries

```php
public function getValue($start, $end)
{
    // Basic aggregation
    return $this->model->whereBetween('created_at', [$start, $end])
        ->sum('amount');
    
    // With joins
    return $this->model->query()
        ->join('categories', 'products.category_id', '=', 'categories.id')
        ->whereBetween('products.created_at', [$start, $end])
        ->where('categories.active', true)
        ->sum('products.price');
    
    // Meta fields
    return $this->model->query()
        ->whereHas('meta', function ($query) {
            $query->where('key', 'views')
                ->where('value', '>', 100);
        })
        ->count();
}
```

### External APIs

```php
public function getValue($start, $end)
{
    return Cache::remember($this->cacheKey, $this->cacheDuration, function () {
        $response = Http::get('https://api.example.com/metrics', [
            'start' => $this->start->toDateString(),
            'end' => $this->end->toDateString(),
            'metric' => $this->widget['metric'],
        ]);
        
        return $response->json('value');
    });
}
```

### Computed Metrics

```php
public function getValue($start, $end)
{
    $orders = Order::whereBetween('created_at', [$start, $end])->get();
    
    // Calculate conversion rate
    $visitors = Analytics::whereBetween('created_at', [$start, $end])
        ->distinct('session_id')
        ->count();
    
    return $visitors > 0 ? ($orders->count() / $visitors) * 100 : 0;
}
```

## Caching & Performance

### Cache Implementation

```php
class CustomWidget extends Widget
{
    public function getCacheKeyProperty()
    {
        // Include additional parameters in cache key
        return parent::getCacheKeyProperty() . '-' . $this->extraParam;
    }
    
    public function getCacheDurationProperty()
    {
        // Dynamic cache duration
        if ($this->isHistoricalData()) {
            return 60 * 24; // 24 hours for historical data
        }
        
        return 5; // 5 minutes for real-time data
    }
    
    public function getValue($start, $end)
    {
        return Cache::tags(['widgets', $this->widget['slug']])
            ->remember($this->cacheKey, $this->cacheDuration, function () {
                return $this->calculateExpensiveMetric();
            });
    }
    
    public function clearCache()
    {
        Cache::tags(['widgets', $this->widget['slug']])->flush();
    }
}
```

### Performance Optimization

```php
// Eager loading
public function getDataProperty()
{
    return $this->model->with(['category', 'tags'])
        ->whereBetween('created_at', [$this->start, $this->end])
        ->get();
}

// Query optimization
public function getValue($start, $end)
{
    return DB::table('orders')
        ->selectRaw('DATE(created_at) as date, SUM(total) as daily_total')
        ->whereBetween('created_at', [$start, $end])
        ->groupBy('date')
        ->orderBy('date')
        ->get();
}

// Chunking large datasets
public function processLargeDataset()
{
    $this->model->whereBetween('created_at', [$this->start, $this->end])
        ->chunk(1000, function ($records) {
            // Process chunk
        });
}
```

## Widget Layouts

### Grid Layout

```blade
{{-- Widget container with responsive grid --}}
<div class="grid grid-cols-12 gap-6">
    @foreach($widgets as $widget)
        <div class="col-span-12 
            sm:col-span-{{ $widget['style']['sm'] ?? 12 }}
            md:col-span-{{ $widget['style']['md'] ?? 6 }}
            lg:col-span-{{ $widget['style']['lg'] ?? $widget['style']['width'] ?? 4 }}">
            @livewire('widget', ['widget' => $widget])
        </div>
    @endforeach
</div>
```

### Custom Widget Template

```blade
{{-- resources/views/components/widget-card.blade.php --}}
<div {{ $attributes->merge(['class' => 'bg-white rounded-lg shadow']) }}>
    @if($header ?? false)
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">
                {{ $header }}
            </h3>
        </div>
    @endif
    
    <div class="p-6">
        {{ $slot }}
    </div>
    
    @if($footer ?? false)
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $footer }}
        </div>
    @endif
</div>
```

### Widget Groups

```php
// Group related widgets
public static function getWidgetGroups()
{
    return [
        'sales' => [
            'name' => 'Sales Metrics',
            'widgets' => [
                ['type' => ValueWidget::class, 'name' => 'Total Sales', ...],
                ['type' => Bar::class, 'name' => 'Sales by Month', ...],
                ['type' => Pie::class, 'name' => 'Sales by Category', ...],
            ],
        ],
        'inventory' => [
            'name' => 'Inventory Status',
            'widgets' => [
                ['type' => ValueWidget::class, 'name' => 'Stock Value', ...],
                ['type' => Donut::class, 'name' => 'Stock Levels', ...],
            ],
        ],
    ];
}
```

## Advanced Features

### Real-time Updates

```php
class LiveWidget extends Widget
{
    public $refreshInterval = 30; // seconds
    
    public function mount()
    {
        parent::mount();
        $this->pollWidget();
    }
    
    public function pollWidget()
    {
        $this->emit('$refresh');
        $this->dispatchBrowserEvent('widget-updated', [
            'widget' => $this->widget['slug'],
            'value' => $this->getValue($this->start, $this->end),
        ]);
    }
    
    public function render()
    {
        return view('widgets.live')->with([
            'polling' => "wire:poll.{$this->refreshInterval}s=\"pollWidget\"",
        ]);
    }
}
```

### Interactive Widgets

```php
class InteractiveChart extends Widget
{
    public $selectedPoint = null;
    
    protected $listeners = [
        'chartPointClicked' => 'handleChartClick',
    ];
    
    public function handleChartClick($data)
    {
        $this->selectedPoint = $data;
        
        // Load detailed data for selected point
        $this->emit('showDetails', $this->getPointDetails($data));
    }
    
    public function getPointDetails($data)
    {
        return Order::whereDate('created_at', $data['date'])
            ->with(['customer', 'items'])
            ->get();
    }
}
```

### Export Functionality

```php
trait ExportableWidget
{
    public function exportData()
    {
        $data = $this->getData();
        
        return response()->streamDownload(function () use ($data) {
            $csv = fopen('php://output', 'w');
            
            // Headers
            fputcsv($csv, ['Date', 'Value', 'Change']);
            
            // Data rows
            foreach ($data as $row) {
                fputcsv($csv, [
                    $row['date'],
                    $row['value'],
                    $row['change'] . '%',
                ]);
            }
            
            fclose($csv);
        }, $this->widget['slug'] . '-' . now()->format('Y-m-d') . '.csv');
    }
}
```

### Widget Permissions

```php
class SecureWidget extends Widget
{
    public function mount()
    {
        $this->authorize('view', $this->widget);
        parent::mount();
    }
    
    public function authorize($ability, $widget)
    {
        if (!auth()->user()->can($ability . '-widget', $widget['slug'])) {
            abort(403, 'Unauthorized to view this widget');
        }
    }
}
```

> 📹 **Video Placeholder**: [Advanced widget features including real-time updates, interactivity, and exports]

## Best Practices

### 1. Performance

```php
// Use database indexes
Schema::table('orders', function ($table) {
    $table->index(['created_at', 'status']);
    $table->index(['customer_id', 'created_at']);
});

// Optimize queries
public function getValue($start, $end)
{
    return DB::table('orders')
        ->whereBetween('created_at', [$start, $end])
        ->where('status', 'completed')
        ->sum('total');
}

// Cache expensive calculations
public function getComplexMetric()
{
    return Cache::remember($this->cacheKey . '-complex', 3600, function () {
        // Expensive calculation
    });
}
```

### 2. User Experience

```php
// Loading states
public function render()
{
    return view('widgets.custom', [
        'loading' => $this->loading,
        'data' => $this->loading ? [] : $this->getData(),
    ]);
}

// Error handling
public function getValue($start, $end)
{
    try {
        return $this->calculateValue($start, $end);
    } catch (\Exception $e) {
        logger()->error('Widget error: ' . $e->getMessage());
        return 0;
    }
}

// Meaningful empty states
@if($data->isEmpty())
    <div class="text-center py-8 text-gray-500">
        <x-aura::icon name="chart-bar" class="w-12 h-12 mx-auto mb-2" />
        <p>No data available for selected period</p>
    </div>
@endif
```

### 3. Maintainability

```php
// Use constants
class SalesWidget extends Widget
{
    const CACHE_DURATION = 60;
    const DEFAULT_PERIOD = 30;
    const MAX_ITEMS = 10;
    
    public function getCacheDurationProperty()
    {
        return static::CACHE_DURATION;
    }
}

// Document complex logic
/**
 * Calculate the conversion rate based on unique visitors
 * and completed orders within the date range.
 */
public function getConversionRate($start, $end)
{
    // Implementation
}

// Separate concerns
trait WidgetCalculations
{
    public function calculateGrowthRate($current, $previous)
    {
        if ($previous == 0) return 0;
        return (($current - $previous) / $previous) * 100;
    }
}
```

### 4. Testing

```php
namespace Tests\Feature;

use Tests\TestCase;
use App\Widgets\RevenueWidget;
use Livewire\Livewire;

class RevenueWidgetTest extends TestCase
{
    public function test_widget_displays_correct_revenue()
    {
        // Create test data
        Order::factory()->count(5)->create([
            'total' => 100,
            'created_at' => now(),
        ]);
        
        Livewire::test(RevenueWidget::class, [
            'widget' => [
                'name' => 'Test Revenue',
                'slug' => 'test-revenue',
            ],
            'start' => now()->startOfMonth(),
            'end' => now()->endOfMonth(),
        ])
        ->assertSee('$500.00');
    }
}
```

### Pro Tips

1. **Cache Strategically**: Use longer cache durations for historical data
2. **Lazy Load**: Load widget data only when visible
3. **Responsive Design**: Test widgets on mobile devices
4. **Accessibility**: Add ARIA labels to charts
5. **Error Boundaries**: Handle API failures gracefully
6. **Progressive Enhancement**: Show basic data while loading complex charts
7. **Documentation**: Document widget configuration options
8. **Modularity**: Create reusable widget traits

The widget system provides a powerful foundation for building interactive dashboards and data visualizations that help users understand their data at a glance.