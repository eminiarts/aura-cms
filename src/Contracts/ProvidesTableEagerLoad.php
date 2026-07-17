<?php

namespace Aura\Base\Contracts;

/**
 * Optional contract for relation field types that can be safely eager-loaded
 * on the table query before pagination.
 *
 * This is intentionally opt-in: the table never infers eager-loadable
 * relations from isRelation() alone because a field slug may collide with a
 * real model method, a relation may be non-polymorphic, or a relation may
 * carry per-instance constraints (e.g. team scoping) that break generic
 * eager loading.
 */
interface ProvidesTableEagerLoad
{
    /**
     * Return the Eloquent relation name(s) to eager-load for table rendering,
     * or null when the field cannot be safely eager-loaded.
     *
     * @param  array  $field  The field definition.
     * @return string|array<int, string>|null
     */
    public function tableEagerLoad(array $field): string|array|null;
}
