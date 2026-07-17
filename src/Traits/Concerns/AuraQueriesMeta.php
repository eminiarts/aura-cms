<?php

namespace Aura\Base\Traits\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait AuraQueriesMeta
{
    public function scopeOrWhereMeta($query, ...$args)
    {
        if (count($args) === 3) {
            $key = $args[0];
            $operator = $args[1];
            $value = $args[2];

            return $query->orWhereHas('meta', function ($query) use ($key, $operator, $value) {
                $query->where('key', $key)->where('value', $operator, $value);
            });
        } elseif (count($args) === 2) {
            $key = $args[0];
            $value = $args[1];

            return $query->orWhereHas('meta', function ($query) use ($key, $value) {
                $query->where('key', $key)->where('value', $value);
            });
        } elseif (count($args) === 1 && is_array($args[0])) {
            $metaPairs = $args[0];

            return $query->orWhere(function ($query) use ($metaPairs) {
                foreach ($metaPairs as $key => $value) {
                    $query->whereHas('meta', function ($query) use ($key, $value) {
                        $query->where('key', $key)->where('value', $value);
                    });
                }
            });
        }

        return $query;
    }

    public function scopeWhereInMeta($query, $field, $values)
    {
        if ($values instanceof Collection) {
            $values = $values->toArray();
        }
        if (! is_array($values)) {
            $values = [$values];
        }

        return $query->whereHas('meta', function ($query) use ($field, $values) {
            $query->where('key', $field)->whereIn('value', $values);
        });
    }

    public function scopeWhereMeta($query, ...$args)
    {
        if (count($args) === 3) {
            $key = $args[0];
            $operator = $args[1];
            $value = $args[2];

            return $query->whereHas('meta', function ($query) use ($key, $operator, $value) {
                $query->where('key', $key)->where('value', $operator, $value);
            });
        } elseif (count($args) === 2) {
            $key = $args[0];
            $value = $args[1];

            return $query->whereHas('meta', function ($query) use ($key, $value) {
                $query->where('key', $key)->where('value', $value);
            });
        } elseif (count($args) === 1 && is_array($args[0])) {
            $metaPairs = $args[0];

            return $query->where(function ($query) use ($metaPairs) {
                foreach ($metaPairs as $key => $value) {
                    $query->whereHas('meta', function ($query) use ($key, $value) {
                        $query->where('key', $key)->where('value', $value);
                    });
                }
            });
        }

        return $query;
    }

    /**
     * Scope a query to only include models where meta contains a specific value.
     *
     * @param  Builder  $query
     * @param  string  $key
     * @param  mixed  $value
     * @return Builder
     */
    public function scopeWhereMetaContains($query, $key, $value)
    {
        return $query->whereHas('meta', function ($query) use ($key, $value) {
            // Qualify as meta.value: SQLite's json_each() also exposes a "value"
            // column, so an unqualified whereJsonContains('value', ...) is ambiguous
            // and never matches on the test driver.
            $column = $query->getModel()->getTable().'.value';

            $query->where('key', $key)
                ->where(function ($query) use ($column, $value) {
                    // Match either string or numeric JSON array elements (e.g. "1" and 1).
                    $query->whereJsonContains($column, (string) $value);

                    if (is_numeric($value)) {
                        $query->orWhereJsonContains($column, (int) $value);
                    }
                });
        });
    }

    public function scopeWhereNotInMeta($query, $field, $values)
    {
        if ($values instanceof Collection) {
            $values = $values->toArray();
        }
        if (! is_array($values)) {
            $values = [$values];
        }

        return $query->whereDoesntHave('meta', function ($query) use ($field, $values) {
            $query->where('key', $field)->whereIn('value', $values);
        });
    }
}
