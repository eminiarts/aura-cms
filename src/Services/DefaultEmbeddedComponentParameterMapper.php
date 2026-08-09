<?php

namespace Aura\Base\Services;

use Aura\Base\Contracts\MapsEmbeddedComponentParameters;

final class DefaultEmbeddedComponentParameterMapper implements MapsEmbeddedComponentParameters
{
    public function map(EmbeddedComponentContext $context): array
    {
        return [
            'resourceType' => $context->resource::class,
            'resourceId' => $context->resource->getKey(),
            'fieldSlug' => (string) ($context->field['slug'] ?? ''),
            'context' => $context->surface->value,
        ];
    }
}
