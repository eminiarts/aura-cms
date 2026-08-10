<?php

namespace Aura\Base\Table;

use Aura\Base\Resource;
use InvalidArgumentException;

final readonly class TableParentScope
{
    /**
     * @param  class-string<resource>  $parentResource
     */
    private function __construct(
        public string $key,
        public string $parentResource,
        public string $foreignKey,
        public string $ability,
    ) {
        if (trim($key) === '' || trim($foreignKey) === '' || trim($ability) === '') {
            throw new InvalidArgumentException('Table parent scope declarations require a key, foreign key, and ability.');
        }

        if (! is_a($parentResource, Resource::class, true)) {
            throw new InvalidArgumentException('A table parent scope must declare an Aura resource class.');
        }
    }

    /**
     * @param  class-string<resource>  $parentResource
     */
    public static function foreignKey(
        string $key,
        string $parentResource,
        string $foreignKey,
        string $ability = 'view',
    ): self {
        return new self($key, $parentResource, $foreignKey, $ability);
    }
}
