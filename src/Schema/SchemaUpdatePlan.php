<?php

namespace Aura\Base\Schema;

use JsonException;
use RuntimeException;

readonly class SchemaUpdatePlan
{
    private const MARKER = '// aura-schema-plan:v1:';

    /**
     * @param  array<string, FieldColumn>  $columns
     * @param  array<int, string>  $preservedColumns
     */
    public function __construct(
        public string $table,
        public array $columns,
        public array $preservedColumns = ['id', 'user_id', 'team_id', 'created_at', 'updated_at', 'deleted_at'],
    ) {
        if ($this->table === '') {
            throw new RuntimeException('Aura schema plans require a table name.');
        }

        foreach ($this->columns as $slug => $column) {
            if (! is_string($slug) || $slug === '' || ! $column instanceof FieldColumn) {
                throw new RuntimeException('Aura schema plan columns are invalid.');
            }
        }
    }

    public function embedIn(string $content): string
    {
        $lines = array_values(array_filter(
            preg_split('/\R/', $content) ?: [],
            static fn (string $line): bool => ! str_starts_with(trim($line), self::MARKER),
        ));
        $marker = self::MARKER.base64_encode(json_encode($this->toArray(), JSON_THROW_ON_ERROR));
        $phpTag = array_search('<?php', array_map('trim', $lines), true);

        if ($phpTag === false) {
            throw new RuntimeException('Unable to embed an Aura schema plan in a migration without a PHP opening tag.');
        }

        array_splice($lines, $phpTag + 1, 0, ['', $marker]);

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    public static function fromMigrationFile(string $path): self
    {
        $content = file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException("Unable to read migration [{$path}].");
        }

        try {
            token_get_all($content, TOKEN_PARSE);
        } catch (\ParseError $exception) {
            throw new RuntimeException("Migration [{$path}] contains invalid PHP: {$exception->getMessage()}", previous: $exception);
        }

        $markers = array_values(array_filter(
            preg_split('/\R/', $content) ?: [],
            static fn (string $line): bool => str_starts_with(trim($line), self::MARKER),
        ));

        if (count($markers) !== 1) {
            throw new RuntimeException("Migration [{$path}] must contain exactly one Aura schema plan.");
        }

        $encoded = substr(trim($markers[0]), strlen(self::MARKER));
        $json = base64_decode($encoded, true);

        if ($json === false) {
            throw new RuntimeException("Migration [{$path}] contains an invalid Aura schema plan.");
        }

        try {
            $plan = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Migration [{$path}] contains an invalid Aura schema plan: {$exception->getMessage()}", previous: $exception);
        }

        if (! is_array($plan) || ! is_string($plan['table'] ?? null) || ! is_array($plan['columns'] ?? null)) {
            throw new RuntimeException("Migration [{$path}] contains an invalid Aura schema plan shape.");
        }

        $columns = [];

        foreach ($plan['columns'] as $slug => $definition) {
            if (! is_string($slug) || ! is_array($definition) || ! is_string($definition['type'] ?? null)) {
                throw new RuntimeException("Migration [{$path}] contains an invalid Aura column definition.");
            }

            $columns[$slug] = FieldColumn::fromArray($definition);
        }

        $preserved = $plan['preserved_columns'] ?? [];

        if (! is_array($preserved) || array_filter($preserved, fn (mixed $column): bool => ! is_string($column)) !== []) {
            throw new RuntimeException("Migration [{$path}] contains invalid preserved columns.");
        }

        return new self($plan['table'], $columns, array_values($preserved));
    }

    /**
     * @return array{table: string, columns: array<string, array{type: string, arguments: array<int, int|float|string|bool|null>, nullable: bool, driver_types: array<string, string>}>, preserved_columns: array<int, string>}
     */
    public function toArray(): array
    {
        return [
            'table' => $this->table,
            'columns' => array_map(
                static fn (FieldColumn $column): array => $column->toArray(),
                $this->columns,
            ),
            'preserved_columns' => $this->preservedColumns,
        ];
    }
}
