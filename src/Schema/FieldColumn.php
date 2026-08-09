<?php

namespace Aura\Base\Schema;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Fluent;

readonly class FieldColumn
{
    /**
     * @param  array<int, int|float|string|bool|null>  $arguments
     * @param  array<string, string>  $driverTypes
     */
    public function __construct(
        public string $type,
        public array $arguments = [],
        public bool $nullable = true,
        public array $driverTypes = [],
    ) {}

    public function addTo(Blueprint $table, string $slug): Fluent
    {
        $type = $this->driverTypes[Schema::getConnection()->getDriverName()] ?? $this->type;
        $arguments = $type === $this->type ? $this->arguments : [];
        $column = $table->{$type}($slug, ...$arguments);

        if ($this->nullable) {
            $column->nullable();
        }

        return $column;
    }

    public function toMigration(string $slug, bool $change = false): string
    {
        $column = $this->migrationColumn($this->type, $slug, $this->arguments);

        foreach (array_reverse($this->driverTypes, true) as $driver => $type) {
            $driverName = var_export($driver, true);
            $driverColumn = $this->migrationColumn($type, $slug);
            $column = sprintf(
                '(\Illuminate\Support\Facades\Schema::getConnection()->getDriverName() === %s ? %s : %s)',
                $driverName,
                $driverColumn,
                $column,
            );
        }

        if ($this->nullable) {
            $column .= '->nullable()';
        }

        if ($change) {
            $column .= '->change()';
        }

        return $column;
    }

    /**
     * @param  array<int, int|float|string|bool|null>  $arguments
     */
    private function migrationColumn(string $type, string $slug, array $arguments = []): string
    {
        $serializedArguments = array_map(
            static fn (int|float|string|bool|null $argument): string => var_export($argument, true),
            [$slug, ...$arguments],
        );

        return sprintf('$table->%s(%s)', $type, implode(', ', $serializedArguments));
    }
}
