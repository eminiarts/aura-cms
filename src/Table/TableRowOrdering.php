<?php

namespace Aura\Base\Table;

use InvalidArgumentException;

final readonly class TableRowOrdering
{
    private function __construct(
        public string $column,
        public string $direction,
        public string $ability,
    ) {
        if (preg_match('/\A[A-Za-z][A-Za-z0-9_]*\z/', $column) !== 1) {
            throw new InvalidArgumentException('Table row ordering requires a physical column identifier.');
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException('Table row ordering direction must be asc or desc.');
        }

        if (preg_match('/\A[A-Za-z][A-Za-z0-9._:-]*\z/', $ability) !== 1) {
            throw new InvalidArgumentException('Table row ordering requires a valid authorization ability.');
        }
    }

    public static function make(
        string $column,
        string $direction = 'asc',
        string $ability = 'update',
    ): self {
        return new self($column, strtolower($direction), $ability);
    }
}
