# Resource Editor in Aura CMS

The Resource Editor in Aura CMS is a powerful tool that allows developers to create, modify, and manage resources directly from the CMS interface. It provides an intuitive UI for editing resource fields, configuring properties, and defining relationships without the need to manually alter code files. This streamlines the development process and enhances productivity by enabling rapid prototyping and customization.

---

## Table of Contents

- [Resource Editor in Aura CMS](#resource-editor-in-aura-cms)
  - [Table of Contents](#table-of-contents)
  - [Introduction to the Resource Editor](#introduction-to-the-resource-editor)
  - [Accessing the Resource Editor](#accessing-the-resource-editor)
  - [Features and Capabilities](#features-and-capabilities)
    - [Field Management](#field-management)
    - [Conditional Logic](#conditional-logic)
    - [Templates and Reusable Fields](#templates-and-reusable-fields)
    - [Resource Properties](#resource-properties)
    - [Actions and Utilities](#actions-and-utilities)
  - [Using the Resource Editor](#using-the-resource-editor)
    - [Creating a New Field](#creating-a-new-field)
    - [Editing Existing Fields](#editing-existing-fields)
    - [Reordering Fields](#reordering-fields)
    - [Deleting Fields](#deleting-fields)
    - [Adding Conditional Logic](#adding-conditional-logic)
    - [Applying Templates](#applying-templates)
  - [Best Practices](#best-practices)
  - [Limitations and Considerations](#limitations-and-considerations)

---

<a name="introduction-to-the-resource-editor"></a>
## Introduction to the Resource Editor

The Resource Editor is a Livewire component in Aura CMS that provides a graphical interface for defining and editing resources. It abstracts the underlying code structure, allowing developers to focus on the resource's configuration rather than its implementation details.

**Figure 1: Resource Editor Interface**

![Figure 1: Resource Editor Interface](placeholder-image.png)

---

<a name="accessing-the-resource-editor"></a>
## Accessing the Resource Editor

To access the Resource Editor:

1. Navigate to the Aura CMS dashboard.
2. In the sidebar, locate the resource you wish to edit.
3. Click on the "Edit Resource" option next to the resource.

**Note**: The Resource Editor is only available for resources defined in the `App` namespace. Vendor resources are not editable through the Resource Editor.

---

<a name="features-and-capabilities"></a>
## Features and Capabilities

<a name="field-management"></a>
### Field Management

- **Add Fields**: Create new fields of various types, including custom fields.
- **Edit Fields**: Modify existing fields' properties, such as name, slug, type, and validation rules.
- **Reorder Fields**: Drag and drop fields to rearrange their order within the resource.
- **Duplicate Fields**: Quickly create copies of existing fields for reuse.
- **Delete Fields**: Remove fields that are no longer needed.

<a name="conditional-logic"></a>
### Conditional Logic

- **Define Conditions**: Set up rules to show or hide fields based on the values of other fields.
- **Add Condition Groups**: Group multiple conditions together for complex logic.
- **Flexible Operators**: Use operators like `==`, `!=`, `>`, `<`, `>=`, `<=` for comparisons.

<a name="templates-and-reusable-fields"></a>
### Templates and Reusable Fields

- **Apply Templates**: Insert predefined sets of fields from templates.
- **Custom Templates**: Create and manage your own templates for repeated use across resources.

<a name="resource-properties"></a>
### Resource Properties

- **Type**: Define the resource type (e.g., `post`, `page`).
- **Slug**: Set the unique identifier for the resource.
- **Icon**: Choose an icon to represent the resource in the CMS.
- **Group**: Organize resources into groups or categories.
- **Dropdown**: Configure dropdown menus for resource navigation.
- **Sort**: Set the sorting order for resources.

<a name="actions-and-utilities"></a>
### Actions and Utilities

- **Generate Migration**: Automatically generate database migration files based on the resource's fields.
- **Delete Resource**: Permanently remove a resource from the CMS.
- **Validation**: Ensure fields meet specified criteria before saving.

---

<a name="using-the-resource-editor"></a>
## Using the Resource Editor

<a name="creating-a-new-field"></a>
### Creating a New Field

1. Click the "Add Field" button.
2. Choose the field type from the list of available options.
3. Fill in the field properties:
   - **Name**: The display name of the field.
   - **Slug**: The unique identifier used in code.
   - **Type**: The field class (e.g., `Text`, `Number`).
   - **Validation**: Laravel validation rules.
   - **Instructions**: Help text for users.

**Figure 2: Adding a New Field**

![Figure 2: Adding a New Field](placeholder-image.png)

<a name="editing-existing-fields"></a>
### Editing Existing Fields

1. Click on the field you wish to edit.
2. Modify the desired properties.
3. Save the changes.

**Note**: Changes are immediately reflected in the resource configuration.

<a name="reordering-fields"></a>
### Reordering Fields

- Drag and drop fields to rearrange their order within the resource.
- The new order is automatically saved upon dropping the field into its new position.

**Figure 3: Reordering Fields**

![Figure 3: Reordering Fields](placeholder-image.png)

<a name="deleting-fields"></a>
### Deleting Fields

1. Click the delete icon next to the field.
2. Confirm the deletion when prompted.

**Warning**: Deleting a field will remove all associated data. This action is irreversible.

<a name="adding-conditional-logic"></a>
### Adding Conditional Logic

1. Open the field's settings.
2. Navigate to the "Conditional Logic" tab.
3. Add a new condition or condition group.
4. Define the field, operator, and value for the condition.

**Example**: Show the "Company Name" field only when "Is Employed" is `true`.

**Figure 4: Adding Conditional Logic**

![Figure 4: Adding Conditional Logic](placeholder-image.png)

<a name="applying-templates"></a>
### Applying Templates

1. Click the "Add Template" button.
2. Select a template from the list.
3. The template fields are inserted into the resource.

**Note**: Templates help in reusing common field sets across different resources.

---

<a name="best-practices"></a>
## Best Practices

- **Unique Slugs**: Ensure each field slug is unique to avoid conflicts.
- **Validation Rules**: Utilize Laravel's validation to maintain data integrity.
- **Reserved Words**: Avoid using reserved words (e.g., `id`, `type`) as slugs.
- **Organize with Tabs and Panels**: Use structural fields to enhance form usability.
- **Backup Before Deletion**: Always backup data before deleting fields or resources.

---

<a name="limitations-and-considerations"></a>
## Limitations and Considerations

- **Vendor Resources**: Resources provided by third-party packages (vendor resources) cannot be edited through the Resource Editor.
- **Fields with Closures**: If a resource's fields use closures, the Resource Editor cannot process them. Editing must be done manually in code.

---
