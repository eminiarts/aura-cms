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
        public int|float|string|bool|null $default = null,
        public bool $unsigned = false,
    ) {}

    public function addTo(Blueprint $table, string $slug): ColumnDefinition
    {
        $definition = $this->forDriver(Schema::getConnection()->getDriverName());
        $column = $table->{$definition->type}($slug, ...$definition->arguments);

        if ($definition->nullable) {
            $column->nullable();
        }

        if ($definition->default !== null) {
            $column->default($definition->default);
        }

        if ($definition->unsigned) {
            $column->unsigned();
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
            default: $this->default,
            unsigned: $this->unsigned,
        );
    }

    /**
     * @param  array{type: string, arguments?: array<int, int|float|string|bool|null>, nullable?: bool, driver_types?: array<string, string>, default?: int|float|string|bool|null, unsigned?: bool}  $definition
     */
    public static function fromArray(array $definition): self
    {
        return new self(
            type: $definition['type'],
            arguments: $definition['arguments'] ?? [],
            nullable: $definition['nullable'] ?? true,
            driverTypes: $definition['driver_types'] ?? [],
            default: $definition['default'] ?? null,
            unsigned: $definition['unsigned'] ?? false,
        );
    }

    /** @param array<string, mixed> $column */
    public function matchesDatabaseColumn(array $column, string $driver): bool
    {
        $expected = $this->forDriver($driver);
        $actualType = strtolower((string) ($column['type_name'] ?? $column['type'] ?? ''));
        $actualType = preg_replace('/\(.*/', '', $actualType) ?? $actualType;
        $aliases = [
            'bigInteger' => ['bigint', 'int8'],
            'dateTime' => $driver === 'pgsql'
                ? ['timestamp', 'timestamp without time zone']
                : ['datetime'],
            'decimal' => ['decimal', 'numeric'],
            'integer' => ['integer', 'int', 'int4'],
            'string' => ['varchar', 'character varying'],
            'text' => ['text'],
            'date' => ['date'],
            'timestamp' => ['timestamp', 'timestamp without time zone'],
        ];

        if (! in_array($actualType, $aliases[$expected->type] ?? [strtolower($expected->type)], true)) {
            return false;
        }

        if (array_key_exists('nullable', $column) && (bool) $column['nullable'] !== $expected->nullable) {
            return false;
        }

        if (self::canonicalDefault($column['default'] ?? null) !== self::canonicalDefault($expected->default)) {
            return false;
        }

        $actualUnsigned = str_contains(strtolower((string) ($column['type'] ?? '')), 'unsigned');

        if ($driver === 'mysql' && $actualUnsigned !== $expected->unsigned) {
            return false;
        }

        if (in_array($expected->type, ['decimal', 'unsignedDecimal'], true) && count($expected->arguments) >= 2) {
            [$precision, $scale] = $expected->arguments;
            $actualPrecision = $column['precision'] ?? null;
            $actualScale = $column['scale'] ?? null;

            if ($actualPrecision !== null && (int) $actualPrecision !== (int) $precision) {
                return false;
            }

            if ($actualScale !== null && (int) $actualScale !== (int) $scale) {
                return false;
            }
        }

        if ($expected->type === 'string' && isset($expected->arguments[0], $column['length'])) {
            return (int) $column['length'] === (int) $expected->arguments[0];
        }

        return true;
    }

    /**
     * @return array{type: string, arguments: array<int, int|float|string|bool|null>, nullable: bool, driver_types: array<string, string>, default: int|float|string|bool|null, unsigned: bool}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'arguments' => $this->arguments,
            'nullable' => $this->nullable,
            'driver_types' => $this->driverTypes,
            'default' => $this->default,
            'unsigned' => $this->unsigned,
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

        if ($this->default !== null) {
            $column .= '->default('.var_export($this->default, true).')';
        }

        if ($this->unsigned) {
            $column .= '->unsigned()';
        }

        if ($change) {
            $column .= '->change()';
        }

        return $column;
    }

    private static function canonicalDefault(mixed $default): ?string
    {
        if ($default === null) {
            return null;
        }

        if (is_bool($default)) {
            return $default ? '1' : '0';
        }

        $value = trim((string) $default);
        $value = preg_replace('/::[a-z_ ]+$/i', '', $value) ?? $value;

        while (strlen($value) >= 2 && $value[0] === '(' && $value[-1] === ')') {
            $value = trim(substr($value, 1, -1));
        }

        if (strlen($value) >= 2 && in_array($value[0], ["'", '"'], true) && $value[-1] === $value[0]) {
            $value = substr($value, 1, -1);
        }

        return preg_match('/^(?:current_(?:date|time|timestamp)|null|true|false)$/i', $value) === 1
            ? strtolower($value)
            : $value;
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
