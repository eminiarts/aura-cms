# Resource Editor

The Resource Editor allows developers to create, modify, and manage resources directly from the CMS interface. It provides an intuitive UI for editing resource fields, configuring properties, and defining relationships without manually altering code files.

---

## Table of Contents

- [Introduction](#introduction)
- [Enabling the Resource Editor](#enabling-the-resource-editor)
- [Accessing the Resource Editor](#accessing-the-resource-editor)
- [Features and Capabilities](#features-and-capabilities)
  - [Field Management](#field-management)
  - [Conditional Logic](#conditional-logic)
  - [Templates](#templates)
  - [Resource Properties](#resource-properties)
  - [Actions and Utilities](#actions-and-utilities)
- [Using the Resource Editor](#using-the-resource-editor)
  - [Creating a New Resource](#creating-a-new-resource)
  - [Creating a New Field](#creating-a-new-field)
  - [Editing Existing Fields](#editing-existing-fields)
  - [Reordering Fields](#reordering-fields)
  - [Deleting Fields](#deleting-fields)
  - [Adding Conditional Logic](#adding-conditional-logic)
  - [Applying Templates](#applying-templates)
- [Field Properties](#field-properties)
- [Best Practices](#best-practices)
- [Limitations and Considerations](#limitations-and-considerations)

---

## Introduction

The Resource Editor is a Livewire component (`Aura\Base\Livewire\ResourceEditor`) that provides a graphical interface for defining and editing resources. It abstracts the underlying code structure, allowing developers to focus on the resource's configuration rather than its implementation details.

Key components:
- **ResourceEditor** (`src/Livewire/ResourceEditor.php`) - Main editor interface
- **CreateResource** (`src/Livewire/CreateResource.php`) - Modal for creating new resources
- **EditResourceField** (`src/Livewire/EditResourceField.php`) - Slide-over panel for editing field properties

---

## Enabling the Resource Editor

The Resource Editor is controlled by the `resource_editor` feature flag in your configuration. By default, it is **only enabled in local environments**.

```php
// config/aura.php
'features' => [
    'resource_editor' => config('app.env') == 'local' ? true : false,
    // ...
],
```

To enable the Resource Editor in other environments:

```php
'features' => [
    'resource_editor' => true,
],
```

**Warning**: Enabling the Resource Editor in production is not recommended as it modifies PHP class files directly.

---

## Accessing the Resource Editor

To access the Resource Editor:

1. Navigate to the Aura CMS dashboard.
2. In the sidebar, locate the resource you wish to edit.
3. Click on the "Edit Resource" option next to the resource.

Alternatively, navigate directly to: `/admin/resources/{slug}/editor`

**Requirements:**
- The Resource Editor feature must be enabled
- Resources must be defined in the `App` namespace (vendor resources cannot be edited)
- Creating new resources requires **super admin** privileges

---

## Features and Capabilities

### Field Management

- **Add Fields**: Create new fields of various types, including custom fields
- **Edit Fields**: Modify existing fields' properties via a slide-over panel
- **Reorder Fields**: Drag and drop fields to rearrange their order within the resource
- **Duplicate Fields**: Quickly create copies of existing fields with auto-generated slugs
- **Delete Fields**: Remove fields that are no longer needed

### Conditional Logic

- **Define Conditions**: Set up rules to show or hide fields based on the values of other fields
- **Add Condition Groups**: Group multiple conditions together for complex logic (AND/OR)
- **Flexible Operators**: Use operators for comparisons:
  - `==` - Equal to
  - `!=` - Not equal to
  - `>` - Greater than
  - `<` - Less than
  - `>=` - Greater than or equal to
  - `<=` - Less than or equal to
- **Role-Based Conditions**: Show/hide fields based on user roles

### Templates

Aura CMS includes built-in templates to quickly scaffold field structures:

| Template | Description |
|----------|-------------|
| `Plain` | Simple layout without tabs or panels, just a text field |
| `Tabs` | Uses global tabs to group fields into separate sections |
| `TabsWithPanels` | Combines tabs with panels for complex content structures |
| `PanelWithSidebar` | Two-column layout with 70/30 split (main content + sidebar) |

Templates are located in `src/Templates/` and provide predefined field configurations.

### Resource Properties

The Resource Editor allows you to configure:

| Property | Description |
|----------|-------------|
| **Type** | The resource type name (e.g., `Post`, `Page`) - read-only |
| **Slug** | The unique URL identifier for the resource - read-only |
| **Icon** | SVG icon or icon class to represent the resource in navigation |
| **Group** | Organize resources into navigation groups |
| **Dropdown** | Configure dropdown menus for resource navigation |
| **Sort** | Numeric value for ordering resources in navigation |

### Actions and Utilities

- **Generate Migration**: Automatically generate database migration files for custom tables. This also sets `$customTable = true` and configures the `$table` property on the resource class.
- **Delete Resource**: Permanently remove a resource by deleting its PHP class file.
- **Save**: Persist all field and property changes to the resource class file.

---

## Using the Resource Editor

### Creating a New Resource

To create a new resource:

1. Use the global "Create Resource" action in the dashboard
2. Enter the resource name (singular form, e.g., "Post", "Product")
3. The system generates a new resource class in `app/Aura/Resources/`

**Requirements:**
- Super admin privileges
- Non-production environment (this action is disabled in production)

```php
// The CreateResource component enforces these requirements
abort_if(app()->environment('production'), 403);
abort_unless(auth()->user()->isSuperAdmin(), 403);
```

### Creating a New Field

1. Click the **"+ Add Field"** button below the last field
2. Choose the field type from the dropdown list
3. A slide-over panel opens with field configuration options
4. Fill in the required properties:
   - **Name**: The display label for the field
   - **Slug**: The unique identifier (auto-generated from name, can be customized)
   - **Type**: The field class (e.g., `Aura\Base\Fields\Text`)
   - **Validation**: Laravel validation rules
5. Configure additional options based on field type
6. Click **Save**

### Editing Existing Fields

1. Click on any field in the editor to open the slide-over panel
2. Modify the desired properties
3. Click **Save** to persist changes

Changes are written directly to the resource's PHP class file.

### Reordering Fields

- Use the **drag handle** (left side of each field) to drag and drop
- The new order is automatically saved when you drop the field
- Fields maintain their wrapper/parent relationships during reordering

### Deleting Fields

1. Click on the field to open the slide-over panel
2. Click the **Delete** button (red, top-right of the panel)
3. The field is immediately removed

**Warning**: Deleting a field removes it from the resource definition. Any data stored in that field remains in the database but becomes inaccessible.

### Adding Conditional Logic

1. Click on a field to open the slide-over panel
2. Locate the **Conditional Logic** section
3. Add a condition with:
   - **Field**: The slug of the field to check
   - **Operator**: The comparison operator (`==`, `!=`, `>`, `<`, `>=`, `<=`)
   - **Value**: The value to compare against
4. Add multiple conditions for AND logic
5. Create condition groups for OR logic

**Example**: Show "Company Name" only when "Employment Status" equals "employed":

```php
'conditional_logic' => [
    [
        'field' => 'employment_status',
        'operator' => '==',
        'value' => 'employed',
    ],
],
```

You can also use role-based conditions:

```php
'conditional_logic' => [
    [
        'field' => 'role',
        'operator' => '==',
        'value' => 'super_admin',
    ],
],
```

### Applying Templates

When starting with an empty resource, template options appear:

1. **Plain** - Simple layout with a single text field
2. **Tabs** - Tabbed interface for organizing fields
3. **Tabs and Panels** - Complex layout with tabs containing panels

For existing resources with fields:
1. Click **"+ Add Field"** at any position
2. Select from preset options like "Panel with Sidebar (70/30)" or "Simple Panel with Text"

Templates insert predefined field structures with auto-generated slugs to prevent conflicts.

---

## Field Properties

When editing a field, the following properties are available (varies by field type):

| Property | Description |
|----------|-------------|
| `name` | Display label for the field |
| `slug` | Unique identifier (used in code and database) |
| `type` | The field class (e.g., `Aura\Base\Fields\Text`) |
| `validation` | Laravel validation rules (e.g., `required|max:255`) |
| `instructions` | Help text displayed below the field |
| `on_index` | Show this field in table/index views (default: `true`) |
| `on_forms` | Show this field in create/edit forms (default: `true`) |
| `on_view` | Show this field in detail views (default: `true`) |
| `searchable` | Include this field in search queries (default: `false`) |
| `conditional_logic` | Rules for showing/hiding the field |
| `style.width` | Field width as percentage (e.g., `50` for 50%) |

---

## Best Practices

- **Unique Slugs**: Ensure each field slug is unique within the resource. The editor validates this automatically.
- **Validation Rules**: Use Laravel's validation rules to maintain data integrity. Common rules include `required`, `max:255`, `email`, `numeric`.
- **Reserved Words**: The slugs `id` and `type` are reserved and cannot be used.
- **Slug Format**: Slugs must start with a letter, contain only alphanumeric characters, hyphens, and underscores.
- **Organize with Tabs and Panels**: Use structural fields (Tab, Panel) to create logical groupings for complex resources.
- **Backup Before Deletion**: The Resource Editor modifies PHP files directly. Use version control to track changes.
- **Use Global Tabs Sparingly**: Global tabs affect the entire form layout. Use them for major sections.

---

## Limitations and Considerations

- **Vendor Resources**: Resources provided by third-party packages (in the `vendor/` directory) cannot be edited through the Resource Editor.
- **Fields with Closures**: If a resource's `getFields()` method uses closures for dynamic field definitions, the Resource Editor cannot process them. You'll see an error: "Your fields have closures. You can not use the Resource Builder with Closures."
- **Production Environment**: Creating new resources is disabled in production environments by default.
- **File Modifications**: The editor writes directly to PHP class files. Ensure proper file permissions and use version control.
- **Migration Generation**: When generating migrations for custom tables, review the generated migration file before running it. The editor automatically sets `$customTable = true` on your resource class.

---
