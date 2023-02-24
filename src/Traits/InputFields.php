<?php

namespace Eminiarts\Aura\Traits;

use Eminiarts\Aura\ConditionalLogic;
use Eminiarts\Aura\Pipeline\AddIdsToFields;
use Eminiarts\Aura\Pipeline\ApplyParentConditionalLogic;
use Eminiarts\Aura\Pipeline\ApplyTabs;
use Eminiarts\Aura\Pipeline\BuildTreeFromFields;
use Eminiarts\Aura\Pipeline\MapFields;
use Eminiarts\Aura\Pipeline\TransformSlugs;

trait InputFields
{
    use InputFieldsHelpers;
    use InputFieldsTable;
    use InputFieldsValidation;

    public function displayFieldValue($key, $value = null)
    {
        // Check Conditional Logic if the field should be displayed
        if (! $this->shouldDisplayField($key)) {
            return;
        }

        $studlyKey = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $key)));

        // If there is a get{key}Field() method, use that
        if ($value && method_exists($this, 'get'.ucfirst($studlyKey).'Field')) {
            return $this->{'get'.ucfirst($key).'Field'}($value);
        }

        // Maybe delete this one?
        if (optional($this->fieldBySlug($key))['display'] && $value) {
            return $this->fieldBySlug($key)['display']($value);
        }

        // ray('hier', $key, $value);

        if ($value === null && optional($this->meta)->$key) {
            // dump($value, $key, optional($this->meta)->$key);
            return optional($this->fieldClassBySlug($key))->display($this->fieldBySlug($key), optional($this->meta)->$key, $this);
        }

        if ($this->fieldClassBySlug($key)) {
            return optional($this->fieldClassBySlug($key))->display($this->fieldBySlug($key), $value, $this);
        }

        return $value;
    }

    public function editFields()
    {
        return $this->mappedFields()->filter(function ($field) {
            if (optional($field)['on_forms'] === false) {
                return false;
            }

            // if there is a on_update = false, filter it out
            if (optional($field)['on_update'] === false) {
                return false;
            }

            return true;
        });
    }

    public function fieldBySlugWithDefaultValues($slug)
    {
        $field = $this->fieldBySlug($slug);

        if (! isset($field)) {
            return;
        }

        $fieldFields = optional($this->mappedFieldBySlug($slug))['field']->getGroupedFields();

        foreach ($fieldFields as $key => $f) {
            // if no key value pair is set, get the default value from the field
            if (! isset($field[$f['slug']]) && isset($f['default'])) {
                $field[$f['slug']] = $f['default'];
            }
        }

        return $field;
    }

    public function fieldsForView($fields = null)
    {
        if (! $fields) {
            $fields = $this->mappedFields();
        }

        $pipes = [
            ApplyTabs::class,
            MapFields::class,
            AddIdsToFields::class,
            // ApplyParentConditionalLogic::class,
            BuildTreeFromFields::class,
        ];

        return $this->sendThroughPipeline($fields, $pipes);
    }

    public function getAccessibleFieldKeys()
    {
        // Apply Conditional Logic of Parent Fields
        $fields = $this->sendThroughPipeline($this->fieldsCollection(), [
            ApplyTabs::class,
            MapFields::class,
            AddIdsToFields::class,
            ApplyParentConditionalLogic::class,
        ]);

        // Get all input fields
        return $fields
            ->filter(function ($field) {
                return $field['field']->isInputField();
                // return in_array($field['field']->type, ['input', 'repeater', 'group']);
            })
            ->pluck('slug')
            ->filter(function ($field) {
                return $this->shouldDisplayField($field);
            })->toArray();
    }

    public function getFieldsBeforeTree($fields = null)
    {
        // If fields is set and is an array, create a collection
        if ($fields && is_array($fields)) {
            $fields = collect($fields);
        }

        if (! $fields) {
            $fields = $this->fieldsCollection();
        }

        return $this->sendThroughPipeline($fields, [
            MapFields::class,
            AddIdsToFields::class,
            TransformSlugs::class,
            ApplyParentConditionalLogic::class,
        ]);
    }

    public function getFieldsForEdit($fields = null)
    {
        if (! $fields) {
            $fields = $this->mappedFields();
        }

        $pipes = [
            // ApplyTabs::class,
            MapFields::class,
            AddIdsToFields::class,
            BuildTreeFromFields::class,
        ];

        return $this->sendThroughPipeline($fields, $pipes);
    }

    /**
     * This code is used to render the form fields in the correct order.
     * It applies tabs to the fields, maps the fields, adds ids to the fields,
     * applies the parent conditional logic to the fields, and builds a tree from the fields.
     */
    public function getGroupedFields($fields = null): array
    {
        // If fields is set and is an array, create a collection
        if ($fields && is_array($fields)) {
            $fields = collect($fields);
        }

        if (! $fields) {
            $fields = $this->fieldsCollection();
        }

        return $this->sendThroughPipeline($fields, [
            ApplyTabs::class,
            MapFields::class,
            AddIdsToFields::class,
            TransformSlugs::class,
            ApplyParentConditionalLogic::class,
            BuildTreeFromFields::class,
        ]);
    }

    public function indexFields()
    {
        return $this->inputFields()->filter(function ($field) {
            if (optional($field)['on_index'] === false) {
                dd($field['on_index']);

                return false;
            }

            return true;
        });
    }

    /**
     * Map to Grouped Fields for the Posttype Builder / Edit Posttype.
     *
     * @param  array  $fields
     * @return array
     */
    public function mapToGroupedFields($fields)
    {
        $fields = collect($fields)->map(function ($item) {
            $item['field'] = app($item['type'])->field($item);
            $item['field_type'] = app($item['type'])->type;

            return $item;
        });

        return $this->sendThroughPipeline($fields, [
            AddIdsToFields::class,
            BuildTreeFromFields::class,
        ]);
    }

    public function shouldDisplayField($key)
    {
        // Check Conditional Logic if the field should be displayed
        return ConditionalLogic::checkCondition($this, $this->fieldBySlug($key));
    }

    public function taxonomyFields()
    {
        return $this->mappedFields()->filter(function ($field) {
            if ($field['field']->isTaxonomyField()) {
                return true;
            }

            return false;
        });
    }

    // Display the value of a field in the index view
    // $this->displayEmailField()
    // $this->getEmailField()
    // $this->setEmailField()
    // $this->getUsersFieldValues()
    // alternative: $this->queryForUsersField()
}
