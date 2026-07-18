<?php

namespace Aura\Base\Traits\Concerns;

trait AuraResourceTaxonomy
{
    public static $taxonomy = false;

    public array $taxonomyFields = [];

    public function isTaxonomy()
    {
        return static::$taxonomy;
    }

    public function isTaxonomyField($key)
    {
        // Check if the Field is a taxonomy 'type' => 'Aura\\Base\\Fields\\Tags',
        if (in_array($key, $this->inputFieldsSlugs())) {
            $field = $this->fieldBySlug($key);

            // Atm only tags, refactor later
            if (isset($field['type']) && $field['type'] == 'Aura\\Base\\Fields\\Tags') {
                return true;
            }
        }

        return false;
    }

    public function saveTaxonomyFields(array $taxonomyFields): void
    {
        $this->taxonomyFields = array_merge($this->taxonomyFields, $taxonomyFields);
    }
}
