# Fields Reference

> 📹 **Video Placeholder**: Complete overview of all 42 Aura CMS field types with live demonstrations

Fields are the building blocks of Aura CMS, transforming your resources into powerful, feature-rich forms and interfaces. With 42 field types available out of the box, you can handle any data requirement from simple text inputs to complex relationships and media management.

## Table of Contents

- [Introduction](#introduction)
- [Field Structure](#field-structure)
- [Common Field Options](#common-field-options)
- [Field Categories](#field-categories)
- [Input Fields](#input-fields)
  - [Text](#text)
  - [Textarea](#textarea)
  - [Number](#number)
  - [Email](#email)
  - [Phone](#phone)
  - [Password](#password)
  - [Slug](#slug)
  - [Date](#date)
  - [Datetime](#datetime)
  - [Time](#time)
- [Choice Fields](#choice-fields)
  - [Boolean](#boolean)
  - [Select](#select)
  - [Radio](#radio)
  - [Checkbox](#checkbox)
  - [Status](#status)
- [Media Fields](#media-fields)
  - [Image](#image)
  - [File](#file)
- [JS Fields](#js-fields)
  - [AdvancedSelect](#advancedselect)
  - [Color](#color)
  - [Code](#code)
  - [Wysiwyg](#wysiwyg)
- [Relationship Fields](#relationship-fields)
  - [BelongsTo](#belongsto)
  - [HasMany](#hasmany)
  - [HasOne](#hasone)
  - [BelongsToMany](#belongstomany)
  - [Tags](#tags)
- [Structure Fields](#structure-fields)
  - [Group](#group)
  - [Repeater](#repeater)
  - [Panel](#panel)
  - [Tab](#tab)
  - [Tabs](#tabs)
- [Layout Fields](#layout-fields)
  - [Heading](#heading)
  - [HorizontalLine](#horizontalline)
  - [View](#view)
  - [ViewValue](#viewvalue)
  - [LivewireComponent](#livewirecomponent)
- [Special Fields](#special-fields)
  - [ID](#id)
  - [Hidden](#hidden)
  - [Embed](#embed)
  - [Json](#json)
  - [Permissions](#permissions)
  - [Roles](#roles)
- [Field Comparison Table](#field-comparison-table)
- [Custom Fields](#custom-fields)
- [Best Practices](#best-practices)

## Introduction

Fields in Aura CMS are PHP classes that handle everything from data input to validation, storage, and display. Each field type is designed for specific use cases, providing a rich set of features:

- **Type-specific UI components** - Optimized interfaces for each data type
- **Built-in validation** - Laravel validation rules with custom validators
- **Conditional logic** - Show/hide fields based on conditions
- **Custom styling** - Tailwind CSS integration
- **Relationship handling** - Automatic relationship management
- **Media integration** - Built-in media manager support
- **Livewire integration** - Real-time updates without page refresh

### Field Architecture

```php
// Every field extends the base Field class
abstract class Field
{
    public $edit = 'aura::fields.text';      // Edit form view
    public $view = 'aura::fields.view-value'; // Display view
    public $index = null;                     // Table/index view
    
    public $type = 'input';                   // Field type category
    public $group = false;                    // Groups other fields?
    public $on_forms = true;                  // Show on forms?
    public $tableColumnType = 'string';       // Database column type
    public $tableNullable = true;             // Nullable in database?
}
```

## Field Structure

Each field in Aura CMS follows a consistent structure with these key components:

### Core Properties

| Property | Description | Default |
|----------|-------------|---------|
| `$edit` | Blade view for edit forms | Field-specific |
| `$view` | Blade view for display | `aura::fields.view-value` |
| `$index` | Blade view for table display | `null` |
| `$type` | Field category (input, relation, etc.) | `'input'` |
| `$group` | Whether field groups other fields | `false` |
| `$on_forms` | Display on create/edit forms | `true` |
| `$optionGroup` | Category in field selector | Field-specific |
| `$tableColumnType` | Database column type | `'string'` |
| `$tableNullable` | Allow NULL in database | `true` |
| `$taxonomy` | Is taxonomy field | `false` |

### Core Methods

```php
// Get value from model
public function get($model, $field)

// Set value on model
public function set($model, $field, $value)

// Display formatted value
public function display($model, $field)

// Transform value before display
public function value($value, $field = [])

// Get field configuration options
public static function getFields(): array

// Get filter options for table
public function filterOptions($field): array

// Check if field is input type
public function isInputField(): bool

// Check if field is relation
public function isRelation(): bool
```

## Common Field Options

All fields share these common configuration options:

```php
[
    'name' => 'Field Label',              // Display name
    'slug' => 'field_slug',               // Unique identifier
    'type' => 'Aura\\Base\\Fields\\Text',  // Field class
    'validation' => 'required|max:255',   // Laravel validation
    'instructions' => 'Help text',        // User guidance
    'default' => 'Default value',         // Default value
    'placeholder' => 'Placeholder text',  // Input placeholder
    'on_index' => true,                   // Show in table
    'on_forms' => true,                   // Show in forms
    'on_view' => true,                    // Show in view
    'on_create' => true,                  // Show on create
    'on_edit' => true,                    // Show on edit
    'searchable' => true,                 // Include in search
    'style' => [                          // Styling options
        'width' => '50',                  // Width percentage
        'wrapper_class' => 'mt-4',        // Wrapper CSS
    ],
    'conditional_logic' => [              // Show/hide rules
        [
            'field' => 'status',
            'operator' => '=',
            'value' => 'active',
        ],
    ],
    'live' => true,                       // Livewire live updates
]
```

## Field Categories

Aura CMS organizes fields into logical categories based on their `$optionGroup` property:

1. **Input Fields** - Basic data entry (Text, Textarea, Number, Email, Phone, Password, Slug, Date, Datetime, Time)
2. **Choice Fields** - Selection from options (Boolean, Select, Radio, Checkbox, Status)
3. **Media Fields** - File handling (Image, File)
4. **JS Fields** - Interactive JavaScript-powered fields (AdvancedSelect, Color, Code, Wysiwyg)
5. **Relationship Fields** - Model relationships (BelongsTo, HasMany, HasOne, BelongsToMany)
6. **Structure Fields** - Form organization (Group, Repeater, Panel, Tab, Tabs)
7. **Layout Fields** - Visual elements (Heading, HorizontalLine, View, ViewValue, LivewireComponent)
8. **Special Fields** - Utility fields (ID, Hidden, Tags, Embed, Json, Permissions, Roles)

## Input Fields

Basic fields for text and numeric data entry.

### Text

The most common field type for single-line text input.

```php
[
    'name' => 'Title',
    'slug' => 'title',
    'type' => 'Aura\\Base\\Fields\\Text',
    'validation' => 'required|string|max:255',
    'placeholder' => 'Enter title...',
    'default' => '',
    'prefix' => 'https://',
    'suffix' => '.com',
    'autocomplete' => 'off',
    'max_length' => 255,
]
```

**Features:**
- Prefix/suffix support
- Character counter
- Autocomplete control
- Real-time validation
- Livewire integration

**Database:** `string` column type

### Textarea

Multi-line text input for longer content.

```php
[
    'name' => 'Description',
    'slug' => 'description',
    'type' => 'Aura\\Base\\Fields\\Textarea',
    'validation' => 'required|string|min:10|max:500',
    'placeholder' => 'Enter description...',
    'default' => '',              // Default value on create
    'rows' => 3,                  // Number of visible rows (default: 3, min: 1)
    'max_length' => 500,          // Maximum character count
]
```

**Features:**
- Adjustable rows
- Character counter with max_length
- Default value support
- Preserves line breaks

**Database:** `text` column type

### Number

Numeric input with validation and formatting.

```php
[
    'name' => 'Price',
    'slug' => 'price',
    'type' => 'Aura\\Base\\Fields\\Number',
    'validation' => 'required|numeric|min:0|max:99999',
    'placeholder' => '0.00',
    'default' => '',              // Default value on create
    'prefix' => '$',              // Display prefix
    'suffix' => 'USD',            // Display suffix
]
```

**Features:**
- Number validation
- Prefix/suffix display
- Casts to integer via `value()` method
- Default value support

**Database:** `integer` column type

**Filter Options:**
- equals / not_equals
- greater_than / less_than
- greater_than_or_equal / less_than_or_equal
- is_empty / is_not_empty

### Email

Specialized input for email addresses.

```php
[
    'name' => 'Email Address',
    'slug' => 'email',
    'type' => 'Aura\\Base\\Fields\\Email',
    'validation' => 'required|email:rfc,dns',
    'placeholder' => 'user@example.com',
    'autocomplete' => 'email',
]
```

**Features:**
- Email validation
- HTML5 email input
- Mobile keyboard optimization
- Autocomplete support

**Database:** `string` column type

### Phone

Phone number input with formatting.

```php
[
    'name' => 'Phone Number',
    'slug' => 'phone',
    'type' => 'Aura\\Base\\Fields\\Phone',
    'validation' => 'required|regex:/^[0-9\-\+\(\)\s]+$/',
    'placeholder' => '+1 (555) 123-4567',
]
```

**Features:**
- Phone number validation
- International format support
- Mobile keyboard optimization

**Database:** `string` column type

### Password

Secure password input with hashing.

```php
[
    'name' => 'Password',
    'slug' => 'password',
    'type' => 'Aura\\Base\\Fields\\Password',
    'validation' => 'required|min:8|confirmed',
    'placeholder' => 'Enter password...',
]
```

**Features:**
- Automatic hashing
- Confirmation field
- Show/hide toggle
- Strength indicator
- Skips empty updates

**Database:** `string` column type

### Slug

Auto-generated URL-friendly slugs.

```php
[
    'name' => 'Slug',
    'slug' => 'slug',
    'type' => 'Aura\\Base\\Fields\\Slug',
    'validation' => 'required|regex:/^[a-zA-Z0-9][a-zA-Z0-9_-]*$/|not_regex:/^[0-9]+$/',
    'based_on' => 'title',      // Required: field to generate slug from
    'custom' => true,           // Allow custom slug input
    'disabled' => true,         // Show as disabled/readonly
    'placeholder' => 'auto-generated-slug',
    'default' => '',
]
```

**Features:**
- Auto-generation from specified field (`based_on`)
- Manual override option when `custom` is enabled
- Uniqueness validation
- Real-time preview
- Disabled state support

**Database:** `string` column type

### Date

Date picker with calendar interface.

```php
[
    'name' => 'Published Date',
    'slug' => 'published_date',
    'type' => 'Aura\\Base\\Fields\\Date',
    'validation' => 'required|date|after:today',
    'format' => 'd.m.Y',              // Storage format (default: d.m.Y)
    'display_format' => 'd.m.Y',      // Display format (default: d.m.Y)
    'enable_input' => true,           // Allow manual input (default: true)
    'maxDate' => 30,                  // Days from today to max date (0-365)
    'weekStartsOn' => 1,              // 0=Sunday to 6=Saturday (default: 1=Monday)
]
```

**Features:**
- Calendar picker
- Configurable storage and display formats (PHP date format)
- Max date constraint in days from today
- Week start customization
- Manual input option

**Database:** `date` column type

**Filter Options:**
- date_is / date_is_not
- date_before / date_after
- date_on_or_before / date_on_or_after
- date_is_empty / date_is_not_empty

### Datetime

Combined date and time picker.

```php
[
    'name' => 'Event Start',
    'slug' => 'event_start',
    'type' => 'Aura\\Base\\Fields\\Datetime',
    'validation' => 'required|date',
    'format' => 'd.m.Y H:i',          // Storage format (default: d.m.Y H:i)
    'display_format' => 'd.m.Y H:i',  // Display format (default: d.m.Y H:i)
    'enable_input' => true,           // Allow manual input (default: true)
    'maxDate' => 30,                  // Days from today to max date
    'minTime' => '09:00',             // Minimum selectable time
    'maxTime' => '18:00',             // Maximum selectable time
    'weekStartsOn' => 1,              // 0=Sunday to 6=Saturday (default: 1=Monday)
]
```

**Features:**
- Combined date/time picker
- Time constraints (minTime, maxTime)
- Configurable storage and display formats
- Week start customization

**Database:** `timestamp` column type

**Filter Options:**
- is / is_not
- before / after
- on_or_before / on_or_after
- is_empty / is_not_empty

### Time

Time-only picker.

```php
[
    'name' => 'Opening Time',
    'slug' => 'opening_time',
    'type' => 'Aura\\Base\\Fields\\Time',
    'validation' => 'required',
    'format' => 'H:i',                // Storage format (default: H:i)
    'display_format' => 'H:i',        // Display format (default: H:i)
    'enable_input' => true,           // Allow manual input (default: true)
    'enable_seconds' => false,        // Show seconds picker (default: false)
    'minTime' => '09:00',             // Minimum selectable time
    'maxTime' => '17:00',             // Maximum selectable time
]
```

**Features:**
- Time picker interface
- Optional seconds support
- Time constraints (minTime, maxTime)
- Manual input option

**Database:** `string` column type

## Choice Fields

Fields for selecting from predefined options.

### Boolean

Toggle switch for true/false values.

```php
[
    'name' => 'Active',
    'slug' => 'is_active',
    'type' => 'Aura\\Base\\Fields\\Boolean',
    'validation' => 'boolean',
    'default' => true,
]
```

**Features:**
- Toggle switch UI
- Casts to boolean automatically via `get()` and `set()` methods
- Default value support
- Livewire integration

**Database:** `string` column type (stores boolean values)

**Display:** Check icon (✓) for true, X icon (✗) for false

### Select

Dropdown selection from options.

```php
[
    'name' => 'Category',
    'slug' => 'category',
    'type' => 'Aura\\Base\\Fields\\Select',
    'validation' => 'required|in:news,blog,tutorial',
    'options' => [
        ['key' => 'news', 'value' => 'News'],
        ['key' => 'blog', 'value' => 'Blog Post'],
        ['key' => 'tutorial', 'value' => 'Tutorial'],
    ],
    'default' => 'blog',              // Default value on create
    'allow_multiple' => false,        // Allow multiple selections
]
```

**Dynamic Options:** Define a method on your model:
```php
public function getCategoryOptions()
{
    return ['option1' => 'Label 1', 'option2' => 'Label 2'];
}
```

**Features:**
- Static or dynamic options via model method
- Multiple selection support with `allow_multiple`
- Options defined as repeater (key/value pairs)

**Database:** `string` column type

**Filter Options:**
- is / is_not
- is_empty / is_not_empty

### Radio

Single selection with radio buttons.

```php
[
    'name' => 'Plan',
    'slug' => 'plan',
    'type' => 'Aura\\Base\\Fields\\Radio',
    'validation' => 'required|in:basic,pro,enterprise',
    'options' => [
        'basic' => 'Basic Plan - $9/mo',
        'pro' => 'Pro Plan - $29/mo',
        'enterprise' => 'Enterprise - Contact us',
    ],
    'default' => 'basic',
]
```

**Features:**
- Radio button group
- Default selection
- Inline or stacked layout

**Database:** `string` column type

### Checkbox

Multiple selection with checkboxes.

```php
[
    'name' => 'Features',
    'slug' => 'features',
    'type' => 'Aura\\Base\\Fields\\Checkbox',
    'validation' => 'array',
    'options' => [
        'api' => 'API Access',
        'support' => 'Priority Support',
        'analytics' => 'Advanced Analytics',
        'export' => 'Data Export',
    ],
    'default' => ['api'],
]
```

**Features:**
- Multiple selection
- Stores as JSON array
- Select all option
- Grid layout support

**Database:** `string` column type (JSON)

### Status

Specialized select with color-coded options.

```php
[
    'name' => 'Status',
    'slug' => 'status',
    'type' => 'Aura\\Base\\Fields\\Status',
    'validation' => 'required',
    'default' => 'draft',             // Default value on create
    'allow_multiple' => false,        // Allow multiple selections
    'options' => [
        ['key' => 'draft', 'value' => 'Draft', 'color' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'],
        ['key' => 'review', 'value' => 'In Review', 'color' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'],
        ['key' => 'published', 'value' => 'Published', 'color' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'],
    ],
]
```

**Available Colors:**
- Blue, Green, Red, Yellow, Indigo, Purple, Pink, Gray, Orange, Teal

Each color includes dark mode variants automatically.

**Features:**
- Color-coded badge display on index and view
- Custom status workflows
- Multiple selection support
- Dynamic options via model method

**Database:** `string` column type

## Media Fields

Fields for handling file uploads and media.

### Image

Image upload with preview and management. Part of the **Media Fields** category.

```php
[
    'name' => 'Featured Image',
    'slug' => 'featured_image',
    'type' => 'Aura\\Base\\Fields\\Image',
    'validation' => 'required|image|max:2048',
    'use_media_manager' => true,      // Enable media manager integration
    'min_files' => 1,                 // Minimum number of files
    'max_files' => 5,                 // Maximum number of files
    'allowed_file_types' => 'jpg, png, gif',  // Comma-separated list of extensions
]
```

**Features:**
- Media manager integration
- Image preview with thumbnails
- Multiple image support
- Min/max file constraints
- File type restrictions (comma-separated extensions)
- Stores as JSON array

**Database:** `string` column type (JSON array of attachment IDs)

### File

General file upload field.

```php
[
    'name' => 'Attachments',
    'slug' => 'attachments',
    'type' => 'Aura\\Base\\Fields\\File',
    'validation' => 'required|file|max:10240',
    'use_media_manager' => true,
    'min_files' => 1,
    'max_files' => 10,
    'allowed_file_types' => [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ],
]
```

**Features:**
- Media manager integration
- Multiple file support
- Type restrictions
- Size limits
- Download links

**Database:** `string` column type (JSON for multiple)

## JS Fields

Interactive JavaScript-powered fields with enhanced UI components.

### AdvancedSelect

Enhanced select with search and AJAX. Part of the **JS Fields** category.

```php
[
    'name' => 'Products',
    'slug' => 'products',
    'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
    'validation' => 'required|array',
    'resource' => 'App\\Resources\\Product',
    'multiple' => true,                     // Allow multiple selections (default: true)
    'create' => true,                       // Allow creating new items
    'return_type' => 'id',                  // 'id' or 'object'
    'polymorphic_relation' => true,         // Use polymorphic relations (default: true)
    'thumbnail' => 'image',                 // Field slug for thumbnail display
    'view_selected' => 'custom.selected',   // Custom view for selected items
    'view_select' => 'custom.select',       // Custom view for dropdown items
    'view_view' => 'custom.view',           // Custom view for display mode
    'view_index' => 'custom.index',         // Custom view for table display
]
```

**Features:**
- AJAX search/loading with 5 results limit
- Create new items inline
- Multiple or single selection
- Polymorphic relationship support
- Custom display views for selected items and dropdown
- Stores as JSON when multiple

**Database:** `string` column type (JSON)

### Color

Color picker with multiple formats. Part of the **JS Fields** category.

```php
[
    'name' => 'Brand Color',
    'slug' => 'brand_color',
    'type' => 'Aura\\Base\\Fields\\Color',
    'validation' => 'required',
    'format' => 'hex',      // Options: hex, rgb, hsl, hsv, cmyk
    'native' => false,      // Use native browser color picker
    'default' => '#3B82F6',
]
```

**Features:**
- Visual color picker (JS-powered)
- Multiple format support (Hex, RGB, HSL, HSV, CMYK)
- Native browser picker option
- Custom color picker when native is disabled

**Database:** `string` column type

### Code

Syntax-highlighted code editor. Part of the **JS Fields** category.

```php
[
    'name' => 'Custom CSS',
    'slug' => 'custom_css',
    'type' => 'Aura\\Base\\Fields\\Code',
    'validation' => 'nullable',
    'language' => 'css',          // Required: html, css, javascript, php, json, yaml, markdown
    'line_numbers' => true,       // Show line numbers
    'min_height' => 200,          // Minimum height in pixels (min: 100)
]
```

**Features:**
- Syntax highlighting
- Line numbers toggle
- Configurable minimum height
- JSON pretty-printing on get

**Supported Languages:**
- HTML, CSS, JavaScript, PHP
- JSON, YAML, Markdown

**Database:** `string` column type

### Wysiwyg

Rich text editor with formatting tools. Part of the **JS Fields** category.

```php
[
    'name' => 'Content',
    'slug' => 'content',
    'type' => 'Aura\\Base\\Fields\\Wysiwyg',
    'validation' => 'required|min:100',
]
```

**Features:**
- Rich text editing
- HTML output
- Standard formatting tools

**Database:** `text` column type

### Code

Syntax-highlighted code editor. Part of the **JS Fields** category.

```php
[
    'name' => 'Custom CSS',
    'slug' => 'custom_css',
    'type' => 'Aura\\Base\\Fields\\Code',
    'validation' => 'nullable',
    'language' => 'css',          // Required: html, css, javascript, php, json, yaml, markdown
    'line_numbers' => true,       // Show line numbers
    'min_height' => 200,          // Minimum height in pixels (min: 100)
]
```

**Features:**
- Syntax highlighting
- Line numbers toggle
- Configurable minimum height
- JSON pretty-printing on get

**Supported Languages:**
- HTML, CSS, JavaScript, PHP
- JSON, YAML, Markdown

**Database:** `string` column type

### Embed

External content embedding.

```php
[
    'name' => 'Video',
    'slug' => 'video_embed',
    'type' => 'Aura\\Base\\Fields\\Embed',
    'validation' => 'required|url',
    'providers' => ['youtube', 'vimeo', 'twitter'],
]
```

**Features:**
- oEmbed support
- Provider restrictions
- Preview display
- Responsive embeds

**Database:** `string` column type

### Json

JSON data editor.

```php
[
    'name' => 'Settings',
    'slug' => 'settings',
    'type' => 'Aura\\Base\\Fields\\Json',
    'validation' => 'nullable|json',
    'default' => '{}',
]
```

**Features:**
- JSON validation
- Pretty formatting
- Syntax highlighting
- Array/object support

**Database:** `text` column type

## Relationship Fields

Fields for managing Eloquent relationships.

### BelongsTo

Many-to-one relationship selector.

```php
[
    'name' => 'Author',
    'slug' => 'user_id',
    'type' => 'Aura\\Base\\Fields\\BelongsTo',
    'validation' => 'required|exists:users,id',
    'resource' => 'Aura\\Base\\Resources\\User',  // Required: Related resource class
]
```

**Features:**
- AJAX search with searchable fields
- Displays linked resource title on index
- Meta field support (searches through meta table)
- Custom table support with optimized queries

**Database:** `bigInteger` column type

### HasMany

One-to-many relationship display.

```php
[
    'name' => 'Comments',
    'slug' => 'comments',
    'type' => 'Aura\\Base\\Fields\\HasMany',
    'resource' => 'App\\Resources\\Comment',  // Required: Related resource class
    'foreign_key' => 'post_id',               // Foreign key for create links
    'column' => 'post_id',                    // Column for direct hasMany relation
    'reverse' => false,                       // Use reverse relationship
    'reverse_slug' => 'field_slug',           // Slug for reverse relationship
]
```

**Features:**
- Related items display as table
- Support for polymorphic relations via `post_relations`
- Reverse relationship support
- Embedded table component for related resources

**Type:** `relation` (not stored in database, uses relationship queries)

### HasOne

One-to-one relationship selector.

```php
[
    'name' => 'Profile',
    'slug' => 'profile',
    'type' => 'Aura\\Base\\Fields\\HasOne',
    'resource' => 'App\\Resources\\Profile',
    'searchable' => true,
    'create' => true,
]
```

**Features:**
- Single relationship
- Create inline
- Advanced select features
- Search functionality

**Extends:** AdvancedSelect field

### BelongsToMany

Many-to-many relationship manager.

```php
[
    'name' => 'Categories',
    'slug' => 'categories',
    'type' => 'Aura\\Base\\Fields\\BelongsToMany',
    'resource' => 'App\\Resources\\Category',
    'pivot_table' => 'post_categories',
    'display_field' => 'name',
]
```

**Features:**
- Pivot table management
- Multiple selection
- Relationship sync
- Custom pivot data

**Type:** `relation` (uses pivot table)

### Tags

Specialized tagging system with taxonomy support.

```php
[
    'name' => 'Tags',
    'slug' => 'tags',
    'type' => 'Aura\\Base\\Fields\\Tags',
    'validation' => 'nullable|array',
    'resource' => 'App\\Resources\\Tag',  // Required: Tag resource class
    'create' => true,                     // Allow creating new tags (default: false)
    'max_tags' => 10,                     // Maximum number of tags
]
```

**Features:**
- Tag creation on-the-fly when `create` is enabled
- Auto-complete from existing tags
- Morphable relationships via `post_relations` table
- Badge display with primary color
- Ordered tags via pivot `order` column

**Database:** Uses polymorphic relationship (`morphToMany` via `post_relations` table)

**Filter Options:**
- contains / does_not_contain

## Structure Fields

Fields for organizing form layout.

### Group

Groups fields without repetition. Part of the **Structure Fields** category.

```php
[
    'name' => 'Contact Information',
    'slug' => 'contact',
    'type' => 'Aura\\Base\\Fields\\Group',
    'fields' => [
        [
            'name' => 'Email',
            'slug' => 'email',
            'type' => 'Aura\\Base\\Fields\\Email',
        ],
        [
            'name' => 'Phone',
            'slug' => 'phone',
            'type' => 'Aura\\Base\\Fields\\Phone',
        ],
    ],
]
```

**Features:**
- Visual grouping with `$group = true`
- No data storage (groups child fields only)
- Nested fields support
- Type: `group`

### Repeater

Repeatable field groups. Part of the **Structure Fields** category.

```php
[
    'name' => 'FAQ Items',
    'slug' => 'faq',
    'type' => 'Aura\\Base\\Fields\\Repeater',
    'validation' => 'array|min:1',
    'min' => 0,               // Minimum entries (default: 0)
    'max' => 10,              // Maximum entries (default: 0 = unlimited)
    'fields' => [
        [
            'name' => 'Question',
            'slug' => 'question',
            'type' => 'Aura\\Base\\Fields\\Text',
        ],
        [
            'name' => 'Answer',
            'slug' => 'answer',
            'type' => 'Aura\\Base\\Fields\\Textarea',
        ],
    ],
]
```

**Features:**
- Add/remove items dynamically
- Min/max entry constraints
- Nested field support with automatic slug prefixing
- JSON storage with automatic encoding/decoding
- Groups child fields (`$group = true`)

**Database:** JSON encoded array (`string` column type)

### Panel

Collapsible content panel. Part of the **Structure Fields** category.

```php
[
    'name' => 'Advanced Settings',
    'slug' => 'advanced',
    'type' => 'Aura\\Base\\Fields\\Panel',
    'fields' => [
        // Panel fields
    ],
]
```

**Features:**
- Groups child fields (`$group = true`)
- Same-level grouping enabled (`$sameLevelGrouping = true`)
- Cannot nest panels inside other panels
- Type: `panel`

### Tab

Individual tab within Tabs container. Part of the **Structure Fields** category.

```php
[
    'name' => 'Content',
    'slug' => 'content_tab',
    'type' => 'Aura\\Base\\Fields\\Tab',
    'fields' => [
        // Tab fields
    ],
]
```

**Features:**
- Tab navigation
- Active state
- Conditional display

**Must be wrapped in Tabs field**

### Tabs

Container for tab fields. Part of the **Structure Fields** category.

```php
[
    'type' => 'Aura\\Base\\Fields\\Tabs',
    'fields' => [
        [
            'name' => 'General',
            'type' => 'Aura\\Base\\Fields\\Tab',
            'fields' => [...],
        ],
        [
            'name' => 'Advanced',
            'type' => 'Aura\\Base\\Fields\\Tab',
            'fields' => [...],
        ],
    ],
]
```

**Features:**
- Tab container
- Auto-wraps tabs
- Responsive design
- State persistence

## Layout Fields

Non-data fields for visual organization.

### Heading

Section heading display. Part of the **Layout Fields** category.

```php
[
    'name' => 'User Settings',
    'type' => 'Aura\\Base\\Fields\\Heading',
]
```

**Features:**
- Visual hierarchy
- No data storage
- No additional configuration needed

### HorizontalLine

Visual separator line. Part of the **Layout Fields** category.

```php
[
    'type' => 'Aura\\Base\\Fields\\HorizontalLine',
]
```

**Features:**
- Visual separation
- No configuration needed

### View

Custom view rendering. Part of the **Layout Fields** category.

```php
[
    'name' => 'Custom Content',
    'type' => 'Aura\\Base\\Fields\\View',
    'view' => 'custom.field.view',
    'data' => ['key' => 'value'],
]
```

**Features:**
- Custom Blade views
- Pass data to view
- Full control

### ViewValue

Display-only field value. Part of the **Layout Fields** category.

```php
[
    'name' => 'Created At',
    'slug' => 'created_at',
    'type' => 'Aura\\Base\\Fields\\ViewValue',
]
```

**Features:**
- Read-only display
- Formatted output
- No editing

### LivewireComponent

Embed Livewire component. Part of the **Layout Fields** category.

```php
[
    'name' => 'Custom Widget',
    'type' => 'Aura\\Base\\Fields\\LivewireComponent',
    'component' => 'custom-widget',
    'params' => ['setting' => 'value'],
]
```

**Features:**
- Full Livewire component
- Interactive features
- Custom parameters

## Special Fields

Utility and system fields.

### ID

Auto-incrementing identifier.

```php
[
    'name' => 'ID',
    'slug' => 'id',
    'type' => 'Aura\\Base\\Fields\\ID',
]
```

**Features:**
- Auto-increment
- Not shown on forms
- Primary key
- Read-only

**Database:** `bigIncrements` column type

### Hidden

Hidden form input.

```php
[
    'name' => 'Type',
    'slug' => 'type',
    'type' => 'Aura\\Base\\Fields\\Hidden',
    'default' => 'post',
]
```

**Features:**
- Not visible
- Stores value
- Default support

### Embed

External content embedding.

```php
[
    'name' => 'Video',
    'slug' => 'video_embed',
    'type' => 'Aura\\Base\\Fields\\Embed',
    'validation' => 'required|url',
]
```

**Features:**
- URL input
- Preview display
- Responsive embeds

**Database:** `string` column type

### Json

JSON data editor.

```php
[
    'name' => 'Settings',
    'slug' => 'settings',
    'type' => 'Aura\\Base\\Fields\\Json',
    'validation' => 'nullable|json',
    'default' => '{}',
]
```

**Features:**
- JSON validation
- Pretty formatting
- Array/object support

**Database:** `text` column type

### Permissions

Permission management field.

```php
[
    'name' => 'Permissions',
    'slug' => 'permissions',
    'type' => 'Aura\\Base\\Fields\\Permissions',
    'resource' => 'App\\Resources\\Permission',
]
```

**Features:**
- Permission grid
- Group by resource
- Select all/none
- Role integration

**Database:** JSON encoded permissions

### Roles

Role assignment with team support.

```php
[
    'name' => 'Roles',
    'slug' => 'roles',
    'type' => 'Aura\\Base\\Fields\\Roles',
    'validation' => 'required|array',
]
```

**Features:**
- Team-aware roles
- Multiple role assignment
- Permission inheritance
- Advanced select UI

**Extends:** AdvancedSelect field

## Field Comparison Table

| Field Type | Input | Storage | $type | $group | Filter |
|------------|-------|---------|-------|--------|--------|
| Text | ✓ | string | input | - | ✓ |
| Textarea | ✓ | text | input | - | ✓ |
| Number | ✓ | integer | input | - | ✓ |
| Email | ✓ | string | input | - | ✓ |
| Date | ✓ | date | input | - | ✓ |
| Datetime | ✓ | timestamp | input | - | ✓ |
| Time | ✓ | string | input | - | - |
| Boolean | ✓ | string | input | - | - |
| Select | ✓ | string | input | - | ✓ |
| Radio | ✓ | string | input | - | - |
| Checkbox | ✓ | string | input | - | - |
| Status | ✓ | string | input | - | - |
| Image | ✓ | string (JSON) | input | - | - |
| File | ✓ | string (JSON) | input | - | - |
| AdvancedSelect | ✓ | string (JSON) | input | - | ✓ |
| Color | ✓ | string | input | - | - |
| Code | ✓ | string | input | - | - |
| Wysiwyg | ✓ | text | input | - | - |
| BelongsTo | ✓ | bigInteger | input | - | - |
| HasMany | - | - | relation | - | - |
| HasOne | ✓ | - | input | - | - |
| BelongsToMany | - | - | relation | - | - |
| Tags | ✓ | - | input | - | ✓ |
| Group | - | - | group | ✓ | - |
| Repeater | ✓ | string (JSON) | input | ✓ | - |
| Panel | - | - | panel | ✓ | - |
| Tab | - | - | input | ✓ | - |
| Tabs | - | - | input | ✓ | - |
| Heading | - | - | input | - | - |
| HorizontalLine | - | - | input | - | - |
| View | - | - | input | - | - |
| ViewValue | - | - | input | - | - |
| LivewireComponent | - | - | input | - | - |
| ID | - | bigIncrements | input | - | - |
| Hidden | ✓ | string | input | - | - |
| Embed | ✓ | string | input | - | - |
| Json | ✓ | text | input | - | - |
| Permissions | ✓ | string (JSON) | input | - | - |
| Roles | ✓ | - | input | - | - |

## Custom Fields

Create your own field types by extending the base Field class:

```php
<?php

namespace App\Fields;

use Aura\Base\Fields\Field;

class RatingField extends Field
{
    // Views
    public $edit = 'fields.rating-edit';
    public $view = 'fields.rating-view';
    
    // Properties
    public $optionGroup = 'Custom Fields';
    public $tableColumnType = 'integer';
    
    // Configuration
    public static function getFields(): array
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Max Stars',
                'slug' => 'max_stars',
                'type' => 'Aura\\Base\\Fields\\Number',
                'default' => 5,
            ],
            [
                'name' => 'Allow Half',
                'slug' => 'allow_half',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'default' => false,
            ],
        ]);
    }
    
    // Value handling
    public function set($model, $field, $value)
    {
        return (int) $value;
    }
    
    // Display
    public function display($model, $field)
    {
        $value = $this->getValue($model, $field);
        $stars = str_repeat('★', $value);
        $empty = str_repeat('☆', $field['max_stars'] - $value);
        
        return $stars . $empty;
    }
}
```

### Registering Custom Fields

Register your custom field in a service provider:

```php
public function boot()
{
    Aura::registerField('rating', RatingField::class);
}
```

## Best Practices

### 1. Field Selection

Choose the right field for your data:

```php
// ❌ Wrong: Using Text for email
[
    'type' => 'Aura\\Base\\Fields\\Text',
    'validation' => 'email',
]

// ✅ Right: Using Email field
[
    'type' => 'Aura\\Base\\Fields\\Email',
    'validation' => 'required|email:rfc,dns',
]
```

### 2. Validation

Always validate user input:

```php
// ❌ Insufficient validation
[
    'type' => 'Aura\\Base\\Fields\\Number',
    'validation' => 'numeric',
]

// ✅ Comprehensive validation
[
    'type' => 'Aura\\Base\\Fields\\Number',
    'validation' => 'required|numeric|min:0|max:100',
]
```

### 3. Conditional Logic

Use conditional logic to create dynamic forms:

```php
[
    'name' => 'Shipping Address',
    'slug' => 'shipping_address',
    'type' => 'Aura\\Base\\Fields\\Textarea',
    'conditional_logic' => [
        [
            'field' => 'needs_shipping',
            'operator' => '=',
            'value' => true,
        ],
    ],
]
```

### 4. Field Organization

Group related fields for better UX:

```php
[
    'name' => 'SEO Settings',
    'type' => 'Aura\\Base\\Fields\\Panel',
    'fields' => [
        ['name' => 'Meta Title', 'type' => 'Text'],
        ['name' => 'Meta Description', 'type' => 'Textarea'],
        ['name' => 'Meta Keywords', 'type' => 'Tags'],
    ],
]
```

### 5. Performance

Consider performance implications:

```php
// ❌ Heavy relationship loading
[
    'type' => 'Aura\\Base\\Fields\\BelongsTo',
    'resource' => 'User',
    // Loads all users immediately
]

// ✅ Optimized with AJAX
[
    'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
    'resource' => 'User',
    'searchable' => true,
    // Loads users on demand
]
```

### 6. User Experience

Provide helpful guidance:

```php
[
    'name' => 'API Key',
    'slug' => 'api_key',
    'type' => 'Aura\\Base\\Fields\\Text',
    'instructions' => 'Find your API key in Settings > Developer',
    'placeholder' => 'sk_live_...',
    'validation' => 'required|regex:/^sk_live_/',
]
```

## Field Development Tips

### 1. Custom Validation

Add custom validation rules:

```php
public function validate($value, $field)
{
    if ($field['unique_per_user'] ?? false) {
        $exists = Model::where('user_id', auth()->id())
            ->where($field['slug'], $value)
            ->exists();
            
        if ($exists) {
            throw new ValidationException('This value already exists.');
        }
    }
    
    return parent::validate($value, $field);
}
```

### 2. Dynamic Options

Load options dynamically:

```php
public function options($field)
{
    if (isset($field['options_from'])) {
        $method = $field['options_from'];
        return $this->model->$method();
    }
    
    return $field['options'] ?? [];
}
```

### 3. Custom Storage

Handle complex data storage:

```php
public function set($model, $field, $value)
{
    // Store as JSON
    if ($field['store_as_json'] ?? false) {
        return json_encode($value);
    }
    
    // Store in pivot table
    if ($field['use_pivot'] ?? false) {
        $model->saved(function($model) use ($field, $value) {
            $model->{$field['slug']}()->sync($value);
        });
        return null; // Don't store in model
    }
    
    return $value;
}
```

### 4. Enhanced Display

Create rich displays:

```php
public function display($model, $field)
{
    $value = $this->getValue($model, $field);
    
    // Return HTML
    if ($field['display_as_badge'] ?? false) {
        $color = $field['badge_color'] ?? 'blue';
        return "<span class=\"badge badge-{$color}\">{$value}</span>";
    }
    
    // Return view
    if ($field['custom_view'] ?? false) {
        return view($field['custom_view'], [
            'value' => $value,
            'field' => $field,
            'model' => $model,
        ])->render();
    }
    
    return $value;
}
```

## Summary

Aura CMS provides a comprehensive field system that handles virtually any data requirement:

- **42 field types** out of the box
- **Consistent API** across all fields
- **Built-in validation** with Laravel rules
- **Conditional logic** for dynamic forms
- **Relationship handling** with Eloquent
- **Media management** integration
- **Livewire support** for reactivity
- **Extensible architecture** for custom fields

### Complete Field List

| Category | Fields |
|----------|--------|
| **Input Fields** | Text, Textarea, Number, Email, Phone, Password, Slug, Date, Datetime, Time |
| **Choice Fields** | Boolean, Select, Radio, Checkbox, Status |
| **Media Fields** | Image, File |
| **JS Fields** | AdvancedSelect, Color, Code, Wysiwyg |
| **Relationship Fields** | BelongsTo, HasMany, HasOne, BelongsToMany |
| **Structure Fields** | Group, Repeater, Panel, Tab, Tabs |
| **Layout Fields** | Heading, HorizontalLine, View, ViewValue, LivewireComponent |
| **Special Fields** | ID, Hidden, Tags, Embed, Json, Permissions, Roles |

Whether you're building a simple blog or a complex enterprise application, Aura's field system provides the flexibility and power you need while maintaining a clean, intuitive API.

> 📹 **Video Placeholder**: Building custom fields and extending the Aura CMS field system

For more information on using fields in resources, see the [Resources Documentation](resources.md) and [Creating Resources Guide](creating-resources.md).