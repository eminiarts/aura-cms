<?php

namespace Aura\Base\Contracts;

use Illuminate\Database\Eloquent\Model;

interface FieldValueContract
{
    /**
     * Convert a value read from a physical column or Aura meta row into its
     * application representation.
     *
     * @param  array<string, mixed>  $field
     */
    public function hydrateFromStorage(
        mixed $value,
        array $field,
        ?Model $model,
        FieldValueStorage $storage,
        FieldValueContext $context = FieldValueContext::Model,
    ): mixed;

    /**
     * Convert form/import input into the portable value persisted by Aura.
     *
     * @param  array<string, mixed>  $field
     */
    public function normalizeForStorage(
        mixed $value,
        array $field,
        ?Model $model,
        FieldValueStorage $storage,
    ): mixed;
}
