# Fields Reference

> 📹 **Video Placeholder**: Complete overview of all 40+ Aura CMS field types with live demonstrations

Fields are the building blocks of Aura CMS, transforming your resources into powerful, feature-rich forms and interfaces. With over 40 field types available out of the box, you can handle any data requirement from simple text inputs to complex relationships and media management.

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
- [Date & Time Fields](#date--time-fields)
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
- [Rich Content Fields](#rich-content-fields)
  - [Wysiwyg](#wysiwyg)
  - [Code](#code)
  - [Embed](#embed)
  - [Json](#json)
- [Relationship Fields](#relationship-fields)
  - [BelongsTo](#belongsto)
  - [HasMany](#hasmany)
  - [HasOne](#hasone)
  - [BelongsToMany](#belongstomany)
  - [Tags](#tags)
- [Advanced Fields](#advanced-fields)
  - [AdvancedSelect](#advancedselect)
  - [Color](#color)
  - [Permissions](#permissions)
  - [Roles](#roles)
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

Aura CMS organizes fields into logical categories:

1. **Input Fields** - Basic data entry (Text, Number, Email, etc.)
2. **Date & Time Fields** - Temporal data (Date, Datetime, Time)
3. **Choice Fields** - Selection from options (Select, Radio, Checkbox, etc.)
4. **Media Fields** - File handling (Image, File)
5. **Rich Content Fields** - Advanced content (Wysiwyg, Code, Json)
6. **Relationship Fields** - Model relationships (BelongsTo, HasMany, etc.)
7. **Advanced Fields** - Complex functionality (AdvancedSelect, Tags, etc.)
8. **Structure Fields** - Form organization (Group, Repeater, Panel, Tab)
9. **Layout Fields** - Visual elements (Heading, HorizontalLine)
10. **Special Fields** - Utility fields (ID, Hidden, View)

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
    'rows' => 5,
    'max_length' => 500,
    'live' => true,
]
```

**Features:**
- Adjustable rows
- Character counter
- Auto-resize option
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
    'prefix' => '$',
    'suffix' => 'USD',
    'step' => 0.01,
    'min' => 0,
    'max' => 99999,
]
```

**Features:**
- Number validation
- Min/max constraints
- Step increments
- Prefix/suffix display
- Casts to integer/float

**Database:** `integer` column type

**Filter Options:**
- equals / not equals
- greater than / less than
- greater than or equal / less than or equal
- is empty / is not empty

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
    'validation' => 'required|unique:posts,slug',
    'based_on' => 'title',
    'custom' => false,
    'disabled' => false,
    'placeholder' => 'auto-generated-slug',
]
```

**Features:**
- Auto-generation from field
- Manual override option
- Uniqueness validation
- Real-time preview

**Database:** `string` column type

## Date & Time Fields

Specialized fields for temporal data.

### Date

Date picker with calendar interface.

```php
[
    'name' => 'Published Date',
    'slug' => 'published_date',
    'type' => 'Aura\\Base\\Fields\\Date',
    'validation' => 'required|date|after:today',
    'format' => 'Y-m-d',
    'display_format' => 'd.m.Y',
    'enable_input' => true,
    'maxDate' => '+1 year',
    'weekStartsOn' => 1,
]
```

**Features:**
- Calendar picker
- Multiple date formats
- Min/max date constraints
- Week start customization
- Manual input option

**Database:** `date` column type

**Filter Options:**
- date is / is not
- date before / after
- date on or before / after
- date is empty / is not empty

### Datetime

Combined date and time picker.

```php
[
    'name' => 'Event Start',
    'slug' => 'event_start',
    'type' => 'Aura\\Base\\Fields\\Datetime',
    'validation' => 'required|date',
    'format' => 'Y-m-d H:i:s',
    'display_format' => 'd.m.Y H:i',
    'enable_input' => true,
    'minTime' => '09:00',
    'maxTime' => '18:00',
    'weekStartsOn' => 1,
]
```

**Features:**
- Combined date/time picker
- Time constraints
- Timezone handling
- Multiple formats

**Database:** `timestamp` column type

**Filter Options:**
- is / is not
- before / after
- on or before / after
- is empty / is not empty

### Time

Time-only picker.

```php
[
    'name' => 'Opening Time',
    'slug' => 'opening_time',
    'type' => 'Aura\\Base\\Fields\\Time',
    'validation' => 'required',
    'format' => 'H:i:s',
    'display_format' => 'H:i',
    'enable_input' => true,
    'enable_seconds' => false,
    'minTime' => '09:00',
    'maxTime' => '17:00',
]
```

**Features:**
- Time picker interface
- Optional seconds
- Time constraints
- 12/24 hour format

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
- Casts to boolean
- Default value support
- Livewire integration

**Database:** `string` column type (stores 'true'/'false')

**Display:** ✓ for true, ✗ for false

### Select

Dropdown selection from options.

```php
[
    'name' => 'Category',
    'slug' => 'category',
    'type' => 'Aura\\Base\\Fields\\Select',
    'validation' => 'required|in:news,blog,tutorial',
    'options' => [
        'news' => 'News',
        'blog' => 'Blog Post',
        'tutorial' => 'Tutorial',
    ],
    'default' => 'blog',
    'placeholder' => 'Select category...',
]
```

**Features:**
- Static or dynamic options
- Placeholder support
- Multiple selection
- Option groups

**Database:** `string` column type

**Filter Options:**
- is / is not
- is empty / is not empty

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
    'options' => [
        'draft' => ['label' => 'Draft', 'color' => 'gray'],
        'review' => ['label' => 'In Review', 'color' => 'yellow'],
        'published' => ['label' => 'Published', 'color' => 'green'],
        'archived' => ['label' => 'Archived', 'color' => 'red'],
    ],
    'default' => 'draft',
]
```

**Features:**
- Color-coded badges
- Predefined colors (blue, green, red, yellow, indigo, purple, pink, gray, orange, teal)
- Custom status workflows
- Visual distinction

**Database:** `string` column type

## Media Fields

Fields for handling file uploads and media.

### Image

Image upload with preview and management.

```php
[
    'name' => 'Featured Image',
    'slug' => 'featured_image',
    'type' => 'Aura\\Base\\Fields\\Image',
    'validation' => 'required|image|max:2048',
    'use_media_manager' => true,
    'min_files' => 1,
    'max_files' => 5,
    'allowed_file_types' => ['image/jpeg', 'image/png', 'image/webp'],
]
```

**Features:**
- Media manager integration
- Drag & drop upload
- Image preview
- Multiple images
- Thumbnail generation
- Format validation

**Database:** `string` column type (JSON for multiple)

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

## Rich Content Fields

Advanced fields for complex content.

### Wysiwyg

Rich text editor with formatting tools.

```php
[
    'name' => 'Content',
    'slug' => 'content',
    'type' => 'Aura\\Base\\Fields\\Wysiwyg',
    'validation' => 'required|min:100',
    'toolbar' => ['bold', 'italic', 'link', 'bullist', 'numlist'],
    'height' => 400,
]
```

**Features:**
- Rich text editing (Quill.js)
- Customizable toolbar
- Media embedding
- HTML output
- Link management

**Database:** `text` column type

### Code

Syntax-highlighted code editor.

```php
[
    'name' => 'Custom CSS',
    'slug' => 'custom_css',
    'type' => 'Aura\\Base\\Fields\\Code',
    'validation' => 'nullable',
    'language' => 'css',
    'theme' => 'monokai',
    'line_numbers' => true,
    'min_height' => '200px',
]
```

**Features:**
- Syntax highlighting (Monaco editor)
- Multiple language support
- Line numbers
- Theme selection
- Auto-formatting

**Supported Languages:**
- HTML, CSS, JavaScript, PHP
- JSON, YAML, Markdown
- SQL, Python, Ruby, Java
- And many more...

**Database:** `text` column type

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
    'resource' => 'Aura\\Base\\Resources\\User',
    'display_field' => 'name',
    'searchable' => true,
]
```

**Features:**
- AJAX search
- Relationship management
- Meta field support
- Custom display
- Eager loading

**Database:** `bigInteger` column type

### HasMany

One-to-many relationship display.

```php
[
    'name' => 'Comments',
    'slug' => 'comments',
    'type' => 'Aura\\Base\\Fields\\HasMany',
    'resource' => 'App\\Resources\\Comment',
    'foreign_key' => 'post_id',
    'display_field' => 'content',
    'sortable' => true,
]
```

**Features:**
- Related items display
- Inline management
- Sorting support
- Pagination
- Add/remove items

**Type:** `relation` (not stored in database)

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

Specialized tagging system.

```php
[
    'name' => 'Tags',
    'slug' => 'tags',
    'type' => 'Aura\\Base\\Fields\\Tags',
    'validation' => 'nullable|array',
    'resource' => 'App\\Resources\\Tag',
    'create' => true,
    'max_tags' => 10,
]
```

**Features:**
- Tag creation on-the-fly
- Auto-complete
- Tag management
- Morphable relationships
- Badge display

**Database:** Uses polymorphic relationship

## Advanced Fields

Complex fields with advanced functionality.

### AdvancedSelect

Enhanced select with search and AJAX.

```php
[
    'name' => 'Products',
    'slug' => 'products',
    'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
    'validation' => 'required|array',
    'resource' => 'App\\Resources\\Product',
    'multiple' => true,
    'searchable' => true,
    'create' => true,
    'min_items' => 1,
    'max_items' => 5,
    'return_type' => 'id', // or 'object'
    'polymorphic_relation' => false,
]
```

**Features:**
- AJAX search/loading
- Create new items
- Multiple selection
- Polymorphic support
- Custom display
- Min/max constraints

**Database:** `string` column type (JSON)

### Color

Color picker with multiple formats.

```php
[
    'name' => 'Brand Color',
    'slug' => 'brand_color',
    'type' => 'Aura\\Base\\Fields\\Color',
    'validation' => 'required',
    'format' => 'hex', // hex, rgb, hsl, hsv, cmyk
    'native' => false,
    'default' => '#3B82F6',
]
```

**Features:**
- Visual color picker
- Multiple format support
- Native browser picker option
- Preset colors
- Alpha channel support

**Database:** `string` column type

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

## Structure Fields

Fields for organizing form layout.

### Group

Groups fields without repetition.

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
- Visual grouping
- No data storage
- Collapsible sections
- Nested fields
- Border options

### Repeater

Repeatable field groups.

```php
[
    'name' => 'FAQ Items',
    'slug' => 'faq',
    'type' => 'Aura\\Base\\Fields\\Repeater',
    'validation' => 'array|min:1',
    'min' => 1,
    'max' => 10,
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
- Add/remove items
- Reorder items
- Min/max constraints
- Nested field support
- JSON storage

**Database:** JSON encoded array

### Panel

Collapsible content panel.

```php
[
    'name' => 'Advanced Settings',
    'slug' => 'advanced',
    'type' => 'Aura\\Base\\Fields\\Panel',
    'collapsed' => true,
    'fields' => [
        // Panel fields
    ],
]
```

**Features:**
- Collapsible UI
- Default state
- Visual separation
- Cannot nest panels
- Icon support

### Tab

Individual tab within Tabs container.

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
- Icon support
- Conditional display

**Must be wrapped in Tabs field**

### Tabs

Container for tab fields.

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

Section heading display.

```php
[
    'name' => 'User Settings',
    'type' => 'Aura\\Base\\Fields\\Heading',
    'level' => 2, // h1-h6
    'icon' => 'settings',
]
```

**Features:**
- Visual hierarchy
- No data storage
- Icon support
- Custom styling

### HorizontalLine

Visual separator line.

```php
[
    'type' => 'Aura\\Base\\Fields\\HorizontalLine',
    'margin' => 'my-4',
]
```

**Features:**
- Visual separation
- Custom margins
- No configuration needed

### View

Custom view rendering.

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

Display-only field value.

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

Embed Livewire component.

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

## Field Comparison Table

| Field Type | Input | Storage | Validation | Search | Filter | Relationship |
|------------|-------|---------|------------|--------|--------|--------------|
| Text | ✓ | string | ✓ | ✓ | ✓ | - |
| Textarea | ✓ | text | ✓ | ✓ | ✓ | - |
| Number | ✓ | integer | ✓ | ✓ | ✓ | - |
| Email | ✓ | string | ✓ | ✓ | ✓ | - |
| Boolean | ✓ | string | ✓ | - | ✓ | - |
| Select | ✓ | string | ✓ | - | ✓ | - |
| Image | ✓ | string/json | ✓ | - | - | - |
| BelongsTo | ✓ | bigInteger | ✓ | ✓ | ✓ | ✓ |
| HasMany | - | - | - | - | - | ✓ |
| Repeater | ✓ | json | ✓ | - | - | - |
| Tab | - | - | - | - | - | - |

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

- **40+ field types** out of the box
- **Consistent API** across all fields
- **Built-in validation** with Laravel rules
- **Conditional logic** for dynamic forms
- **Relationship handling** with Eloquent
- **Media management** integration
- **Livewire support** for reactivity
- **Extensible architecture** for custom fields

Whether you're building a simple blog or a complex enterprise application, Aura's field system provides the flexibility and power you need while maintaining a clean, intuitive API.

> 📹 **Video Placeholder**: Building custom fields and extending the Aura CMS field system

For more information on using fields in resources, see the [Resources Documentation](resources.md) and [Creating Resources Guide](creating-resources.md).