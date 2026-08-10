<?php

namespace Aura\Base\Traits\Concerns;

use Aura\Base\ConditionalLogic;
use Aura\Base\Contracts\FieldValueContext;
use Aura\Base\Resource;
use Aura\Base\Support\FieldDisplayValue;
use Aura\Base\Table\TableColumnRegistry;
use Illuminate\Support\Collection;

trait AuraResourceTableConfig
{
    protected FieldValueContext $fieldDisplayContext = FieldValueContext::Index;

    public function display($key)
    {
        if ($this instanceof Resource) {
            $computed = (new TableColumnRegistry)->find($this, (string) $key);

            if ($computed !== null) {
                return $computed->render($this);
            }
        }

        $context = $this->fieldDisplayContext;
        $field = $this->fieldBySlug($key);
        $isInputField = in_array($key, $this->inputFieldsSlugs(), true);

        if (! $isInputField && $field) {
            $fieldClass = $this->fieldClassBySlug($key);

            if ($fieldClass && $fieldClass->rendersConfiguredFieldOnIndex($field)) {
                return $fieldClass->display($field, null, $this);
            }
        }

        // Fast path: a plain input field without conditional logic resolves to
        // exactly the same value it would have inside the full `fields`
        // collection, so resolve just this one field instead of building every
        // field value for every rendered cell.
        if ($isInputField && $this->canFastPathDisplay($key, $field)) {
            return $this->formatDisplayValue($key, $this->resolveDisplayFieldValue($key, $context), $context);
        }

        // Keys that are not input fields (id, title, raw attributes) are never
        // present in the `fields` collection, so building it is wasted work —
        // resolve the raw attribute directly.
        if (! $isInputField) {
            return $this->displayRawAttribute($key);
        }

        // Full accessor path: input fields with conditional logic (whose
        // closures may need the complete `fields` structure) and fields that the
        // accessor may filter out.
        $fields = $this->fields;

        if ($fields instanceof Collection) {
            $fields = $fields->toArray();
        }

        if (! is_array($fields)) {
            $fields = [];
        }

        if (array_key_exists($key, $fields)) {
            return $this->formatDisplayValue(
                $key,
                $this->resolveDisplayFieldValue($key, $context),
                $context,
            );
        }

        return $this->displayRawAttribute($key);
    }

    /**
     * Display a field in an explicit UI context without changing the legacy
     * display($key) signature that host resources may override.
     */
    public function displayInContext($key, FieldValueContext $context): mixed
    {
        $previousContext = $this->fieldDisplayContext;
        $this->fieldDisplayContext = $context;

        try {
            return $this->display($key);
        } finally {
            $this->fieldDisplayContext = $previousContext;
        }
    }

    /**
     * Render a field through the explicit export presentation contract.
     */
    public function exportFieldValue(string $key): mixed
    {
        if ($this instanceof Resource) {
            $computed = (new TableColumnRegistry)->find($this, $key);

            if ($computed !== null) {
                return $computed->export($this);
            }
        }

        return $this->displayInContext($key, FieldValueContext::Export);
    }

    public function getHeaders()
    {
        $fields = $this->indexFields();

        // Filter $fields based on Conditional Logic for roles
        $fields = $fields->filter(function ($field) {
            return ConditionalLogic::fieldIsVisibleTo($field, auth()->user());
        });

        $fields = $fields->pluck('name', 'slug')
            ->when($this->usesTitle(), function ($collection, $value) {
                return $collection->prepend('title', 'title');
            })
            ->prepend('ID', 'id');

        return $fields;
    }

    public function indexTableSettings()
    {
        return [];
    }

    public function isNumberField($key)
    {
        return data_get($this->fieldBySlug($key), 'type') === 'Aura\\Base\\Fields\\Number';
    }

    /**
     * Decide whether display($key) can skip the full `fields` accessor and
     * resolve only the requested field. Callers must already have confirmed the
     * key is an input field slug.
     */
    protected function canFastPathDisplay(string $key, $field): bool
    {
        // No field definition: let the full path handle attribute fallback.
        if (! $field) {
            return false;
        }

        // Conditional logic may depend on the complete `fields` structure or on
        // other fields' resolved values, so keep those on the full path.
        if (! empty($field['conditional_logic'])) {
            return false;
        }

        // Hidden keys (e.g. 'meta') are filtered out of the accessor.
        if (in_array($key, $this->hidden, true)) {
            return false;
        }

        // Nested/dotted fields are filtered out of the accessor; keep their
        // (null) behavior on the full path.
        if (str_contains($key, '.')) {
            return false;
        }

        return true;
    }

    /**
     * Resolve a raw (non-input-field) attribute for display. Mirrors the
     * attribute-fallback branch of the previous display() implementation,
     * including HTML-escaping since table/view blades render the result raw.
     */
    protected function displayRawAttribute(string $key)
    {
        if (! isset($this->{$key})) {
            return;
        }

        $value = $this->{$key};

        // This branch bypasses the field contract and is rendered through a
        // raw Blade slot, so recursively escape every non-Htmlable value.
        return FieldDisplayValue::escape($value);
    }

    /**
     * Run a resolved field value through the field's display() and flatten any
     * array result the same way the table cell expects.
     */
    protected function formatDisplayValue(
        string $key,
        $rawValue,
        FieldValueContext $context = FieldValueContext::Index,
    ) {
        $value = $this->displayFieldValueInContext($key, $rawValue, $context);

        return FieldDisplayValue::secure($value);
    }

    protected function resolveDisplayFieldValue(string $key, FieldValueContext $context): mixed
    {
        if (method_exists($this, 'resolveFieldValueInContext')) {
            return $this->resolveFieldValueInContext($key, $context);
        }

        return $this->resolveFieldValue($key);
    }
}
