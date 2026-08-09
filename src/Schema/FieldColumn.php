<?php

namespace Aura\Base\Schema;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Facades\Schema;

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

    public function addTo(Blueprint $table, string $slug): ColumnDefinition
    {
        $definition = $this->forDriver(Schema::getConnection()->getDriverName());
        $column = $table->{$definition->type}($slug, ...$definition->arguments);

        if ($definition->nullable) {
            $column->nullable();
        }

        return $column;
    }

    public function forDriver(string $driver): self
    {
        $type = $this->driverTypes[$driver] ?? $this->type;

        return new self(
            type: $type,
            arguments: $type === $this->type ? $this->arguments : [],
            nullable: $this->nullable,
        );
    }

    /**
     * @param  array{type: string, arguments?: array<int, int|float|string|bool|null>, nullable?: bool, driver_types?: array<string, string>}  $definition
     */
    public static function fromArray(array $definition): self
    {
        return new self(
            type: $definition['type'],
            arguments: $definition['arguments'] ?? [],
            nullable: $definition['nullable'] ?? true,
            driverTypes: $definition['driver_types'] ?? [],
        );
    }

    /**
     * @return array{type: string, arguments: array<int, int|float|string|bool|null>, nullable: bool, driver_types: array<string, string>}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'arguments' => $this->arguments,
            'nullable' => $this->nullable,
            'driver_types' => $this->driverTypes,
        ];
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
