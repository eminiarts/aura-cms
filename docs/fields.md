# Fields in Aura CMS

Fields are fundamental building blocks in Aura CMS that define the attributes and data types of resources. They determine how data is inputted, validated, stored, and displayed within the system. Understanding fields is crucial for customizing resources and tailoring the CMS to meet specific application requirements.

## Introduction to Fields

Fields in Aura CMS are powerful, reusable components that handle various aspects of data management:

- **Data Input**: Provide intuitive interfaces for users to enter data
- **Validation**: Ensure data integrity through built-in validation rules
- **Storage**: Handle data persistence and retrieval
- **Display**: Format and present data in views
- **Relationships**: Manage connections between different resources
- **Organization**: Structure forms and content logically

Each field type is designed to handle specific types of data and user interactions, from simple text inputs to complex relationship management.

## Field Structure

Each field in Aura CMS extends the base `Field` class, which provides a consistent interface and shared functionality across all field types.

### Core Components

1. View Templates
   - `$edit`: Blade view for form input
   - `$view`: Blade view for displaying values
   - `$index`: Blade view for list/table display

2. Data Handling
   - `get()`: Retrieve field value
   - `set()`: Store field value
   - `validate()`: Apply validation rules
   - `format()`: Format value for display

3. Database Integration
   - `$tableColumnType`: Database column type
   - `$tableNullable**: Column nullable status
   - `$cast`: Laravel attribute casting

### Field Categories

Fields are organized into logical categories based on their functionality:

#### Input Fields
Basic data input fields for common data types:
- Text, Textarea, Number, Email, Password
- Date, Time, Datetime
- Boolean, Select, Radio, Checkbox

#### Media Fields
Fields for handling file uploads and media:
- Image
- File
- Gallery

#### Relationship Fields
Fields that manage connections between resources:
- BelongsTo
- HasMany
- BelongsToMany
- MorphMany

#### Layout Fields
Fields for organizing and structuring forms:
- Group
- Repeater
- Tab
- Panel
- Heading
- HorizontalLine

#### Rich Content Fields
Fields for enhanced content editing:
- Wysiwyg
- Code
- Markdown
- JSON

## Common Features

All fields in Aura CMS share a set of common features and capabilities:

### Validation
```php
'validation' => 'required|min:3|max:255'
```

### Conditional Display
```php
'conditional_logic' => [
    'field' => 'status',
    'value' => 'active'
]
```

### Visibility Control
```php
'on_index' => true,
'on_detail' => true,
'on_forms' => true
```

### Styling Options
```php
'style' => [
    'width' => '50'  // Number between 0 and 100 representing percentage width
]
```

### Help Text
```php
'instructions' => 'Enter a unique identifier for this resource',
'placeholder' => 'e.g., unique-identifier'
```

---

## Field Types

Below are detailed descriptions of all available field types in Aura CMS:

---

## Table of Contents

- [Introduction to Fields](#introduction-to-fields)
- [Field Structure](#field-structure)
  - [Key Properties](#key-properties)
  - [Common Methods](#common-methods)
  - [Options](#field-options)
- [Field Categories](#field-categories)
  - [Input Fields](#input-fields)
  - [Choice Fields](#choice-fields)
  - [Media Fields](#media-fields)
  - [Relationship Fields](#relationship-fields)
  - [Structure Fields](#structure-fields)
  - [JS Fields](#js-fields)
  - [Layout Fields](#layout-fields)
- [Field Types](#field-types)
  - [Text](#text)
  - [Textarea](#textarea)
  - [Number](#number)
  - [Email](#email)
  - [Password](#password)
  - [Date](#date)
  - [Datetime](#datetime)
  - [Time](#time)
  - [Boolean](#boolean)
  - [Select](#select)
  - [Checkbox](#checkbox)
  - [Radio](#radio)
  - [Status](#status)
  - [Advanced Select](#advanced-select)
  - [Image](#image)
  - [File](#file)
  - [BelongsTo](#belongsto)
  - [HasMany](#hasmany)
  - [Group](#group)
  - [Repeater](#repeater)
  - [Tab](#tab)
  - [Panel](#panel)
  - [Wysiwyg](#wysiwyg)
  - [Code](#code)
  - [Color](#color)
  - [Embed](#embed)
  - [Heading](#heading)
  - [Horizontal Line](#horizontal-line)
- [Customizing Fields](#customizing-fields)
  - [Defining Custom Fields](#defining-custom-fields)
  - [Field Options and Attributes](#field-options-and-attributes)
- [References](#references)

---

<a name="introduction-to-fields"></a>
## Introduction to Fields

Fields in Aura CMS are classes that represent the various types of data that can be associated with a resource. They handle the rendering of form inputs, validation, data storage, and display logic. Fields are designed to be extensible and customizable, allowing developers to create rich and dynamic forms with ease.

*Figure 1: Field Integration within a Resource*

![Figure 1: Field Integration](placeholder-image.png)

---

<a name="field-structure"></a>
## Field Structure

Each field in Aura CMS is a class that extends the base `Field` class. This base class provides common functionality and enforces a consistent interface across all field types.

<a name="key-properties"></a>
### Key Properties

- **$edit**: The Blade view used to render the field in edit forms.
- **$view**: The Blade view used to display the field value.
- **$optionGroup**: The category group the field belongs to.
- **$type**: The type of the field (e.g., input, relation, group).
- **$tableColumnType**: The database column type associated with the field.
- **$tableNullable**: Whether the database column allows null values.
- **$group**: Indicates if the field is a grouping field (e.g., Tab, Panel).
- **$taxonomy**: Specifies if the field is used for taxonomy relations.

<a name="common-methods"></a>
### Common Methods

- **getFields()**: Returns the configuration options for the field.
- **get()**: Retrieves the field value from the model.
- **set()**: Processes and sets the field value to the model.
- **display()**: Renders the field value for display purposes.
- **isRelation()**: Determines if the field represents a relationship.
- **isInputField()**: Checks if the field is an input type.
- **filterOptions()**: Provides filter options for the field in queries.

<a name="field-options"></a>
### Options

These options are used to configure the field and are passed to the field class. They are available on most fields.

| Option | Description |
|--------|-------------|
| `name` | The display name of the field |
| `slug` | The unique identifier for the field |
| `conditional_logic` | Conditions for displaying the field |
| `validation` | Laravel validation rules for the field |
| `type` | The field class or type |
| `instructions` | Help text or instructions for users |
| `searchable` | Whether the field is searchable |
| `on_index` | Whether to display the field on the index page |
| `on_forms` | Whether to display the field on forms |
| `on_view` | Whether to display the field on the view page |
| `on_edit` | Whether to display the field on the edit page |
| `on_create` | Whether to display the field on the create page |
| `style.width` | The width styling option for the field |
| `live` | Whether to enable real-time updates using Livewire's wire:model.live |


---

<a name="field-categories"></a>
## Field Categories

Fields are grouped into several categories based on their functionality:

<a name="input-fields"></a>
### Input Fields

Basic fields for data input.

- **Text**
- **Textarea**
- **Number**
- **Email**
- **Password**
- **Date**
- **Datetime**
- **Time**
- **Slug**
- **Phone**

<a name="choice-fields"></a>
### Choice Fields

Fields that offer predefined options.

- **Select**
- **Checkbox**
- **Radio**
- **Status**
- **Boolean**

<a name="media-fields"></a>
### Media Fields

Fields for handling media files.

- **Image**
- **File**

<a name="relationship-fields"></a>
### Relationship Fields

Fields that define relationships between resources.

- **BelongsTo**
- **HasMany**
- **BelongsToMany**
- **HasOne**
- **AdvancedSelect**

<a name="structure-fields"></a>
### Structure Fields

Fields that organize other fields.

- **Group**
- **Repeater**
- **Tab**
- **Panel**

<a name="js-fields"></a>
### JS Fields

Fields that rely on JavaScript components.

- **Wysiwyg**
- **Code**
- **Color**
- **AdvancedSelect**

<a name="layout-fields"></a>
### Layout Fields

Fields that enhance form layout.

- **Heading**
- **HorizontalLine**
- **View**
- **LivewireComponent**

---

<a name="field-types"></a>
## Field Types

Below is a detailed explanation of each field type, including their properties and usage.

<a name="text"></a>
### Text

**Class**: `Aura\Base\Fields\Text`

The Text field creates a regular text input, allowing users to enter a single line of text. It's suitable for short, unformatted text entries such as names, titles, or brief descriptions. The field supports real-time updates through Livewire and includes features like prefixes, suffixes, and autocomplete.

<div class="dark:hidden">
<img src="/images/Fields/Text.png" alt="Text Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/Text_dark.png" alt="Text Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Title',
    'slug' => 'title',
    'type' => 'Aura\\Base\\Fields\\Text',
    'validation' => 'required|string|max:255',
    'placeholder' => 'Enter the title',
],
```

#### Additional Options

Here's a table summarizing additional options for the Text field:

| Option | Description |
|--------|-------------|
| `default_value` | Sets a default value for the field |
| `placeholder` | Displays placeholder text when the field is empty |
| `autocomplete` | Controls browser autocomplete behavior |
| `prefix` | Displays text or icon before the input field |
| `suffix` | Displays text or icon after the input field |
| `live` | Enables real-time updates using Livewire's live wire:model |

**Example with additional options:**

```php
[
    'name' => 'Username',
    'slug' => 'username',
    'type' => 'Aura\\Base\\Fields\\Text',
    'validation' => 'required|string|max:255',
    'placeholder' => 'Enter your username',
    'autocomplete' => 'username',
    'prefix' => '@',
    'live' => true,
    'default_value' => '',
],
```

#### Implementation Details

The Text field is implemented using the following components:

1. Field Wrapper
   - Provides consistent field layout
   - Handles label and error display
   - Manages field width and spacing

2. Input Component
   - Basic text input with enhanced features
   - Supports prefixes and suffixes
   - Handles disabled states
   - Integrates with Livewire for real-time updates

3. Livewire Integration
   ```php
   // Live mode (real-time updates)
   wire:model.live="form.fields.field_name"

   // Normal mode (update on change)
   wire:model="form.fields.field_name"
   ```

#### Filter Options

The Text field provides several filter options for querying:

- equals
- not equals
- contains
- does not contain
- starts with
- ends with
- is empty
- is not empty

<a name="textarea"></a>
### Textarea

**Class**: `Aura\Base\Fields\Textarea`

A textarea element that creates a multiline text input field, suitable for entering and displaying unformatted text across multiple lines. It allows users to input larger amounts of text, such as descriptions, comments, or any content that may span several lines. Unlike single-line text inputs, textareas can accommodate line breaks and preserve formatting, making them ideal for longer-form content entry.

<div class="dark:hidden">
<img src="/images/Fields/Textarea.png" alt="Textarea Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/Textarea_dark.png" alt="Textarea Field (Dark Mode)" style="max-width: 600px;">
</div>

**Key Attributes**:

- **`rows`**: Number of visible text lines.
- **`max_length`**: Maximum number of characters.

**Example**:

```php
[
    'name' => 'Description',
    'slug' => 'description',
    'type' => 'Aura\\Base\\Fields\\Textarea',
    'validation' => 'required|string|min:10',
    'placeholder' => 'Enter a detailed description',
    'rows' => 5,
],
```

#### Additional Options

Here's a table summarizing additional options for the Textarea field:

| Option | Description |
|--------|-------------|
| `default_value` | Sets a default value for the field |
| `placeholder` | Displays placeholder text when the field is empty |
| `rows` | Number of visible text rows (default: 3) |
| `max_length` | Maximum number of characters allowed |
| `min_length` | Minimum number of characters required |
| `live` | Enables real-time updates using Livewire |
| `resize` | Controls textarea resizing ('none', 'vertical', 'horizontal', 'both') |

**Example with additional options:**

```php
[
    'name' => 'Article Content',
    'slug' => 'content',
    'type' => 'Aura\\Base\\Fields\\Textarea',
    'validation' => 'required|string|min:100|max:2000',
    'placeholder' => 'Write your article content here...',
    'rows' => 8,
    'live' => true,
    'resize' => 'vertical',
    'default_value' => '',
],
```

#### Implementation Details

The Textarea field is implemented using the following components:

1. Field Wrapper
   - Provides consistent field layout
   - Handles label and error display
   - Manages field width and spacing

2. Textarea Component
   - Multi-line text input with enhanced features
   - Supports auto-resizing
   - Handles character counting
   - Integrates with Livewire for real-time updates

3. Livewire Integration
   ```php
   // Live mode (real-time updates)
   wire:model.live="form.fields.field_name"

   // Normal mode (update on change)
   wire:model="form.fields.field_name"
   ```

#### Filter Options

The Textarea field provides several filter options for querying:

- equals
- not equals
- contains
- does not contain
- starts with
- ends with
- is empty
- is not empty

<a name="number"></a>
### Number

**Class**: `Aura\Base\Fields\Number`

The Number field creates an input specifically designed for numeric values. It provides features for handling integers, decimals, currency amounts, and other numeric data types. The field includes built-in validation for numeric values and supports formatting options like prefixes and suffixes, making it perfect for prices, quantities, measurements, or any numeric input.

<div class="dark:hidden">
<img src="/images/Fields/Number.png" alt="Number Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/Number_dark.png" alt="Number Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Price',
    'slug' => 'price',
    'type' => 'Aura\\Base\\Fields\\Number',
    'validation' => 'required|numeric|min:0',
    'placeholder' => 'Enter the price',
    'prefix' => '$',
    'suffix' => 'USD',
],
```

#### Additional Options

Here's a table summarizing additional options for the Number field:

| Option | Description |
|--------|-------------|
| `default_value` | Sets a default numeric value |
| `placeholder` | Displays placeholder text when empty |
| `prefix` | Displays text or symbol before the input |
| `suffix` | Displays text or symbol after the input |
| `min` | Minimum allowed value |
| `max` | Maximum allowed value |
| `step` | Increment/decrement step value |
| `precision` | Number of decimal places to display |
| `live` | Enables real-time updates using Livewire |
| `thousands_separator` | Character used to separate thousands |
| `decimal_separator` | Character used for decimal point |

**Example with additional options:**

```php
[
    'name' => 'Amount',
    'slug' => 'amount',
    'type' => 'Aura\\Base\\Fields\\Number',
    'validation' => 'required|numeric|min:0|max:1000000',
    'placeholder' => 'Enter amount',
    'prefix' => '$',
    'suffix' => 'USD',
    'step' => '0.01',
    'precision' => 2,
    'live' => true,
    'thousands_separator' => ',',
    'decimal_separator' => '.',
],
```

#### Implementation Details

The Number field is implemented using the following components:

1. Field Wrapper
   - Provides consistent field layout
   - Handles label and error display
   - Manages field width and spacing

2. Number Input Component
   - Numeric input with validation
   - Supports prefixes and suffixes
   - Handles formatting and parsing
   - Integrates with Livewire for real-time updates

3. Livewire Integration
   ```php
   // Live mode (real-time updates)
   wire:model.live="form.fields.field_name"

   // Normal mode (update on change)
   wire:model="form.fields.field_name"
   ```

#### Filter Options

The Number field provides several filter options for querying:

- equals
- not equals
- greater than
- less than
- greater than or equal to
- less than or equal to
- between
- not between
- is empty
- is not empty

<a name="email"></a>
### Email

**Class**: `Aura\Base\Fields\Email`

The Email field creates an input specifically designed for email addresses. It provides built-in email validation, autocomplete functionality, and proper keyboard type on mobile devices. The field ensures that users enter properly formatted email addresses and can be configured to handle multiple email addresses when needed.

<div class="dark:hidden">
<img src="/images/Fields/Email.png" alt="Email Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/Email_dark.png" alt="Email Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Email Address',
    'slug' => 'email',
    'type' => 'Aura\\Base\\Fields\\Email',
    'validation' => 'required|email',
    'placeholder' => 'Enter your email address',
],
```

#### Additional Options

Here's a table summarizing additional options for the Email field:

| Option | Description |
|--------|-------------|
| `default_value` | Sets a default email address |
| `placeholder` | Displays placeholder text when empty |
| `autocomplete` | Controls browser autocomplete behavior ('email', 'off') |
| `multiple` | Allows multiple email addresses |
| `pattern` | Custom validation pattern for specific email formats |
| `live` | Enables real-time updates using Livewire |
| `prefix` | Displays text or icon before the input |
| `suffix` | Displays text or icon after the input |

**Example with additional options:**

```php
[
    'name' => 'Contact Emails',
    'slug' => 'contact_emails',
    'type' => 'Aura\\Base\\Fields\\Email',
    'validation' => 'required|email:rfc,dns|unique:users,email',
    'placeholder' => 'Enter email address',
    'autocomplete' => 'email',
    'multiple' => true,
    'live' => true,
],
```

#### Implementation Details

The Email field is implemented using the following components:

1. Field Wrapper
   - Provides consistent field layout
   - Handles label and error display
   - Manages field width and spacing

2. Email Input Component
   - Email-specific input with validation
   - Supports multiple email addresses
   - Handles proper keyboard type on mobile
   - Integrates with Livewire for real-time updates

3. Livewire Integration
   ```php
   // Live mode (real-time updates)
   wire:model.live="form.fields.field_name"

   // Normal mode (update on change)
   wire:model="form.fields.field_name"
   ```

#### Filter Options

The Email field provides several filter options for querying:

- equals
- not equals
- contains
- does not contain
- starts with
- ends with
- is empty
- is not empty
- domain equals
- domain not equals

<a name="password"></a>
### Password

**Class**: `Aura\Base\Fields\Password`

The Password field creates a secure input for password entry and confirmation. It includes features like password strength indicators, show/hide password toggle, and proper security measures for handling sensitive data. The field automatically handles password hashing and provides options for password validation rules.

<div class="dark:hidden">
<img src="/images/Fields/Password.png" alt="Password Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/Password_dark.png" alt="Password Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Password',
    'slug' => 'password',
    'type' => 'Aura\\Base\\Fields\\Password',
    'validation' => 'required|min:8|confirmed',
    'placeholder' => 'Enter your password',
],
```

#### Additional Options

Here's a table summarizing additional options for the Password field:

| Option | Description |
|--------|-------------|
| `show_password_toggle` | Enables the show/hide password button |
| `strength_meter` | Shows password strength indicator |
| `min_length` | Minimum password length requirement |
| `require_numbers` | Requires numeric characters |
| `require_special_chars` | Requires special characters |
| `require_uppercase` | Requires uppercase letters |
| `require_lowercase` | Requires lowercase letters |
| `autocomplete` | Controls browser autocomplete behavior |
| `confirmation` | Enables password confirmation field |
| `hash_driver` | Specifies the password hashing algorithm |

**Example with additional options:**

```php
[
    'name' => 'Password',
    'slug' => 'password',
    'type' => 'Aura\\Base\\Fields\\Password',
    'validation' => 'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
    'placeholder' => 'Enter a strong password',
    'show_password_toggle' => true,
    'strength_meter' => true,
    'min_length' => 8,
    'require_numbers' => true,
    'require_special_chars' => true,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'confirmation' => true,
    'autocomplete' => 'new-password',
],
```

#### Implementation Details

The Password field is implemented using the following components:

1. Field Wrapper
   - Provides consistent field layout
   - Handles label and error display
   - Manages field width and spacing

2. Password Input Component
   - Secure password input with masking
   - Show/hide password toggle
   - Password strength indicator
   - Confirmation field handling
   - Automatic password hashing

3. Security Features
   - Automatic password hashing using Laravel's Hash facade
   - HTTPS-only transmission
   - Protection against XSS and other vulnerabilities
   - Secure password validation rules

4. Livewire Integration
   ```php
   // Password field (without live updates for security)
   wire:model="form.fields.field_name"

   // Confirmation field
   wire:model="form.fields.field_name_confirmation"
   ```

#### Filter Options

The Password field provides limited filter options for security:

- is empty
- is not empty

<a name="date"></a>
### Date

**Class**: `Aura\Base\Fields\Date`

The Date field creates an input for selecting dates using a calendar picker interface. It provides a user-friendly date selection experience with features like date range restrictions, localization, and various display formats. The field automatically handles date formatting and validation, making it perfect for handling any date-related data.

<div class="dark:hidden">
<img src="/images/Fields/Date.png" alt="Date Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/Date_dark.png" alt="Date Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Publication Date',
    'slug' => 'published_at',
    'type' => 'Aura\\Base\\Fields\\Date',
    'validation' => 'required|date',
    'placeholder' => 'Select publication date',
],
```

#### Additional Options

Here's a table summarizing additional options for the Date field:

| Option | Description |
|--------|-------------|
| `default_value` | Sets a default date value |
| `placeholder` | Displays placeholder text when empty |
| `format` | PHP date format for storing the value |
| `display_format` | Format for displaying the date |
| `min_date` | Earliest selectable date |
| `max_date` | Latest selectable date |
| `disable_dates` | Array of disabled dates |
| `disable_days` | Array of disabled days of week |
| `week_starts_on` | First day of the week (0-6) |
| `enable_time` | Enable time selection |
| `live` | Enables real-time updates using Livewire |
| `locale` | Sets the calendar locale |

**Example with additional options:**

```php
[
    'name' => 'Event Date',
    'slug' => 'event_date',
    'type' => 'Aura\\Base\\Fields\\Date',
    'validation' => 'required|date|after:today',
    'placeholder' => 'Select event date',
    'format' => 'Y-m-d',
    'display_format' => 'F j, Y',
    'min_date' => 'today',
    'max_date' => '+1 year',
    'disable_days' => [0, 6], // Disable weekends
    'week_starts_on' => 1, // Start week on Monday
    'live' => true,
    'locale' => 'en',
],
```

#### Implementation Details

The Date field is implemented using the following components:

1. Field Wrapper
   - Provides consistent field layout
   - Handles label and error display
   - Manages field width and spacing

2. Date Picker Component
   - Calendar interface for date selection
   - Supports date range restrictions
   - Handles localization
   - Manages date formatting
   - Integrates with Livewire for real-time updates

3. Livewire Integration
   ```php
   // Live mode (real-time updates)
   wire:model.live="form.fields.field_name"

   // Normal mode (update on change)
   wire:model="form.fields.field_name"
   ```

#### Filter Options

The Date field provides several filter options for querying:

- equals
- not equals
- before
- after
- between
- not between
- is empty
- is not empty
- is today
- is past
- is future

<a name="datetime"></a>
### Datetime

**Class**: `Aura\Base\Fields\Datetime`

The Datetime field combines date and time selection into a single input. It provides a comprehensive interface for selecting both date and time values, with features like timezone support, range restrictions, and various formatting options. This field is perfect for scheduling events, setting deadlines, or recording timestamps that require both date and time precision.

<div class="dark:hidden">
<img src="/images/Fields/Datetime.png" alt="Datetime Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/Datetime_dark.png" alt="Datetime Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Event Start',
    'slug' => 'starts_at',
    'type' => 'Aura\\Base\\Fields\\Datetime',
    'validation' => 'required|date',
    'placeholder' => 'Select event start time',
],
```

#### Additional Options

Here's a table summarizing additional options for the Datetime field:

| Option | Description |
|--------|-------------|
| `default_value` | Sets a default datetime value |
| `placeholder` | Displays placeholder text when empty |
| `format` | PHP datetime format for storing the value |
| `display_format` | Format for displaying the datetime |
| `min_date` | Earliest selectable date |
| `max_date` | Latest selectable date |
| `min_time` | Earliest selectable time |
| `max_time` | Latest selectable time |
| `disable_dates` | Array of disabled dates |
| `disable_days` | Array of disabled days of week |
| `week_starts_on` | First day of the week (0-6) |
| `enable_seconds` | Enable seconds selection |
| `timezone` | Timezone for the datetime value |
| `live` | Enables real-time updates using Livewire |
| `locale` | Sets the calendar locale |

**Example with additional options:**

```php
[
    'name' => 'Meeting Time',
    'slug' => 'meeting_time',
    'type' => 'Aura\\Base\\Fields\\Datetime',
    'validation' => 'required|date|after:now',
    'placeholder' => 'Select meeting time',
    'format' => 'Y-m-d H:i:s',
    'display_format' => 'F j, Y g:i A',
    'min_date' => 'today',
    'max_date' => '+6 months',
    'min_time' => '09:00',
    'max_time' => '17:00',
    'disable_days' => [0, 6], // Disable weekends
    'week_starts_on' => 1, // Start week on Monday
    'enable_seconds' => false,
    'timezone' => 'UTC',
    'live' => true,
    'locale' => 'en',
],
```

#### Implementation Details

The Datetime field is implemented using the following components:

1. Field Wrapper
   - Provides consistent field layout
   - Handles label and error display
   - Manages field width and spacing

2. Datetime Picker Component
   - Combined calendar and time picker interface
   - Supports date and time range restrictions
   - Handles timezone conversions
   - Manages datetime formatting
   - Integrates with Livewire for real-time updates

3. Livewire Integration
   ```php
   // Live mode (real-time updates)
   wire:model.live="form.fields.field_name"

   // Normal mode (update on change)
   wire:model="form.fields.field_name"
   ```

#### Filter Options

The Datetime field provides several filter options for querying:

- equals
- not equals
- before
- after
- between
- not between
- is empty
- is not empty
- is today
- is past
- is future
- is within hours
- is within days

<a name="time"></a>
### Time

**Class**: `Aura\Base\Fields\Time`

The Time field creates an input specifically for time selection. It provides a user-friendly time picker interface that allows users to select hours, minutes, and optionally seconds. This field is ideal for scheduling, setting operating hours, or any time-specific data entry.

<div class="dark:hidden">
<img src="/images/Fields/Time.png" alt="Time Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/Time_dark.png" alt="Time Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Opening Time',
    'slug' => 'opens_at',
    'type' => 'Aura\\Base\\Fields\\Time',
    'validation' => 'required',
],
```

#### Additional Options

Here's a table summarizing additional options for the Time field:

| Option | Description |
|--------|-------------|
| `format` | PHP time format for storage (default: 'H:i:s') |
| `display_format` | Format for displaying the time (default: 'H:i') |
| `enable_input` | Allows manual time input (default: true) |
| `enable_seconds` | Include seconds in time selection (default: false) |
| `min_time` | Minimum selectable time |
| `max_time` | Maximum selectable time |
| `default_value` | Default time value |
| `placeholder` | Placeholder text when no time is selected |
| `time_increment` | Minutes between time options (default: 30) |
| `use_24hr` | Use 24-hour format instead of AM/PM (default: true) |

**Example with additional options:**

```php
[
    'name' => 'Opening Time',
    'slug' => 'opens_at',
    'type' => 'Aura\\Base\\Fields\\Time',
    'validation' => 'required',
    'format' => 'H:i:s',
    'display_format' => 'h:i A',
    'enable_input' => true,
    'enable_seconds' => false,
    'min_time' => '09:00',
    'max_time' => '17:00',
    'default_value' => '09:00',
    'placeholder' => 'Select opening time',
    'time_increment' => 15,
    'use_24hr' => false,
],
```

#### Filter Options

The Time field provides several filter options for querying:

- equals
- not equals
- before
- after
- between
- is empty
- is not empty

<a name="boolean"></a>
### Boolean

**Class**: `Aura\Base\Fields\Boolean`

The Boolean field creates a toggle switch for true/false values. It provides a simple and intuitive interface for binary choices, perfect for settings, flags, or any yes/no decisions. The field renders as a modern toggle switch with customizable labels and styling.

<div class="dark:hidden">
<img src="/images/Fields/Boolean.png" alt="Boolean Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/Boolean_dark.png" alt="Boolean Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Active',
    'slug' => 'is_active',
    'type' => 'Aura\\Base\\Fields\\Boolean',
    'validation' => 'boolean',
],
```

#### Additional Options

Here's a table summarizing additional options for the Boolean field:

| Option | Description |
|--------|-------------|
| `default_value` | Sets the default state (true/false) |
| `true_label` | Custom label for the "true" state |
| `false_label` | Custom label for the "false" state |
| `true_value` | Custom value for the "true" state (default: true) |
| `false_value` | Custom value for the "false" state (default: false) |
| `switch_size` | Size of the toggle switch ('sm', 'md', 'lg') |
| `colors` | Custom colors for the toggle states |

**Example with additional options:**

```php
[
    'name' => 'Newsletter Subscription',
    'slug' => 'newsletter_subscribed',
    'type' => 'Aura\\Base\\Fields\\Boolean',
    'validation' => 'boolean',
    'default_value' => true,
    'true_label' => 'Subscribed',
    'false_label' => 'Unsubscribed',
    'true_value' => 1,
    'false_value' => 0,
    'switch_size' => 'lg',
    'colors' => [
        'true' => 'green',
        'false' => 'gray',
    ],
],
```

#### Filter Options

The Boolean field provides several filter options for querying:

- is true
- is false
- is empty
- is not empty

<a name="select"></a>
### Select

**Class**: `Aura\Base\Fields\Select`

The Select field creates a dropdown menu with predefined options. It provides a clean interface for selecting one or multiple options from a list. This field is ideal for situations where users need to choose from a fixed set of options, such as categories, status types, or any predefined list of choices.

<div class="dark:hidden">
<img src="/images/Fields/Select.png" alt="Select Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/Select_dark.png" alt="Select Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Category',
    'slug' => 'category',
    'type' => 'Aura\\Base\\Fields\\Select',
    'options' => [
        'technology' => 'Technology',
        'lifestyle' => 'Lifestyle',
        'business' => 'Business',
    ],
],
```

#### Additional Options

Here's a table summarizing additional options for the Select field:

| Option | Description |
|--------|-------------|
| `options` | Array of key-value pairs for selectable options |
| `multiple` | Allow selection of multiple options (default: false) |
| `placeholder` | Text to display when no option is selected |
| `default_value` | Default selected option(s) |
| `searchable` | Enable search functionality for options |
| `clear_button` | Show a button to clear selection |
| `group_options` | Group options under categories |
| `max_items` | Maximum number of selectable items (when multiple is true) |

**Example with additional options:**

```php
[
    'name' => 'Categories',
    'slug' => 'categories',
    'type' => 'Aura\\Base\\Fields\\Select',
    'validation' => 'required|array|min:1',
    'options' => [
        'content' => [
            'blog' => 'Blog Posts',
            'news' => 'News Articles',
            'tutorials' => 'Tutorials',
        ],
        'media' => [
            'photos' => 'Photography',
            'videos' => 'Video Content',
            'podcasts' => 'Podcasts',
        ],
    ],
    'multiple' => true,
    'searchable' => true,
    'placeholder' => 'Select categories',
    'default_value' => ['blog'],
    'clear_button' => true,
    'group_options' => true,
    'max_items' => 3,
],
```

#### Filter Options

The Select field provides several filter options for querying:

- equals
- not equals
- contains
- does not contain
- is empty
- is not empty
- has any
- has all

<a name="checkbox"></a>
### Checkbox

**Class**: `Aura\Base\Fields\Checkbox`

The Checkbox field creates a group of checkboxes that allow users to select multiple options from a predefined list. Each option is presented as a separate checkbox, making it ideal for situations where users need to select multiple items from a visible list of choices, such as selecting multiple features, permissions, or preferences.

<div class="dark:hidden">
<img src="/images/Fields/Checkbox.png" alt="Checkbox Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/Checkbox_dark.png" alt="Checkbox Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Features',
    'slug' => 'features',
    'type' => 'Aura\\Base\\Fields\\Checkbox',
    'options' => [
        'wifi' => 'WiFi',
        'parking' => 'Parking',
        'breakfast' => 'Breakfast',
        'pool' => 'Swimming Pool',
    ],
],
```

#### Additional Options

Here's a table summarizing additional options for the Checkbox field:

| Option | Description |
|--------|-------------|
| `options` | Array of key-value pairs for checkbox options |
| `default_value` | Array of keys for pre-selected options |
| `layout` | Display layout ('vertical', 'horizontal', 'grid') |
| `columns` | Number of columns when using grid layout |
| `group_options` | Group checkboxes under categories |
| `select_all` | Show a "Select All" option |
| `min_selected` | Minimum number of options that must be selected |
| `max_selected` | Maximum number of options that can be selected |

**Example with additional options:**

```php
[
    'name' => 'Amenities',
    'slug' => 'amenities',
    'type' => 'Aura\\Base\\Fields\\Checkbox',
    'validation' => 'required|array|min:1',
    'options' => [
        'basic' => [
            'wifi' => 'WiFi',
            'parking' => 'Parking',
            'ac' => 'Air Conditioning',
        ],
        'premium' => [
            'pool' => 'Swimming Pool',
            'spa' => 'Spa Access',
            'gym' => 'Fitness Center',
        ],
    ],
    'default_value' => ['wifi', 'parking'],
    'layout' => 'grid',
    'columns' => 3,
    'group_options' => true,
    'select_all' => true,
    'min_selected' => 2,
    'max_selected' => 5,
],
```

#### Filter Options

The Checkbox field provides several filter options for querying:

- has all
- has any
- has none
- is empty
- is not empty

<a name="radio"></a>
### Radio

**Class**: `Aura\Base\Fields\Radio`

The Radio field creates a group of radio buttons that allow users to select a single option from a predefined list. Each option is presented as a separate radio button, making it ideal for situations where users need to make a single choice from clearly visible options, such as selecting a payment method, subscription plan, or any mutually exclusive options.

<div class="dark:hidden">
<img src="/images/Fields/Radio.png" alt="Radio Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/Radio_dark.png" alt="Radio Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Subscription Plan',
    'slug' => 'plan',
    'type' => 'Aura\\Base\\Fields\\Radio',
    'options' => [
        'basic' => 'Basic Plan',
        'pro' => 'Pro Plan',
        'enterprise' => 'Enterprise Plan',
    ],
],
```

#### Additional Options

Here's a table summarizing additional options for the Radio field:

| Option | Description |
|--------|-------------|
| `options` | Array of key-value pairs for radio options |
| `default_value` | Key of the pre-selected option |
| `layout` | Display layout ('vertical', 'horizontal', 'grid') |
| `columns` | Number of columns when using grid layout |
| `group_options` | Group radio buttons under categories |
| `description_position` | Position of option descriptions ('below', 'right') |
| `show_description` | Whether to show option descriptions |

**Example with additional options:**

```php
[
    'name' => 'Subscription Plan',
    'slug' => 'plan',
    'type' => 'Aura\\Base\\Fields\\Radio',
    'validation' => 'required',
    'options' => [
        'starter' => [
            'label' => 'Starter Plan',
            'description' => 'Perfect for individuals',
            'price' => '$9/month',
        ],
        'team' => [
            'label' => 'Team Plan',
            'description' => 'Great for small teams',
            'price' => '$29/month',
        ],
        'business' => [
            'label' => 'Business Plan',
            'description' => 'For large organizations',
            'price' => '$99/month',
        ],
    ],
    'default_value' => 'starter',
    'layout' => 'grid',
    'columns' => 3,
    'show_description' => true,
    'description_position' => 'below',
],
```

#### Filter Options

The Radio field provides several filter options for querying:

- equals
- not equals
- is empty
- is not empty

<a name="status"></a>
### Status

**Class**: `Aura\Base\Fields\Status`

The Status field creates a specialized select input with color-coded options. It's perfect for representing different states or statuses in your application, such as "Published/Draft", "Active/Inactive", or any workflow states. Each option can be associated with a specific color for visual distinction.

<div class="dark:hidden">
<img src="/images/Fields/Status.png" alt="Status Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/Status_dark.png" alt="Status Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Status',
    'slug' => 'status',
    'type' => 'Aura\\Base\\Fields\\Status',
    'options' => [
        'draft' => [
            'label' => 'Draft',
            'color' => 'gray',
        ],
        'published' => [
            'label' => 'Published',
            'color' => 'green',
        ],
        'archived' => [
            'label' => 'Archived',
            'color' => 'red',
        ],
    ],
],
```

#### Additional Options

Here's a table summarizing additional options for the Status field:

| Option | Description |
|--------|-------------|
| `options` | Array of status options with labels and colors |
| `default_value` | Default selected status |
| `placeholder` | Text to display when no status is selected |
| `searchable` | Enable search functionality for options |
| `clear_button` | Show a button to clear selection |
| `colors` | Predefined color options ('gray', 'red', 'yellow', 'green', 'blue', 'indigo', 'purple', 'pink') |
| `display_as_badge` | Show status as a colored badge |
| `size` | Size of the status badge ('sm', 'md', 'lg') |

**Example with additional options:**

```php
[
    'name' => 'Order Status',
    'slug' => 'order_status',
    'type' => 'Aura\\Base\\Fields\\Status',
    'validation' => 'required',
    'options' => [
        'pending' => [
            'label' => 'Pending',
            'color' => 'yellow',
            'description' => 'Order is awaiting processing',
        ],
        'processing' => [
            'label' => 'Processing',
            'color' => 'blue',
            'description' => 'Order is being processed',
        ],
        'completed' => [
            'label' => 'Completed',
            'color' => 'green',
            'description' => 'Order has been fulfilled',
        ],
        'cancelled' => [
            'label' => 'Cancelled',
            'color' => 'red',
            'description' => 'Order has been cancelled',
        ],
    ],
    'default_value' => 'pending',
    'display_as_badge' => true,
    'size' => 'md',
    'searchable' => true,
    'clear_button' => false,
],
```

#### Filter Options

The Status field provides several filter options for querying:

- equals
- not equals
- in
- not in
- is empty
- is not empty

<a name="advanced-select"></a>
### Advanced Select

**Class**: `Aura\Base\Fields\AdvancedSelect`

The Advanced Select field creates an enhanced dropdown with powerful features like search, dynamic loading, and the ability to create new options on the fly. It's perfect for handling relationships between resources or when dealing with large sets of options that require filtering. This field provides a superior user experience compared to the standard Select field when working with extensive data sets.

<div class="dark:hidden">
<img src="/images/Fields/AdvancedSelect.png" alt="Advanced Select Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/AdvancedSelect_dark.png" alt="Advanced Select Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Author',
    'slug' => 'author_id',
    'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
    'resource' => 'App\\Resources\\UserResource',
    'searchable' => true,
],
```

#### Additional Options

Here's a table summarizing additional options for the Advanced Select field:

| Option | Description |
|--------|-------------|
| `resource` | The related resource class for populating options |
| `multiple` | Allow selection of multiple options |
| `searchable` | Enable search functionality (default: true) |
| `create` | Allow creating new entries inline |
| `placeholder` | Text to display when no option is selected |
| `min_input_length` | Minimum characters required to trigger search |
| `max_items` | Maximum number of selectable items (when multiple is true) |
| `ajax` | Enable dynamic loading of options via AJAX |
| `preload` | Load all options immediately instead of using AJAX |
| `display_field` | Field from resource to use as display text |
| `search_fields` | Fields to search when filtering options |

**Example with additional options:**

```php
[
    'name' => 'Tags',
    'slug' => 'tags',
    'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
    'resource' => 'App\\Resources\\TagResource',
    'validation' => 'required|array|min:1',
    'multiple' => true,
    'searchable' => true,
    'create' => true,
    'placeholder' => 'Select or create tags',
    'min_input_length' => 2,
    'max_items' => 5,
    'ajax' => true,
    'preload' => false,
    'display_field' => 'name',
    'search_fields' => ['name', 'slug'],
    'create_rules' => [
        'name' => 'required|min:3|max:50|unique:tags',
    ],
],
```

#### Filter Options

The Advanced Select field provides several filter options for querying:

- equals
- not equals
- contains
- does not contain
- is empty
- is not empty
- has any
- has all
- has none

<a name="image"></a>
### Image

**Class**: `Aura\Base\Fields\Image`

The Image field creates an interface for uploading, selecting, and managing images. It provides a rich media management experience with features like drag-and-drop upload, image preview, and the ability to handle multiple images. This field is perfect for managing product photos, user avatars, blog post featured images, or any image-based content.

<div class="dark:hidden">
<img src="/images/Fields/Image.png" alt="Image Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/Image_dark.png" alt="Image Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Featured Image',
    'slug' => 'featured_image',
    'type' => 'Aura\\Base\\Fields\\Image',
    'validation' => 'required|image|max:2048',
],
```

#### Additional Options

Here's a table summarizing additional options for the Image field:

| Option | Description |
|--------|-------------|
| `use_media_manager` | Enable the media manager interface |
| `min_files` | Minimum number of required images |
| `max_files` | Maximum number of allowed images |
| `allowed_types` | Array of allowed image mime types |
| `max_size` | Maximum file size in kilobytes |
| `disk` | Storage disk to use for uploads |
| `path` | Upload path within the storage disk |
| `show_file_name` | Display the file name below the image |
| `show_file_size` | Display the file size below the image |
| `generate_thumbnails` | Automatically generate thumbnails |
| `thumbnail_sizes` | Array of thumbnail dimensions to generate |
| `crop` | Enable image cropping tool |
| `aspect_ratio` | Fixed aspect ratio for cropping |

**Example with additional options:**

```php
[
    'name' => 'Gallery Images',
    'slug' => 'gallery',
    'type' => 'Aura\\Base\\Fields\\Image',
    'validation' => 'required|array|min:2|max:5',
    'use_media_manager' => true,
    'min_files' => 2,
    'max_files' => 5,
    'allowed_types' => ['image/jpeg', 'image/png', 'image/webp'],
    'max_size' => 5120, // 5MB
    'disk' => 'public',
    'path' => 'uploads/gallery',
    'show_file_name' => true,
    'show_file_size' => true,
    'generate_thumbnails' => true,
    'thumbnail_sizes' => [
        'thumb' => [200, 200],
        'medium' => [800, 600],
    ],
    'crop' => true,
    'aspect_ratio' => 16/9,
],
```

#### Filter Options

The Image field provides several filter options for querying:

- has file
- does not have file
- is empty
- is not empty

<a name="file"></a>
### File

**Class**: `Aura\Base\Fields\File`

The File field creates an interface for uploading and managing files of various types. It provides features like drag-and-drop upload, file preview (when possible), and download functionality. This field is perfect for handling documents, PDFs, archives, or any other file type that needs to be attached to your resources.

<div class="dark:hidden">
<img src="/images/Fields/File.png" alt="File Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/File_dark.png" alt="File Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Document',
    'slug' => 'document',
    'type' => 'Aura\\Base\\Fields\\File',
    'validation' => 'required|file|max:10240',
],
```

#### Additional Options

Here's a table summarizing additional options for the File field:

| Option | Description |
|--------|-------------|
| `use_media_manager` | Enable the media manager interface |
| `min_files` | Minimum number of required files |
| `max_files` | Maximum number of allowed files |
| `allowed_types` | Array of allowed mime types |
| `max_size` | Maximum file size in kilobytes |
| `disk` | Storage disk to use for uploads |
| `path` | Upload path within the storage disk |
| `show_file_name` | Display the file name |
| `show_file_size` | Display the file size |
| `show_preview` | Show file preview when possible |
| `download_button` | Show download button for uploaded files |
| `multiple` | Allow multiple file uploads |

**Example with additional options:**

```php
[
    'name' => 'Documents',
    'slug' => 'documents',
    'type' => 'Aura\\Base\\Fields\\File',
    'validation' => 'required|array|min:1|max:3',
    'use_media_manager' => true,
    'min_files' => 1,
    'max_files' => 3,
    'allowed_types' => [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ],
    'max_size' => 10240, // 10MB
    'disk' => 'public',
    'path' => 'uploads/documents',
    'show_file_name' => true,
    'show_file_size' => true,
    'show_preview' => true,
    'download_button' => true,
    'multiple' => true,
],
```

#### Filter Options

The File field provides several filter options for querying:

- has file
- does not have file
- is empty
- is not empty
- file type is
- file type is not

<a name="belongsto"></a>
### BelongsTo

**Class**: `Aura\Base\Fields\BelongsTo`

The BelongsTo field creates a relationship input that represents a many-to-one association between resources. It provides an interface for selecting a single related record from another resource, perfect for establishing relationships like a post belonging to a category, a comment belonging to a user, or any other parent-child relationship in your data model.

<div class="dark:hidden">
<img src="/images/Fields/BelongsTo.png" alt="BelongsTo Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/BelongsTo_dark.png" alt="BelongsTo Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Category',
    'slug' => 'category_id',
    'type' => 'Aura\\Base\\Fields\\BelongsTo',
    'resource' => 'App\\Resources\\CategoryResource',
],
```

#### Additional Options

Here's a table summarizing additional options for the BelongsTo field:

| Option | Description |
|--------|-------------|
| `resource` | The related resource class |
| `display_field` | Field from related resource to use as label |
| `searchable` | Enable search functionality for selecting related record |
| `placeholder` | Text to display when no relation is selected |
| `nullable` | Allow the relationship to be nullable |
| `with` | Array of relationships to eager load |
| `scope` | Custom query scope to filter available options |
| `create_new` | Allow creating new related records inline |
| `show_preview` | Display preview of selected related record |

**Example with additional options:**

```php
[
    'name' => 'Author',
    'slug' => 'user_id',
    'type' => 'Aura\\Base\\Fields\\BelongsTo',
    'resource' => 'App\\Resources\\UserResource',
    'validation' => 'required|exists:users,id',
    'display_field' => 'name',
    'searchable' => true,
    'placeholder' => 'Select an author',
    'nullable' => false,
    'with' => ['profile', 'roles'],
    'scope' => 'active',
    'create_new' => true,
    'show_preview' => true,
    'preview_fields' => [
        'name',
        'email',
        'role',
    ],
],
```

#### Filter Options

The BelongsTo field provides several filter options for querying:

- equals
- not equals
- is empty
- is not empty
- is in
- is not in

<a name="hasmany"></a>
### HasMany

**Class**: `Aura\Base\Fields\HasMany`

The HasMany field creates an interface for managing one-to-many relationships between resources. It allows users to associate multiple related records with the current resource, perfect for relationships like a post having many comments, a user having many posts, or any parent-child relationship where the parent can have multiple children.

<div class="dark:hidden">
<img src="/images/Fields/HasMany.png" alt="HasMany Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/HasMany_dark.png" alt="HasMany Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Comments',
    'slug' => 'comments',
    'type' => 'Aura\\Base\\Fields\\HasMany',
    'resource' => 'App\\Resources\\CommentResource',
],
```

#### Additional Options

Here's a table summarizing additional options for the HasMany field:

| Option | Description |
|--------|-------------|
| `resource` | The related resource class |
| `display_field` | Field from related resource to use as label |
| `searchable` | Enable search functionality for selecting related records |
| `sortable` | Allow reordering of related records |
| `min_items` | Minimum number of related records required |
| `max_items` | Maximum number of related records allowed |
| `create_new` | Allow creating new related records inline |
| `show_preview` | Display preview of selected related records |
| `with` | Array of relationships to eager load |
| `scope` | Custom query scope to filter available options |
| `order_by` | Field and direction for ordering related records |

**Example with additional options:**

```php
[
    'name' => 'Products',
    'slug' => 'products',
    'type' => 'Aura\\Base\\Fields\\HasMany',
    'resource' => 'App\\Resources\\ProductResource',
    'validation' => 'required|array|min:1',
    'display_field' => 'name',
    'searchable' => true,
    'sortable' => true,
    'min_items' => 1,
    'max_items' => 10,
    'create_new' => true,
    'show_preview' => true,
    'with' => ['category', 'tags'],
    'scope' => 'active',
    'order_by' => [
        'field' => 'sort_order',
        'direction' => 'asc',
    ],
    'preview_fields' => [
        'name',
        'price',
        'stock',
    ],
],
```

#### Filter Options

The HasMany field provides several filter options for querying:

- has any
- has all
- has none
- has exactly
- has count greater than
- has count less than
- is empty
- is not empty

<a name="group"></a>
### Group

**Class**: `Aura\Base\Fields\Group`

The Group field creates a container for organizing related fields together. It helps structure complex forms by grouping related fields under a common section, making forms more organized and easier to understand. Groups can be collapsible and can contain any other field types, including nested groups.

<div class="dark:hidden">
<img src="/images/Fields/Group.png" alt="Group Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/Group_dark.png" alt="Group Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Contact Information',
    'slug' => 'contact_info',
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
            'type' => 'Aura\\Base\\Fields\\Text',
        ],
    ],
],
```

#### Additional Options

Here's a table summarizing additional options for the Group field:

| Option | Description |
|--------|-------------|
| `fields` | Array of field configurations within the group |
| `collapsible` | Allow the group to be collapsed/expanded |
| `collapsed` | Default collapsed state (when collapsible is true) |
| `border` | Show border around the group |
| `padding` | Internal padding for the group content |
| `description` | Description text shown below the group title |
| `icon` | Icon to display next to the group title |
| `conditional_logic` | Rules for showing/hiding the entire group |

**Example with additional options:**

```php
[
    'name' => 'Social Media Profiles',
    'slug' => 'social_media',
    'type' => 'Aura\\Base\\Fields\\Group',
    'collapsible' => true,
    'collapsed' => false,
    'border' => true,
    'padding' => 'p-4',
    'description' => 'Enter your social media profile URLs',
    'icon' => 'social',
    'fields' => [
        [
            'name' => 'Twitter URL',
            'slug' => 'twitter_url',
            'type' => 'Aura\\Base\\Fields\\Text',
            'validation' => 'nullable|url',
        ],
        [
            'name' => 'Facebook URL',
            'slug' => 'facebook_url',
            'type' => 'Aura\\Base\\Fields\\Text',
            'validation' => 'nullable|url',
        ],
        [
            'name' => 'LinkedIn URL',
            'slug' => 'linkedin_url',
            'type' => 'Aura\\Base\\Fields\\Text',
            'validation' => 'nullable|url',
        ],
    ],
    'conditional_logic' => [
        'show_social' => true,
    ],
],
```

#### Nesting and Organization

Groups can be nested to create complex form structures:

- Groups within groups
- Groups within tabs
- Groups within panels
- Groups within repeaters

---

<a name="customizing-fields"></a>
## Customizing Fields

<a name="defining-custom-fields"></a>
### Defining Custom Fields

You can create custom fields by extending the base `Field` class and implementing the required methods.

**Example**:

```php
namespace App\Fields;

use Aura\Base\Fields\Field;

class CustomField extends Field
{
    public $edit = 'custom::fields.custom';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            // Custom field options
        ]);
    }
}
```

<a name="field-options-and-attributes"></a>
### Field Options and Attributes

When defining fields, you can specify various options and attributes to control their behavior.

**Common Attributes**:

- **name**: The display name of the field.
- **slug**: The unique identifier.
- **type**: The field class.
- **validation**: Laravel validation rules.
- **instructions**: Help text for users.
- **conditional_logic**: Conditions for displaying the field.
- **on_forms**: Display on forms.
- **on_index**: Display on index page.
- **on_view**: Display on view page.
- **style**: Styling options (e.g., width).


---

<a name="references"></a>
## References

- [Aura CMS Resources](resources.md)
- [Laravel Eloquent Relationships](https://laravel.com/docs/eloquent-relationships)
- [Laravel Validation](https://laravel.com/docs/validation)
- [Tailwind CSS](https://tailwindcss.com/docs)
- [Livewire](https://laravel-livewire.com/docs)
- [Alpine.js](https://alpinejs.dev/)

---

By mastering the various field types and their configurations in Aura CMS, developers can create intuitive and powerful forms that enhance user experience and data management within their applications.

<a name="repeater"></a>
### Repeater

**Class**: `Aura\Base\Fields\Repeater`

The Repeater field creates a dynamic interface for managing multiple sets of fields. It allows users to add, remove, and reorder groups of fields, perfect for handling variable-length content like FAQ sections, product variations, or any scenario where you need to collect multiple instances of the same data structure.

<div class="dark:hidden">
<img src="/images/Fields/Repeater.png" alt="Repeater Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/Repeater_dark.png" alt="Repeater Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'FAQ Items',
    'slug' => 'faq_items',
    'type' => 'Aura\\Base\\Fields\\Repeater',
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
],
```

#### Additional Options

Here's a table summarizing additional options for the Repeater field:

| Option | Description |
|--------|-------------|
| `fields` | Array of field configurations for each repeater item |
| `min_items` | Minimum number of items required |
| `max_items` | Maximum number of items allowed |
| `sortable` | Allow reordering of items |
| `collapsed` | Default collapsed state of items |
| `add_button_text` | Custom text for the add button |
| `remove_button_text` | Custom text for remove buttons |
| `item_label` | Label format for each repeater item |
| `layout` | Layout style ('stack', 'table', 'grid') |
| `default_values` | Default values for new items |

**Example with additional options:**

```php
[
    'name' => 'Product Variations',
    'slug' => 'variations',
    'type' => 'Aura\\Base\\Fields\\Repeater',
    'validation' => 'required|array|min:1',
    'fields' => [
        [
            'name' => 'Size',
            'slug' => 'size',
            'type' => 'Aura\\Base\\Fields\\Select',
            'options' => ['S', 'M', 'L', 'XL'],
        ],
        [
            'name' => 'Color',
            'slug' => 'color',
            'type' => 'Aura\\Base\\Fields\\Color',
        ],
        [
            'name' => 'Price',
            'slug' => 'price',
            'type' => 'Aura\\Base\\Fields\\Number',
            'prefix' => '$',
        ],
        [
            'name' => 'Stock',
            'slug' => 'stock',
            'type' => 'Aura\\Base\\Fields\\Number',
        ],
    ],
    'min_items' => 1,
    'max_items' => 10,
    'sortable' => true,
    'collapsed' => false,
    'add_button_text' => 'Add Variation',
    'remove_button_text' => 'Remove Variation',
    'item_label' => 'Variation #:index - :size :color',
    'layout' => 'table',
    'default_values' => [
        'stock' => 0,
        'price' => 9.99,
    ],
],
```

#### Nested Fields

The Repeater field can contain any other field type, including:

- Basic input fields (Text, Number, etc.)
- Select and choice fields
- Media fields
- Groups
- Even nested repeaters


<a name="tab"></a>
### Tab

**Class**: `Aura\Base\Fields\Tab`

The Tab field creates a tabbed interface for organizing fields into separate sections. It helps reduce form complexity by dividing content into logical groups, making large forms more manageable and easier to navigate. Tabs can contain any combination of fields and are perfect for organizing related content while maintaining a clean user interface.

<div class="dark:hidden">
<img src="/images/Fields/Tab.png" alt="Tab Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/Tab_dark.png" alt="Tab Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Content',
    'slug' => 'content_tab',
    'type' => 'Aura\\Base\\Fields\\Tab',
    'fields' => [
        [
            'name' => 'Title',
            'slug' => 'title',
            'type' => 'Aura\\Base\\Fields\\Text',
        ],
        [
            'name' => 'Content',
            'slug' => 'content',
            'type' => 'Aura\\Base\\Fields\\Wysiwyg',
        ],
    ],
],
```

#### Additional Options

Here's a table summarizing additional options for the Tab field:

| Option     | Description                                  |
| ---------- | -------------------------------------------- |
| `fields`   | Array of field configurations within the tab |
| `icon`     | Icon to display in the tab header            |
| `badge`    | Badge text or count to show on the tab       |
| `active`   | Whether this tab should be active by default |
| `disabled` | Whether this tab should be disabled          |
| `visible`  | Condition for tab visibility                 |
| `remember` | Remember the last active tab state           |
| `lazy`     | Load tab content only when activated         |

**Example with additional options:**

```php
[
    [
        'name' => 'Basic Info',
        'slug' => 'basic_info_tab',
        'type' => 'Aura\\Base\\Fields\\Tab',
        'icon' => 'information',
        'active' => true,
        'fields' => [
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
            [
                'name' => 'Description',
                'slug' => 'description',
                'type' => 'Aura\\Base\\Fields\\Textarea',
            ],
        ],
    ],
    [
        'name' => 'Media',
        'slug' => 'media_tab',
        'type' => 'Aura\\Base\\Fields\\Tab',
        'icon' => 'photo',
        'badge' => '2',
        'lazy' => true,
        'fields' => [
            [
                'name' => 'Featured Image',
                'slug' => 'featured_image',
                'type' => 'Aura\\Base\\Fields\\Image',
            ],
            [
                'name' => 'Gallery',
                'slug' => 'gallery',
                'type' => 'Aura\\Base\\Fields\\Image',
                'multiple' => true,
            ],
        ],
    ],
    [
        'name' => 'Advanced',
        'slug' => 'advanced_tab',
        'type' => 'Aura\\Base\\Fields\\Tab',
        'icon' => 'cog',
        'visible' => 'is_admin',
        'fields' => [
            [
                'name' => 'Custom CSS',
                'slug' => 'custom_css',
                'type' => 'Aura\\Base\\Fields\\Code',
            ],
        ],
    ],
],
```

#### Tab Navigation

Tabs can be navigated in several ways:
- Clicking on tab headers
- Using keyboard shortcuts (Left/Right arrows)
- Programmatically through JavaScript
- Through URL hash parameters

```

<a name="panel"></a>
### Panel

**Class**: `Aura\Base\Fields\Panel`

The Panel field creates a collapsible container for organizing fields. Similar to groups, panels help structure complex forms, but with the added ability to expand and collapse sections. This is particularly useful for forms with many fields where you want to reduce visual clutter while maintaining easy access to all fields.

<div class="dark:hidden">
<img src="/images/Fields/Panel.png" alt="Panel Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/Panel_dark.png" alt="Panel Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'SEO Settings',
    'slug' => 'seo_panel',
    'type' => 'Aura\\Base\\Fields\\Panel',
    'fields' => [
        [
            'name' => 'Meta Title',
            'slug' => 'meta_title',
            'type' => 'Aura\\Base\\Fields\\Text',
        ],
        [
            'name' => 'Meta Description',
            'slug' => 'meta_description',
            'type' => 'Aura\\Base\\Fields\\Textarea',
        ],
    ],
],
```

#### Additional Options

Here's a table summarizing additional options for the Panel field:

| Option | Description |
|--------|-------------|
| `fields` | Array of field configurations within the panel |
| `collapsed` | Default collapsed state (true/false) |
| `icon` | Icon to display in the panel header |
| `description` | Description text shown below the panel title |
| `border` | Show border around the panel |
| `padding` | Internal padding for the panel content |
| `header_class` | Custom CSS classes for the panel header |
| `body_class` | Custom CSS classes for the panel body |
| `remember_state` | Remember collapse state between page loads |

**Example with additional options:**

```php
[
    'name' => 'Advanced Settings',
    'slug' => 'advanced_settings',
    'type' => 'Aura\\Base\\Fields\\Panel',
    'collapsed' => true,
    'icon' => 'cog',
    'description' => 'Configure advanced settings for this resource',
    'border' => true,
    'padding' => 'p-4',
    'remember_state' => true,
    'fields' => [
        [
            'name' => 'Cache Duration',
            'slug' => 'cache_duration',
            'type' => 'Aura\\Base\\Fields\\Number',
            'suffix' => 'minutes',
        ],
        [
            'name' => 'Custom Headers',
            'slug' => 'custom_headers',
            'type' => 'Aura\\Base\\Fields\\Repeater',
            'fields' => [
                [
                    'name' => 'Header Name',
                    'slug' => 'name',
                    'type' => 'Aura\\Base\\Fields\\Text',
                ],
                [
                    'name' => 'Header Value',
                    'slug' => 'value',
                    'type' => 'Aura\\Base\\Fields\\Text',
                ],
            ],
        ],
    ],
],
```

#### Panel Features

Panels provide several useful features for form organization:

- Collapsible sections to reduce visual clutter
- Optional icons and descriptions for better context
- Ability to remember collapse state
- Can contain any combination of fields
- Nestable within other panels or groups

```

<a name="wysiwyg"></a>
### Wysiwyg

**Class**: `Aura\Base\Fields\Wysiwyg`

The Wysiwyg (What You See Is What You Get) field creates a rich text editor interface that allows users to format text with various styling options. It provides a familiar word processor-like experience, perfect for creating formatted content like blog posts, articles, or any text that requires styling and formatting.

<div class="dark:hidden">
<img src="/images/Fields/Wysiwyg.png" alt="Wysiwyg Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/Wysiwyg_dark.png" alt="Wysiwyg Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Content',
    'slug' => 'content',
    'type' => 'Aura\\Base\\Fields\\Wysiwyg',
    'validation' => 'required',
],
```

#### Additional Options

Here's a table summarizing additional options for the Wysiwyg field:

| Option | Description |
|--------|-------------|
| `toolbar` | Customize which formatting tools are available |
| `height` | Set the editor's initial height |
| `min_height` | Minimum height of the editor |
| `max_height` | Maximum height of the editor |
| `plugins` | Enable/disable specific editor plugins |
| `upload_url` | URL for handling image uploads |
| `media_library` | Enable media library integration |
| `content_css` | Custom CSS for the editor content |
| `custom_formats` | Define custom formatting options |
| `paste_filter` | Rules for filtering pasted content |

**Example with additional options:**

```php
[
    'name' => 'Article Content',
    'slug' => 'content',
    'type' => 'Aura\\Base\\Fields\\Wysiwyg',
    'validation' => 'required|min:100',
    'toolbar' => [
        'bold', 'italic', 'underline', 'strikethrough',
        'bullist', 'numlist',
        'link', 'image',
        'h2', 'h3', 'h4',
        'code', 'blockquote',
    ],
    'height' => 400,
    'min_height' => 200,
    'max_height' => 800,
    'plugins' => [
        'link', 'lists', 'image', 'code',
        'table', 'paste', 'media', 'fullscreen',
    ],
    'upload_url' => '/admin/media/upload',
    'media_library' => true,
    'content_css' => '/css/editor-styles.css',
    'custom_formats' => [
        [
            'title' => 'Warning Box',
            'block' => 'div',
            'classes' => 'warning-box',
            'wrapper' => true,
        ],
    ],
    'paste_filter' => [
        'strip_tags' => ['style', 'script'],
        'keep_styles' => false,
    ],
],
```

#### Filter Options

The Wysiwyg field provides several filter options for querying:

- contains
- does not contain
- is empty
- is not empty
- word count greater than
- word count less than

<a name="code"></a>
### Code

**Class**: `Aura\Base\Fields\Code`

The Code field creates a specialized editor for writing and editing code with syntax highlighting. It provides features like line numbers, multiple language support, and code formatting. This field is perfect for storing code snippets, custom scripts, CSS styles, or any content that benefits from code-specific editing features.

<div class="dark:hidden">
<img src="/images/Fields/Code.png" alt="Code Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/Code_dark.png" alt="Code Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Custom CSS',
    'slug' => 'custom_css',
    'type' => 'Aura\\Base\\Fields\\Code',
    'language' => 'css',
],
```

#### Additional Options

Here's a table summarizing additional options for the Code field:

| Option | Description |
|--------|-------------|
| `language` | Programming language for syntax highlighting |
| `theme` | Editor theme ('light', 'dark', etc.) |
| `line_numbers` | Show line numbers (true/false) |
| `tab_size` | Number of spaces for tab indentation |
| `height` | Height of the editor |
| `min_height` | Minimum editor height |
| `max_height` | Maximum editor height |
| `wrap` | Enable line wrapping |
| `readonly` | Make editor read-only |
| `auto_close_brackets` | Automatically close brackets/quotes |
| `lint` | Enable code linting |

**Example with additional options:**

```php
[
    'name' => 'JavaScript Code',
    'slug' => 'custom_js',
    'type' => 'Aura\\Base\\Fields\\Code',
    'validation' => 'required',
    'language' => 'javascript',
    'theme' => 'monokai',
    'line_numbers' => true,
    'tab_size' => 2,
    'height' => '400px',
    'min_height' => '200px',
    'max_height' => '800px',
    'wrap' => false,
    'readonly' => false,
    'auto_close_brackets' => true,
    'lint' => true,
],
```

#### Supported Languages

The Code field supports syntax highlighting for many programming languages, including:

- HTML/XML
- CSS/SCSS/Less
- JavaScript/TypeScript
- PHP
- Python
- Ruby
- Java
- SQL
- Markdown
- JSON
- YAML
- And many more...

#### Filter Options

The Code field provides several filter options for querying:

- contains
- does not contain
- is empty
- is not empty

<a name="color"></a>
### Color

**Class**: `Aura\Base\Fields\Color`

The Color field creates an interface for selecting and managing colors. It provides both a visual color picker and manual input options, supporting various color formats. This field is perfect for customizing theme colors, styling elements, or any scenario where color selection is needed.

<div class="dark:hidden">
<img src="/images/Fields/Color.png" alt="Color Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/Color_dark.png" alt="Color Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Background Color',
    'slug' => 'background_color',
    'type' => 'Aura\\Base\\Fields\\Color',
    'format' => 'hex',
],
```

#### Additional Options

Here's a table summarizing additional options for the Color field:

| Option | Description |
|--------|-------------|
| `format` | Color format ('hex', 'rgb', 'rgba', 'hsl') |
| `native` | Use native browser color picker |
| `alpha` | Allow transparency selection |
| `default_value` | Default color value |
| `presets` | Array of predefined color options |
| `show_input` | Show manual color input field |
| `show_labels` | Show labels for color values |
| `show_buttons` | Show preset color buttons |
| `popup` | Show color picker in popup |

**Example with additional options:**

```php
[
    'name' => 'Theme Colors',
    'slug' => 'theme_colors',
    'type' => 'Aura\\Base\\Fields\\Color',
    'validation' => 'required',
    'format' => 'rgba',
    'native' => false,
    'alpha' => true,
    'default_value' => '#4A90E2',
    'presets' => [
        '#2196F3' => 'Primary',
        '#4CAF50' => 'Success',
        '#FFC107' => 'Warning',
        '#F44336' => 'Danger',
    ],
    'show_input' => true,
    'show_labels' => true,
    'show_buttons' => true,
    'popup' => true,
],
```

#### Filter Options

The Color field provides several filter options for querying:

- equals
- not equals
- is empty
- is not empty

<a name="embed"></a>
### Embed

**Class**: `Aura\Base\Fields\Embed`

The Embed field creates an interface for embedding external content like videos, social media posts, or other web content. It provides preview capabilities and handles the complexities of embedding content from various platforms. This field is perfect for integrating content from YouTube, Vimeo, Twitter, Instagram, and other platforms that support oEmbed.

<div class="dark:hidden">
<img src="/images/Fields/Embed.png" alt="Embed Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/Embed_dark.png" alt="Embed Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Video',
    'slug' => 'video',
    'type' => 'Aura\\Base\\Fields\\Embed',
    'validation' => 'required|url',
],
```

#### Additional Options

Here's a table summarizing additional options for the Embed field:

| Option | Description |
|--------|-------------|
| `providers` | Array of allowed embed providers |
| `width` | Default width of the embedded content |
| `height` | Default height of the embedded content |
| `responsive` | Make embedded content responsive |
| `show_preview` | Show preview of embedded content |
| `cache_duration` | Duration to cache embed responses |
| `parameters` | Additional parameters for embed URLs |
| `allow_fullscreen` | Allow fullscreen mode for videos |
| `lazy_load` | Enable lazy loading of embeds |

**Example with additional options:**

```php
[
    'name' => 'Media Embed',
    'slug' => 'media_embed',
    'type' => 'Aura\\Base\\Fields\\Embed',
    'validation' => 'required|url',
    'providers' => [
        'youtube',
        'vimeo',
        'twitter',
        'instagram',
    ],
    'width' => 800,
    'height' => 450,
    'responsive' => true,
    'show_preview' => true,
    'cache_duration' => 3600,
    'parameters' => [
        'youtube' => [
            'autoplay' => 0,
            'controls' => 1,
            'rel' => 0,
        ],
    ],
    'allow_fullscreen' => true,
    'lazy_load' => true,
],
```

#### Supported Providers

The Embed field supports various content providers, including:

- YouTube
- Vimeo
- Twitter
- Instagram
- Facebook
- SoundCloud
- Spotify
- And many others that support oEmbed

#### Filter Options

The Embed field provides several filter options for querying:

- contains
- does not contain
- provider is
- provider is not
- is empty
- is not empty

<a name="heading"></a>
### Heading

**Class**: `Aura\Base\Fields\Heading`

The Heading field creates a text heading element for organizing and structuring forms. Unlike input fields, it's used purely for visual organization and doesn't store any data. This field is perfect for creating sections, dividing long forms into logical parts, or providing context for groups of fields.

<div class="dark:hidden">
<img src="/images/Fields/Heading.png" alt="Heading Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/Heading_dark.png" alt="Heading Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'name' => 'Personal Information',
    'type' => 'Aura\\Base\\Fields\\Heading',
],
```

#### Additional Options

Here's a table summarizing additional options for the Heading field:

| Option | Description |
|--------|-------------|
| `level` | Heading level (h1-h6) |
| `text_align` | Text alignment ('left', 'center', 'right') |
| `icon` | Icon to display next to heading |
| `description` | Subtext to display below heading |
| `color` | Text color class |
| `size` | Text size class |
| `padding` | Padding around the heading |
| `border_bottom` | Show border below heading |
| `margin_top` | Margin above heading |
| `margin_bottom` | Margin below heading |

**Example with additional options:**

```php
[
    'name' => 'Contact Details',
    'type' => 'Aura\\Base\\Fields\\Heading',
    'level' => 2,
    'text_align' => 'center',
    'icon' => 'user',
    'description' => 'Please provide your contact information below',
    'color' => 'text-primary-600',
    'size' => 'text-xl',
    'padding' => 'py-4',
    'border_bottom' => true,
    'margin_top' => 'mt-8',
    'margin_bottom' => 'mb-6',
],
```

#### Usage Examples

Headings can be used in various ways to improve form organization:

- Section dividers
- Form introductions
- Group labels
- Visual hierarchy
- Navigation landmarks

<a name="horizontal-line"></a>
### Horizontal Line

**Class**: `Aura\Base\Fields\HorizontalLine`

The Horizontal Line field creates a visual separator between form sections. Like the Heading field, it's used purely for visual organization and doesn't store any data. This field is perfect for creating clear visual breaks between different sections of a form, improving readability and organization.

<div class="dark:hidden">
<img src="/images/Fields/HorizontalLine.png" alt="Horizontal Line Field" style="max-width: 600px;">
</div>

<div class="hidden dark:block">
<img src="/images/Fields/HorizontalLine_dark.png" alt="Horizontal Line Field (Dark Mode)" style="max-width: 600px;">
</div>

**Example**:

```php
[
    'type' => 'Aura\\Base\\Fields\\HorizontalLine',
],
```

#### Additional Options

Here's a table summarizing additional options for the Horizontal Line field:

| Option | Description |
|--------|-------------|
| `style` | Line style ('solid', 'dashed', 'dotted') |
| `color` | Line color class |
| `thickness` | Line thickness in pixels |
| `margin_top` | Margin above the line |
| `margin_bottom` | Margin below the line |
| `width` | Line width ('full', 'auto', or specific value) |
| `opacity` | Line opacity (0-100) |

**Example with additional options:**

```php
[
    'type' => 'Aura\\Base\\Fields\\HorizontalLine',
    'style' => 'dashed',
    'color' => 'border-gray-300',
    'thickness' => 2,
    'margin_top' => 'mt-8',
    'margin_bottom' => 'mb-8',
    'width' => 'w-full',
    'opacity' => 50,
],
```

#### Usage Examples

Horizontal lines can be used effectively to:

- Separate form sections
- Create visual hierarchy
- Group related fields
- Indicate section transitions
- Enhance form readability
