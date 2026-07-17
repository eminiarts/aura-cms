<?php

namespace Aura\Base\Contracts;

use Illuminate\Database\Eloquent\Collection;

/**
 * Optional contract for field types that can batch-resolve their table
 * display values for a whole page of rows in a single query.
 *
 * The table invokes preloadTableDisplay() once per visible column after
 * pagination. Implementations should read the per-row foreign value, issue a
 * single scoped query for all rows, and prime each row via
 * Resource::setTableDisplayValue() so that display() resolves without a
 * per-row query. Scopes (TeamScope/TypeScope/ScopedScope) MUST be preserved:
 * use ::query(), never withoutGlobalScopes().
 */
interface PreloadsTableDisplay
{
    /**
     * Prime per-row table-display values for a paginated collection of rows.
     *
     * @param  Collection  $rows  The models on the current page.
     * @param  array  $field  The field definition.
     */
    public function preloadTableDisplay(Collection $rows, array $field): void;
}
