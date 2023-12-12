export const description =
  'Description here, '

<div className="firstlevel">
  # Aura Fields
</div>

// Introduction here

## Fieldtypes

// Introduction here

The following fields are available on all Resources:

### Field categories
We have three categories of fields:
- [Input Fields](#input-fields)
- [Media Fields](#media-fields)
- [Choice Fields](#choice-fields)
- [JS Fields](#js-fields)
- [Layout Fields](#layout-fields)
- [Structure Fields](#structure-fields)
- [Relational Fields](#relational-fields)

### Input Fields
- [Email](#email-field)
- [Hidden](#hidden-field)
- [Number](#number-field)
- [Password](#password-field)
- [Phone](#phone-field)
- [Slug](#slug-field)
- [Text](#text-field)
- [Textarea](#textarea-field)

### Media Fields
- [File](#file-field)
- [Image](#image-field)

### Choice Fields
- [Checkbox](#checkbox-field)
- [Radio](#radio-field)
- [Select](#select-field)

### JS Fields
- [Code](#code-field)
- [Color](#color-field)
- [Date](#date-field)
- [Datetime](#datetime-field)
- [HTML](#html-field)
- [JSON](#json-field)
- [Markdown](#markdown-field)
- [Time](#time-field)
- [WYSIWYG](#wysiwyg-field)

### Relational Fields
- [Belongs To](#belongs-to-field)
- [Belongs To Many](#belongs-to-many-field)
- [Has Many](#has-many-field)
- [Has One](#has-one-field)

### Layout Fields
- [Horizontal Line](#horizontal-line-field)
- [Heading](#heading-field)
- [Notice](#notice-field)
- [Subheading](#subheading-field)

### Structure Fields
- [Accordion](#accordion-field)
- [Group](#group-field)
- [Panel](#panel-field)
- [Tab](#tab-field)


---

## Base Field Properties

The following attributes are available on all of the fields:

<Row>
  <Col>
    <Properties>
      <Property name="name" type="string">
        Name of the field.
      </Property>
      <Property name="slug" type="string">
        Unique identifier of the field.
      </Property>
      <Property name="type" type="string">
        Type of the Field. See [Fieldtypes](#fieldtypes) for more information.
      </Property>
      <Property name="placeholder" type="string">
        Placeholder of the field. Default is `null`.
      </Property>
      <Property name="instructions" type="string">
        Instructions for the field. Default is `null`.
      </Property>
      <Property name="suffix" type="string">
        Suffix of the field. Default is `null`.
      </Property>
      <Property name="prefix" type="string">
        Prefix of the field. Default is `null`.
      </Property>
      <Property name="validation" type="string">
        Validation rules for the field. See [Validation](#validation) for more information.
      </Property>
      <Property name="conditional_logic" type="array">
        See [Conditional Logic](#conditional-logic) for more information.
      </Property>
      <Property name="style" type="array">
        See [Style](#style) for more information.
      </Property>
      <Property name="style.width" type="integer">
        Width of the field in perfect. Default is `100`.
      </Property>
      <Property name="style.hide_label" type="integer">
        Hide the label of the field. Default is `false`.
      </Property>
      <Property name="style.class" type="string">
        Custom CSS class for the field wrapper. Default is `null`.
      </Property>

      <Property name="on_index" type="boolean">
        Specifies if the field should be shown on the index page. Default is `true`.
      </Property>
      <Property name="on_forms" type="boolean">
        Specifies if the field should be shown on the create and edit views. Default is `true`.
      </Property>
      <Property name="on_view" type="boolean">
        Specifies if the field should be shown on the view page. Default is `true`.
      </Property>
      <Property name="on_create" type="boolean">
        Specifies if the field should be shown on the create page. Default is `true`.
      </Property>
      <Property name="on_search" type="boolean">
        Specifies if the field should be shown on the search page. Default is `true`.
      </Property>
    </Properties>
  </Col>
  <Col sticky>

    <CodeGroup title="Define Base Field Properties" tag="Field" label="\Aura\Resources\Post.php">

    <div title="Field">
      ```php
      [
        'name' => 'Text',
        'slug' => 'text',
        'type' => 'App\\Aura\\Fields\\Text',
        'placeholder' => 'Text',
        'instructions' => 'This is a text field.',
        'suffix' => '',
        'prefix' => '',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
          'class' => '',
        ],
      ],
      ```
    </div>

    <div title="File">
      ```php
      namespace App\Aura\Resources;

      class Option extends Post
      {
          public static function getFields()
          {
              return [
                  [
                      'name' => 'Text',
                      'slug' => 'text',
                      'type' => 'App\\Aura\\Fields\\Text',
                      'placeholder' => 'Text',
                      'instructions' => 'This is a text field.',
                      'suffix' => '',
                      'prefix' => '',

                      'on_index' => true,
                      'on_forms' => true,
                      'on_view' => true,

                      'validation' => '',
                      'conditional_logic' => [],
                      'style' => [
                          'width' => 100,
                          'hide_label' => false,
                      ],
                  ],
              ];
          }
      }
      ```
    </div>


    </CodeGroup>

  </Col>
</Row>

---

## Validation

The validation rules are defined in the `validation` attribute of the field. These are the same rules as [Laravel Validation Rules](https://laravel.com/docs/master/validation#available-validation-rules). The following validation rules are most commonly used:

<Row>
  <Col>

    <Properties>
      <Property name="required" type="boolean">
        Specifies if the field is required. Default is `false`.
      </Property>
      <Property name="email" type="boolean">
        Specifies if the field is an email. Default is `false`.
      </Property>
      <Property name="url" type="boolean">
        Specifies if the field is an url. Default is `false`.
      </Property>
      <Property name="min" type="integer">
        Specifies the minimum length of the field. Default is `null`.
      </Property>
      <Property name="max" type="integer">
        Specifies the maximum length of the field. Default is `null`.
      </Property>
      <Property name="min_value" type="integer">
        Specifies the minimum value of the field. Default is `null`.
      </Property>
      <Property name="max_value" type="integer">
        Specifies the maximum value of the field. Default is `null`.
      </Property>
      <Property name="regex" type="string">
        Specifies the regex pattern of the field. Default is `null`.
      </Property>
    </Properties>

    For more validation rules, please refer to the [Laravel Validation Rules](https://laravel.com/docs/master/validation#available-validation-rules) documentation.

    You can even define your own validation rules. For more information, please refer to the [Custom Validation Rules](https://laravel.com/docs/master/validation#custom-validation-rules) documentation.

  </Col>
  <Col sticky>

    <CodeGroup title="Define Field Validation" tag="Field" label="\Aura\Fields\Email.php">

    <div title="Required">
      ```php
      [
        'name' => 'Title',
        'slug' => 'title',
        'type' => 'App\\Aura\\Fields\\Text',
        'placeholder' => 'Title',
        'validation' => 'required',
      ],
      ```
    </div>

    <div title="Email">
      ```php
      [
        'name' => 'Email',
        'slug' => 'email',
        'type' => 'App\\Aura\\Fields\\Email',
        'placeholder' => 'Email',
        'validation' => 'sometimes|required|email',
      ],
      ```
    </div>

    <div title="Date after today">
      ```php
      [
        'name' => 'Start Date',
        'slug' => 'start_date',
        'type' => 'App\\Aura\\Fields\\Date',
        'placeholder' => 'Start Date',
        'validation' => 'required|date|after:today',
      ],
      ```

    </div>

    <div title="RegEx">
      ```php
      [
        'name' => 'Phone',
        'slug' => 'phone',
        'type' => 'App\\Aura\\Fields\\Phone',
        'placeholder' => 'Phone',
        // Swiss phone number RegEx
        'validation' => 'regex:/^(0041|041|\+41|\+\+41|41)?(0|\(0\))?([1-9]\d{1})(\d{3})(\d{2})(\d{2})$/',
      ],
      ```
    </div>

    </CodeGroup>

  </Col>
</Row>

---

## Conditional Logic

The conditional logic is defined in the `conditional_logic` attribute of the field and is defined as an array. Each item in the array is a condition and has the following keys:

<Row>
  <Col>
    <Properties>
      <Property name="field" type="string">
        Specifies the field slug to compare with.
      </Property>
      <Property name="operator" type="string">
        Specifies the operator to compare with. Available operators are `==`, `!=`, `>`, `>=`, `<`, `<=`.
      </Property>
      <Property name="value" type="string">
        Specifies the value to compare with.
      </Property>
    </Properties>
  </Col>

  <Col sticky>
    <CodeGroup title="Define Conditional Logic" tag="Field" label="\Aura\Fields\Email.php">
    <div title="Value based">
      ```php
      [
        'name' => 'Number',
        'slug' => 'number',
        'type' => 'App\\Aura\\Fields\\Number',
      ],
      [
        'name' => 'Title',
        'slug' => 'title',
        'type' => 'App\\Aura\\Fields\\Text',
        'placeholder' => 'Title',
        'conditional_logic' => [
          [
            'field' => 'number',
            'operator' => '>=',
            'value' => '1000',
          ],
        ],
      ],
      ```
    </div>
    <div title="Role based">
      ```php
      [
        'name' => 'Email',
        'slug' => 'email',
        'type' => 'App\\Aura\\Fields\\Email',
        'placeholder' => 'Email',
        'conditional_logic' => [
          [
            'field' => 'role',
            'operator' => '==',
            'value' => 'admin',
          ],
        ],
      ],
      ```
    </div>

    </CodeGroup>
  </Col>
</Row>


---

## Email Field {{ tag: 'Email', label: 'Aura Base' }}

<Row>
  <Col>

    The email field allows you to add a email input to your resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Email Field Properties

    No custom properties are available for the email field.

  </Col>
  <Col sticky>

    <CodeGroup title="Define Email Field" tag="Field" label="\Aura\Fields\Email.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Email',
        'slug' => 'email',
        'type' => 'App\\Aura\\Fields\\Email',
        'placeholder' => 'Email',
        'validation' => 'email',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Email',
        'slug' => 'Email',
        'type' => 'App\\Aura\\Fields\\Email',
        'placeholder' => 'Email',
        'instructions' => 'This is an email field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => 'email',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>

    </CodeGroup>

  </Col>
</Row>

---

## Hidden Field {{ tag: 'Hidden', label: 'Aura Base' }}

<Row>
  <Col>

    The Hidden field allows you to add a hidden input to your resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Hidden Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Hidden Field" tag="Field" label="\Aura\Fields\Hidden.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Hidden',
        'slug' => 'hidden',
        'type' => 'App\\Aura\\Fields\\Hidden',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Hidden',
        'slug' => 'hidden',
        'type' => 'App\\Aura\\Fields\\Hidden',

        'on_index' => false,
        'on_forms' => false,
        'on_view' => false,

        'validation' => '',
        'conditional_logic' => [],
      ],
      ```
    </div>

    </CodeGroup>

  </Col>
</Row>

---

##  Number Field {{ tag: 'Number', label: 'Aura Base' }}

<Row>
  <Col>

    The number field allows you to add a number input to your resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Number Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Number Field" tag="Field" label="\Aura\Fields\Number.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Number',
        'slug' => 'number',
        'type' => 'App\\Aura\\Fields\\Number',
        'placeholder' => 'Number',
        'validation' => 'nullable|numeric',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Number',
        'slug' => 'number',
        'type' => 'App\\Aura\\Fields\\Number',
        'placeholder' => 'Number',
        'instructions' => 'This is a number field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => 'nullable|numeric',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>

    </CodeGroup>

  </Col>
</Row>

---

##  Password Field {{ tag: 'Password', label: 'Aura Base' }}

<Row>
  <Col>

    The password field allows you to add a password input to your resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Password Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Password Field" tag="Field" label="\Aura\Fields\Password.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Password',
        'slug' => 'password',
        'type' => 'App\\Aura\\Fields\\Password',
        'placeholder' => 'Password',
        'validation' => 'min:8',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Password',
        'slug' => 'password',
        'type' => 'App\\Aura\\Fields\\Password',
        'placeholder' => 'Password',
        'instructions' => 'This is a password field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => 'min:8',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>

    </CodeGroup>

  </Col>
</Row>

---

##  Phone Field {{ tag: 'Phone', label: 'Aura Base' }}

<Row>
  <Col>

    The phone field allows you to add a phone input to your resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Phone Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Phone Field" tag="Field" label="\Aura\Fields\Phone.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Phone',
        'slug' => 'phone',
        'type' => 'App\\Aura\\Fields\\Phone',
        'placeholder' => 'Phone',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Phone',
        'slug' => 'phone',
        'type' => 'App\\Aura\\Fields\\Phone',
        'placeholder' => 'Phone',
        'instructions' => 'This is a phone field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>

    </CodeGroup>

  </Col>
</Row>

---

##  Slug Field {{ tag: 'Slug', label: 'Aura Base' }}

<Row>
  <Col>

    The slug field allows you to add a slug input to your resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Slug Field Properties

    <Properties>

      <Property name="based_on" type="string">
        The field to base the slug on.
      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Slug Field" tag="Field" label="\Aura\Fields\Slug.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Slug',
        'slug' => 'slug',
        'type' => 'App\\Aura\\Fields\\Slug',
        'placeholder' => 'Slug',
        'based_on' => 'title',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Slug',
        'slug' => 'slug',
        'type' => 'App\\Aura\\Fields\\Slug',
        'placeholder' => 'Slug',
        'instructions' => 'This is a slug field.',
        'based_on' => 'title',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>

    </CodeGroup>

  </Col>
</Row>

---

## Text Field {{ tag: 'Text', label: 'Aura Base' }}

<Row>
  <Col>

    The text field allows you to add a text input to your resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Text Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Text Field" tag="Field" label="\Aura\Fields\Text.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Text',
        'slug' => 'text',
        'type' => 'App\\Aura\\Fields\\Text',
        'placeholder' => 'Text',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Text',
        'slug' => 'text',
        'type' => 'App\\Aura\\Fields\\Text',
        'placeholder' => 'Text',
        'instructions' => 'This is a text field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>

    </CodeGroup>

  </Col>
</Row>

---

## Textarea Field {{ tag: 'Textarea', label: 'Aura Base' }}

<Row>
  <Col>

    The textarea field allows you to add a textarea to your resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Textarea Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Textarea Field" tag="Field" label="\Aura\Fields\Textarea.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Textarea',
        'slug' => 'textarea',
        'type' => 'App\\Aura\\Fields\\Textarea',
        'placeholder' => 'Message',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Textarea',
        'slug' => 'textarea',
        'type' => 'App\\Aura\\Fields\\Textarea',
        'placeholder' => 'Message',
        'instructions' => 'This is a Textarea field.',

        'on_index' => false,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>

    </CodeGroup>

  </Col>
</Row>

---

<div className="secondlevel">
  # Media Fields
</div>

## File Field {{ tag: 'File', label: 'Aura Base' }}

<Row>
  <Col>

    The file field allows you to add a file to your resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### File Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define File Field" tag="Field" label="\Aura\Fields\File.php">

    <div title="Basic">
      ```php
      [
        'name' => 'File',
        'slug' => 'file',
        'type' => 'App\\Aura\\Fields\\File',
        'placeholder' => 'File',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'File',
        'slug' => 'file',
        'type' => 'App\\Aura\\Fields\\File',
        'placeholder' => 'File',
        'instructions' => 'This is a file field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>

    </CodeGroup>
  </Col>
</Row>

---

## Image Field {{ tag: 'Image', label: 'Aura Base' }}

<Row>
  <Col>

    The image field allows you to add an image to your resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Image Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Image Field" tag="Field" label="\Aura\Fields\Image.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Image',
        'slug' => 'image',
        'type' => 'App\\Aura\\Fields\\Image',
        'placeholder' => 'Image',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Image',
        'slug' => 'image',
        'type' => 'App\\Aura\\Fields\\Image',
        'placeholder' => 'Image',
        'instructions' => 'This is an image field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>

    </CodeGroup>
  </Col>
</Row>

---

<div className="secondlevel">
  # Choice Fields
</div>

## Checkbox Field {{ tag: 'Checkbox', label: 'Aura Base' }}

<Row>
  <Col>

    The checkbox field allows you to add a checkbox input to your resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Checkbox Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Checkbox Field" tag="Field" label="\Aura\Fields\Checkbox.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Checkbox',
        'slug' => 'checkbox',
        'type' => 'App\\Aura\\Fields\\Checkbox',
        'options' => [
          'option-1' => 'Option 1',
          'option-2' => 'Option 2',
          'option-3' => 'Option 3',
        ],
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Checkbox',
        'slug' => 'checkbox',
        'type' => 'App\\Aura\\Fields\\Checkbox',
        'options' => [
          'option-1' => 'Option 1',
          'option-2' => 'Option 2',
          'option-3' => 'Option 3',
        ],
        'instructions' => 'This is a checkbox field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>

    </CodeGroup>

  </Col>
</Row>

---

## Select Field {{ tag: 'Select', label: 'Aura Base' }}

<Row>
  <Col>

    The select field allows you to add a select input to your resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Select Field Properties

    <Properties>

      <Property name="options" type="array">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Select Field" tag="Field" label="\Aura\Fields\Select.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Select',
        'slug' => 'select',
        'type' => 'App\\Aura\\Fields\\Select',
        'options' => [
          'option-1' => 'Option 1',
          'option-2' => 'Option 2',
          'option-3' => 'Option 3',
        ],
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Select',
        'slug' => 'select',
        'type' => 'App\\Aura\\Fields\\Select',
        'options' => [
          'option-1' => 'Option 1',
          'option-2' => 'Option 2',
          'option-3' => 'Option 3',
        ],
        'instructions' => 'This is a select field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>

    </CodeGroup>

  </Col>
</Row>

---

## Radio Field {{ tag: 'Radio', label: 'Aura Base' }}

<Row>
  <Col>

    The radio field allows you to add a radio input to your resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Radio Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Radio Field" tag="Field" label="\Aura\Fields\Radio.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Radio',
        'slug' => 'radio',
        'type' => 'App\\Aura\\Fields\\Radio',
        'options' => [
          'option-1' => 'Option 1',
          'option-2' => 'Option 2',
          'option-3' => 'Option 3',
        ],
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Radio',
        'slug' => 'radio',
        'type' => 'App\\Aura\\Fields\\Radio',
        'options' => [
          'option-1' => 'Option 1',
          'option-2' => 'Option 2',
          'option-3' => 'Option 3',
        ],
        'instructions' => 'This is a radio field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>

    </CodeGroup>

  </Col>
</Row>

---

<div className="secondlevel">
  # JS Fields
</div>

## Code Field {{ tag: 'Code', label: 'Aura Base' }}

<Row>
  <Col>

    The code field allows you to add a code input to your resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Code Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Code Field" tag="Field" label="\Aura\Fields\Code.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Code',
        'slug' => 'code',
        'type' => 'App\\Aura\\Fields\\Code',
        'placeholder' => 'Code',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Code',
        'slug' => 'code',
        'type' => 'App\\Aura\\Fields\\Code',
        'placeholder' => 'Code',
        'instructions' => 'This is a code field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>

    </CodeGroup>

  </Col>
</Row>

---

## Color Field {{ tag: 'Color', label: 'Aura Base' }}

<Row>
  <Col>

    The color field allows you to add a color input to your resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Color Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Color Field" tag="Field" label="\Aura\Fields\Color.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Color',
        'slug' => 'color',
        'type' => 'App\\Aura\\Fields\\Color',
        'placeholder' => 'Color',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Color',
        'slug' => 'color',
        'type' => 'App\\Aura\\Fields\\Color',
        'placeholder' => 'Color',
        'instructions' => 'This is a color field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>

    </CodeGroup>

  </Col>
</Row>

---

## Date Field {{ tag: 'Date', label: 'Aura Base' }}

<Row>
  <Col>

    The date field allows you to add a date input to your resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Date Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Date Field" tag="Field" label="\Aura\Fields\Date.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Date',
        'slug' => 'date',
        'type' => 'App\\Aura\\Fields\\Date',
        'placeholder' => 'Date',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Date',
        'slug' => 'date',
        'type' => 'App\\Aura\\Fields\\Date',
        'placeholder' => 'Date',
        'instructions' => 'This is a date field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>

    </CodeGroup>

  </Col>
</Row>

---

## DateTime Field {{ tag: 'DateTime', label: 'Aura Base' }}

<Row>
  <Col>

    The datetime field allows you to add a datetime input to your resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### DateTime Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define DateTime Field" tag="Field" label="\Aura\Fields\DateTime.php">

    <div title="Basic">
      ```php
      [
        'name' => 'DateTime',
        'slug' => 'datetime',
        'type' => 'App\\Aura\\Fields\\DateTime',
        'placeholder' => 'DateTime',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'DateTime',
        'slug' => 'datetime',
        'type' => 'App\\Aura\\Fields\\DateTime',
        'placeholder' => 'DateTime',
        'instructions' => 'This is a datetime field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>

    </CodeGroup>

  </Col>
</Row>

---

## HTML Field {{ tag: 'HTML', label: 'Aura Base' }}

<Row>
  <Col>

    The HTML field allows you to add a HTML input to your resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### HTML Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define HTML Field" tag="Field" label="\Aura\Fields\HTML.php">

    <div title="Basic">
      ```php
      [
        'name' => 'HTML',
        'slug' => 'html',
        'type' => 'App\\Aura\\Fields\\HTML',
        'placeholder' => 'HTML',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'HTML',
        'slug' => 'html',
        'type' => 'App\\Aura\\Fields\\HTML',
        'placeholder' => 'HTML',
        'instructions' => 'This is a HTML field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>

    </CodeGroup>

  </Col>
</Row>

---

## JSON Field {{ tag: 'JSON', label: 'Aura Base' }}

<Row>
  <Col>

    The JSON field allows you to add a JSON input to your resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### JSON Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define JSON Field" tag="Field" label="\Aura\Fields\JSON.php">

    <div title="Basic">
      ```php
      [
        'name' => 'JSON',
        'slug' => 'json',
        'type' => 'App\\Aura\\Fields\\JSON',
        'placeholder' => 'JSON',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'JSON',
        'slug' => 'json',
        'type' => 'App\\Aura\\Fields\\JSON',
        'placeholder' => 'JSON',
        'instructions' => 'This is a JSON field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>

    </CodeGroup>

  </Col>
</Row>

---

## Markdown Field {{ tag: 'Markdown', label: 'Aura Base' }}

<Row>
  <Col>

    The markdown field allows you to add a markdown input to your resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Markdown Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Markdown Field" tag="Field" label="\Aura\Fields\Markdown.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Markdown',
        'slug' => 'markdown',
        'type' => 'App\\Aura\\Fields\\Markdown',
        'placeholder' => 'Markdown',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Markdown',
        'slug' => 'markdown',
        'type' => 'App\\Aura\\Fields\\Markdown',
        'placeholder' => 'Markdown',
        'instructions' => 'This is a markdown field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>

    </CodeGroup>

  </Col>
</Row>

---

## WYSIWYG Field {{ tag: 'WYSIWYG', label: 'Aura Base' }}

<Row>
  <Col>

    The WYSIWYG field allows you to add a WYSIWYG input to your resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### WYSIWYG Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define WYSIWYG Field" tag="Field" label="\Aura\Fields\WYSIWYG.php">

    <div title="Basic">
      ```php
      [
        'name' => 'WYSIWYG',
        'slug' => 'wysiwyg',
        'type' => 'App\\Aura\\Fields\\WYSIWYG',
        'placeholder' => 'WYSIWYG',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'WYSIWYG',
        'slug' => 'wysiwyg',
        'type' => 'App\\Aura\\Fields\\WYSIWYG',
        'placeholder' => 'WYSIWYG',
        'instructions' => 'This is a WYSIWYG field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>

    </CodeGroup>

  </Col>
</Row>

---

<div className="secondlevel">
  # Relational Fields
</div>

Relational fields allow you to relate your resource to other resources.

## Belongs To Field {{ tag: 'Belongs To', label: 'Aura Base' }}

<Row>
  <Col>

    The belongs to field allows you to relate your resource to another resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Belongs To Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

      <Property name="resource" type="string">

      </Property>

      <Property name="display" type="string">

      </Property>

      <Property name="searchable" type="boolean">

      </Property>

      <Property name="sortable" type="boolean">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Belongs To Field" tag="Field" label="\Aura\Fields\BelongsTo.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Belongs To',
        'slug' => 'belongs_to',
        'type' => 'App\\Aura\\Fields\\BelongsTo',
        'placeholder' => 'Belongs To',
        'resource' => 'App\\Aura\\Resources\\UserResource',
        'display' => 'name',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Belongs To',
        'slug' => 'belongs_to',
        'type' => 'App\\Aura\\Fields\\BelongsTo',
        'placeholder' => 'Belongs To',
        'instructions' => 'This is a belongs to field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],

        'resource' => 'App\\Aura\\Resources\\UserResource',
        'display' => 'name',
        'searchable' => true,
        'sortable' => true,
      ],
      ```
    </div>

    </CodeGroup>

  </Col>
</Row>

---

## Belongs To Many Field {{ tag: 'Belongs To Many', label: 'Aura Base' }}

<Row>
  <Col>

    The belongs to many field allows you to relate your resource to many other resources.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Belongs To Many Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

      <Property name="resource" type="string">

      </Property>

      <Property name="display" type="string">

      </Property>

      <Property name="searchable" type="boolean">

      </Property>

      <Property name="sortable" type="boolean">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Belongs To Many Field" tag="Field" label="\Aura\Fields\BelongsToMany.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Belongs To Many',
        'slug' => 'belongs_to_many',
        'type' => 'App\\Aura\\Fields\\BelongsToMany',
        'placeholder' => 'Belongs To Many',
        'resource' => 'App\\Aura\\Resources\\UserResource',
        'display' => 'name',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Belongs To Many',
        'slug' => 'belongs_to_many',
        'type' => 'App\\Aura\\Fields\\BelongsToMany',
        'placeholder' => 'Belongs To Many',
        'instructions' => 'This is a belongs to many field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],

        'resource' => 'App\\Aura\\Resources\\UserResource',
        'display' => 'name',
        'searchable' => true,
        'sortable' => true,
      ],
      ```
    </div>

    </CodeGroup>

  </Col>
</Row>

---

## Has Many Field {{ tag: 'Has Many', label: 'Aura Base' }}

<Row>
  <Col>

    The has many field allows you to relate many of your resources to another resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Has Many Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

      <Property name="resource" type="string">

      </Property>

      <Property name="display" type="string">

      </Property>

      <Property name="searchable" type="boolean">

      </Property>

      <Property name="sortable" type="boolean">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Has Many Field" tag="Field" label="\Aura\Fields\HasMany.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Has Many',
        'slug' => 'has_many',
        'type' => 'App\\Aura\\Fields\\HasMany',
        'placeholder' => 'Has Many',
        'resource' => 'App\\Aura\\Resources\\UserResource',
        'display' => 'name',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Has Many',
        'slug' => 'has_many',
        'type' => 'App\\Aura\\Fields\\HasMany',
        'placeholder' => 'Has Many',
        'instructions' => 'This is a has many field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],

        'resource' => 'App\\Aura\\Resources\\UserResource',
        'display' => 'name',
        'searchable' => true,
        'sortable' => true,
      ],
      ```
    </div>
    </CodeGroup>
  </Col>
</Row>

---

## Has One Field {{ tag: 'Has One', label: 'Aura Base' }}

<Row>
  <Col>

    The has one field allows you to relate one of your resources to another resource.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Has One Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

      <Property name="resource" type="string">

      </Property>

      <Property name="display" type="string">

      </Property>

      <Property name="searchable" type="boolean">

      </Property>

      <Property name="sortable" type="boolean">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Has One Field" tag="Field" label="\Aura\Fields\HasOne.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Has One',
        'slug' => 'has_one',
        'type' => 'App\\Aura\\Fields\\HasOne',
        'placeholder' => 'Has One',
        'resource' => 'App\\Aura\\Resources\\UserResource',
        'display' => 'name',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Has One',
        'slug' => 'has_one',
        'type' => 'App\\Aura\\Fields\\HasOne',
        'placeholder' => 'Has One',
        'instructions' => 'This is a has one field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],

        'resource' => 'App\\Aura\\Resources\\UserResource',
        'display' => 'name',
        'searchable' => true,
        'sortable' => true,
      ],
      ```
    </div>
    </CodeGroup>
  </Col>
</Row>

---

<div className="secondlevel">
  # Layout Fields
</div>

## Horizontal Line Field {{ tag: 'HR', label: 'Aura Base' }}

<Row>
  <Col>

    The horizontal line field allows you to add a horizontal line to your form.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Horizontal Line Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Horizontal Line Field" tag="Field" label="\Aura\Fields\HorizontalLine.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Horizontal Line',
        'slug' => 'horizontal_line',
        'type' => 'App\\Aura\\Fields\\HorizontalLine',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Horizontal Line',
        'slug' => 'horizontal_line',
        'type' => 'App\\Aura\\Fields\\HorizontalLine',
        'instructions' => 'This is a horizontal line field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>
    </CodeGroup>
  </Col>
</Row>

---

## Heading Field {{ tag: 'Heading', label: 'Aura Base' }}

<Row>
  <Col>

    The heading field allows you to add a heading to your form.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Heading Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

      <Property name="heading" type="string">

      </Property>

      <Property name="subheading" type="string">

      </Property>

      <Property name="size" type="string">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Heading Field" tag="Field" label="\Aura\Fields\Heading.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Heading',
        'slug' => 'heading',
        'type' => 'App\\Aura\\Fields\\Heading',
        'heading' => 'Heading',
        'subheading' => 'Subheading',
        'size' => '2xl',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Heading',
        'slug' => 'heading',
        'type' => 'App\\Aura\\Fields\\Heading',
        'heading' => 'Heading',
        'subheading' => 'Subheading',
        'size' => '2xl',
        'instructions' => 'This is a heading field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>
    </CodeGroup>
  </Col>
</Row>

---

## Notice Field {{ tag: 'Notice', label: 'Aura Base' }}

<Row>
  <Col>

    The notice field allows you to add a notice to your form.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Notice Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

      <Property name="notice" type="string">

      </Property>

      <Property name="type" type="string">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Notice Field" tag="Field" label="\Aura\Fields\Notice.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Notice',
        'slug' => 'notice',
        'type' => 'App\\Aura\\Fields\\Notice',
        'notice' => 'This is a notice.',
        'type' => 'info',
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Notice',
        'slug' => 'notice',
        'type' => 'App\\Aura\\Fields\\Notice',
        'notice' => 'This is a notice.',
        'type' => 'info',
        'instructions' => 'This is a notice field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>
    </CodeGroup>
  </Col>
</Row>

---

<div className="secondlevel">
  # Structure Fields
</div>

## Group Field {{ tag: 'Group', label: 'Aura Base' }}

<Row>
  <Col>

    The group field allows you to group fields together.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Group Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

      <Property name="fields" type="array">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Group Field" tag="Field" label="\Aura\Fields\Group.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Group',
        'slug' => 'group',
        'type' => 'App\\Aura\\Fields\\Group',
        'fields' => [
          [
            'name' => 'Text',
            'slug' => 'text',
            'type' => 'App\\Aura\\Fields\\Text',
          ],
        ],
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Group',
        'slug' => 'group',
        'type' => 'App\\Aura\\Fields\\Group',
        'fields' => [
          [
            'name' => 'Text',
            'slug' => 'text',
            'type' => 'App\\Aura\\Fields\\Text',
          ],
        ],
        'instructions' => 'This is a group field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>
    </CodeGroup>
  </Col>
</Row>

---

## Panel Field {{ tag: 'Panel', label: 'Aura Base' }}

<Row>
  <Col>

    The panel field allows you to group fields together in a panel.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Panel Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

      <Property name="fields" type="array">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Panel Field" tag="Field" label="\Aura\Fields\Panel.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Panel',
        'slug' => 'panel',
        'type' => 'App\\Aura\\Fields\\Panel',
        'fields' => [
          [
            'name' => 'Text',
            'slug' => 'text',
            'type' => 'App\\Aura\\Fields\\Text',
          ],
        ],
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Panel',
        'slug' => 'panel',
        'type' => 'App\\Aura\\Fields\\Panel',
        'fields' => [
          [
            'name' => 'Text',
            'slug' => 'text',
            'type' => 'App\\Aura\\Fields\\Text',
          ],
        ],
        'instructions' => 'This is a panel field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>
    </CodeGroup>
  </Col>
</Row>

---

## Tab Field {{ tag: 'Tab', label: 'Aura Base' }}

<Row>
  <Col>

    The tab field allows you to group fields together in a tab.

    ### Base Field Properties

    See [Base Field Properties](#base-field-properties) for more information.

    ### Tab Field Properties

    <Properties>

      <Property name="slug" type="string">

      </Property>

      <Property name="fields" type="array">

      </Property>

    </Properties>

  </Col>
  <Col sticky>

    <CodeGroup title="Define Tab Field" tag="Field" label="\Aura\Fields\Tab.php">

    <div title="Basic">
      ```php
      [
        'name' => 'Tab',
        'slug' => 'tab',
        'type' => 'App\\Aura\\Fields\\Tab',
        'fields' => [
          [
            'name' => 'Text',
            'slug' => 'text',
            'type' => 'App\\Aura\\Fields\\Text',
          ],
        ],
      ],
      ```
    </div>

    <div title="Advanced">
      ```php
      [
        'name' => 'Tab',
        'slug' => 'tab',
        'type' => 'App\\Aura\\Fields\\Tab',
        'fields' => [
          [
            'name' => 'Text',
            'slug' => 'text',
            'type' => 'App\\Aura\\Fields\\Text',
          ],
        ],
        'instructions' => 'This is a tab field.',

        'on_index' => true,
        'on_forms' => true,
        'on_view' => true,

        'validation' => '',
        'conditional_logic' => [],
        'style' => [
          'width' => 100,
          'hide_label' => false,
        ],
      ],
      ```
    </div>
    </CodeGroup>
  </Col>
</Row>

---
