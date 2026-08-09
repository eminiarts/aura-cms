<?php

namespace Aura\Base\Schema;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;

readonly class FieldColumn
{
    /**
     * @param  array<int, int|float|string|bool|null>  $arguments
     */
    public function __construct(
        public string $type,
        public array $arguments = [],
        public bool $nullable = true,
    ) {}

    public function addTo(Blueprint $table, string $slug): Fluent
    {
        $column = $table->{$this->type}($slug, ...$this->arguments);

        if ($this->nullable) {
            $column->nullable();
        }

        return $column;
    }

    public function toMigration(string $slug, bool $change = false): string
    {
        $arguments = array_map(
            static fn (int|float|string|bool|null $argument): string => var_export($argument, true),
            [$slug, ...$this->arguments],
        );

        $column = sprintf('$table->%s(%s)', $this->type, implode(', ', $arguments));

        if ($this->nullable) {
            $column .= '->nullable()';
        }

        if ($change) {
            $column .= '->change()';
        }

        return $column;
    }
}
