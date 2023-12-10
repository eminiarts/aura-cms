<?php

namespace Eminiarts\Aura\Traits;

use Eminiarts\Aura\ConditionalLogic;
use Eminiarts\Aura\Pipeline\AddIdsToFields;
use Eminiarts\Aura\Pipeline\ApplyParentConditionalLogic;
use Eminiarts\Aura\Pipeline\ApplyParentDisplayAttributes;
use Eminiarts\Aura\Pipeline\ApplyTabs;
use Eminiarts\Aura\Pipeline\BuildTreeFromFields;
use Eminiarts\Aura\Pipeline\DoNotDeferConditionalLogic;
use Eminiarts\Aura\Pipeline\FilterCreateFields;
use Eminiarts\Aura\Pipeline\FilterEditFields;
use Eminiarts\Aura\Pipeline\FilterViewFields;
use Eminiarts\Aura\Pipeline\MapFields;
use Eminiarts\Aura\Pipeline\RemoveClosureAttributes;
use Eminiarts\Aura\Pipeline\RemoveValidationAttribute;
use Eminiarts\Aura\Pipeline\TransformSlugs;

trait InputFields
{
    use InputFieldsHelpers;
    use InputFieldsTable;
    use InputFieldsValidation;

    private $accessibleFieldKeysCache = null;

    public function createFields()
    {
        // Apply Conditional Logic of Parent Fields
        return $this->sendThroughPipeline($this->fieldsCollection(), [
            ApplyTabs::class,
            MapFields::class,
            AddIdsToFields::class,
            ApplyParentConditionalLogic::class,
            DoNotDeferConditionalLogic::class,
            ApplyParentDisplayAttributes::class,
            FilterCreateFields::class,
            BuildTreeFromFields::class,
        ]);
    }

    public function displayFieldValue($key, $value = null)
    {
        // return $value;

        // Check Conditional Logic if the field should be displayed
        if (! $this->shouldDisplayField($this->fieldBySlug($key))) {
            return;
        }

        $studlyKey = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $key)));

        // If there is a get{key}Field() method, use that
        if ($value && method_exists($this, 'get'.ucfirst($studlyKey).'Field')) {
            return $this->{'get'.ucfirst($key).'Field'}($value);
        }

        // Maybe delete this one?
        if (optional($this->fieldBySlug($key))['display'] && $value) {
            return $this->fieldBySlug($key)['display']($value, $this);
        }

        if ($value === null && optional(optional($this)->meta)->$key) {
            return optional($this->fieldClassBySlug($key))->display($this->fieldBySlug($key), optional($this->meta)->$key, $this);
        }

        if ($this->fieldClassBySlug($key)) {
            return optional($this->fieldClassBySlug($key))->display($this->fieldBySlug($key), $value, $this);
        }

        return $value;
    }

    public function editFields()
    {
        // Apply Conditional Logic of Parent Fields
        return $this->sendThroughPipeline($this->fieldsCollection(), [
            ApplyTabs::class,
            MapFields::class,
            AddIdsToFields::class,
            ApplyParentConditionalLogic::class,
            DoNotDeferConditionalLogic::class,
            ApplyParentDisplayAttributes::class,
            FilterEditFields::class,
            RemoveClosureAttributes::class,
            BuildTreeFromFields::class,
        ]);
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

    // public function getAccessibleFieldKeys()
    // {
    //     if ($this->accessibleFieldKeysCache === null) {
    //         // Apply Conditional Logic of Parent Fields
    //         $fields = $this->sendThroughPipeline($this->fieldsCollection(), [
    //             ApplyTabs::class,
    //             MapFields::class,
    //             AddIdsToFields::class,
    //             ApplyParentConditionalLogic::class,
    //             DoNotDeferConditionalLogic::class,
    //         ]);

    //         // Get all input fields
    //         $this->accessibleFieldKeysCache = $fields
    //             ->filter(function ($field) {
    //                 return $field['field']->isInputField();
    //             })
    //             ->pluck('slug')
    //             ->filter(function ($field) {
    //                 // return true;
    //                 return $this->shouldDisplayField($this->fieldBySlug($field));
    //             })
    //             ->toArray();
    //     }

    //     return $this->accessibleFieldKeysCache;
    // }

    public function fieldsForView($fields = null, $pipes = null)
    {
        if (! $fields) {
            $fields = $this->mappedFields();
        }

        if (! $pipes) {
            $pipes = [
                ApplyTabs::class,
                MapFields::class,
                AddIdsToFields::class,
                ApplyParentConditionalLogic::class,
                DoNotDeferConditionalLogic::class,
                ApplyParentDisplayAttributes::class,
                FilterViewFields::class,
                RemoveValidationAttribute::class,
                BuildTreeFromFields::class,
            ];
        }

        return $this->sendThroughPipeline($fields, $pipes);
    }

    public function fieldsHaveClosures($fields)
    {
        foreach ($fields as $field) {
            foreach ($field as $value) {
                if (is_array($value)) {
                    if ($this->fieldsHaveClosures([$value])) {
                        return true;
                    }
                } elseif ($value instanceof \Closure) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getFieldsBeforeTree($fields = null)
    {
        $cacheKey = get_class($this).'-getFieldsBeforeTree';

        if (! app()->bound($cacheKey)) {
            // If fields is set and is an array, create a collection
            if ($fields && is_array($fields)) {
                $fields = collect($fields);
            }

            if (! $fields) {
                $fields = $this->fieldsCollection();
            }

            $fieldsBeforeTree = $this->sendThroughPipeline($fields, [
                MapFields::class,
                AddIdsToFields::class,
                TransformSlugs::class,
                ApplyParentConditionalLogic::class,
                DoNotDeferConditionalLogic::class,
            ]);

            app()->singleton($cacheKey, function () use ($fieldsBeforeTree) {
                return $fieldsBeforeTree;
            });

        }

        return app($cacheKey);

    }

    // Used in Posttype
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
    public function getGroupedFields($fields = null, $pipes = null): array
    {
        // If fields is set and is an array, create a collection
        if ($fields && is_array($fields)) {
            $fields = collect($fields);
        }

        if (! $fields) {
            $fields = $this->fieldsCollection();
        }

        if (! $pipes) {
            $pipes = [
                ApplyTabs::class,
                MapFields::class,
                AddIdsToFields::class,
                ApplyParentConditionalLogic::class,
                DoNotDeferConditionalLogic::class,
                ApplyParentDisplayAttributes::class,
                FilterViewFields::class,
                BuildTreeFromFields::class,
            ];
        }

        return $this->sendThroughPipeline($fields, $pipes);
    }

    public function indexFields()
    {
        return $this->inputFields()->filter(function ($field) {
            if (optional($field)['on_index'] === false) {
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

    public function shouldDisplayField($field)
    {
        return ConditionalLogic::shouldDisplayField($this, $field);
    }

    public function taxonomyFields()
    {
        return $this->mappedFields()->filter(function ($field) {
            if (optional(optional($field)['field'])->isTaxonomyField()) {
                return true;
            }

            return false;
        });
    }

    public function viewFields()
    {
        return $this->sendThroughPipeline($this->mappedFields(), [
            ApplyTabs::class,
            MapFields::class,
            AddIdsToFields::class,
            ApplyParentConditionalLogic::class,
            DoNotDeferConditionalLogic::class,
            ApplyParentDisplayAttributes::class,
            FilterViewFields::class,
            BuildTreeFromFields::class,
        ]);
    }
}
