<?php

namespace Aura\Base\Contracts;

use Illuminate\Database\Eloquent\Model;

interface FieldPresentationContract
{
    /**
     * Render a hydrated value for a declared UI context.
     *
     * @param  array<string, mixed>  $field
     */
    public function presentValue(
        mixed $value,
        array $field,
        ?Model $model,
        FieldValueContext $context = FieldValueContext::Index,
    ): mixed;
}
