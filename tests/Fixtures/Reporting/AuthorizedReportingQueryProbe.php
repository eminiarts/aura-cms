<?php

namespace Aura\Base\Tests\Fixtures\Reporting;

use Aura\Base\Contracts\FieldValueContext;
use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Number;
use Aura\Base\Fields\Select;
use Aura\Base\Fields\Text;
use Aura\Base\Resource;
use Aura\Base\Support\ExactDecimal;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use RuntimeException;

final readonly class ReportingGroupPoint
{
    public function __construct(
        public ?string $key,
        public string $label,
        public int $value,
        public int $rowCount,
    ) {}
}

final class AuthorizedReportingQueryProbe
{
    /**
     * @return list<ReportingGroupPoint>
     */
    public function groupedCount(Resource $prototype, string $field, int $maximumGroups = 100): array
    {
        $fieldClass = $prototype->fieldClassBySlug($field);
        $fieldDefinition = $prototype->fieldBySlug($field);

        if (! $prototype->isTableField($field)
            || ! is_array($fieldDefinition)
            || ! ($fieldClass instanceof Boolean
                || $fieldClass instanceof Number
                || $fieldClass instanceof Select
                || $fieldClass instanceof Text)) {
            throw new InvalidArgumentException("The reporting group field [{$field}] is not an eligible physical scalar.");
        }

        if ($maximumGroups < 1 || $maximumGroups > 100) {
            throw new InvalidArgumentException('Reporting group cardinality must be between 1 and 100.');
        }

        $query = $this->query($prototype);
        $qualifiedField = $prototype->qualifyColumn($field);
        $wrappedField = $query->getQuery()->getGrammar()->wrap($qualifiedField);
        $rows = $query
            ->selectRaw("{$wrappedField} as group_key")
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy($qualifiedField)
            ->orderByRaw("CASE WHEN {$wrappedField} IS NULL THEN 1 ELSE 0 END")
            ->orderBy($qualifiedField)
            ->limit($maximumGroups + 1)
            ->get();

        if ($rows->count() > $maximumGroups) {
            throw new RuntimeException("Reporting groups exceed the [{$maximumGroups}] point limit.");
        }

        $points = $rows->map(function (Resource $row) use ($fieldClass, $fieldDefinition, $prototype): ReportingGroupPoint {
            $rawKey = $row->getAttribute('group_key');
            $key = $this->canonicalGroupKey($rawKey, $fieldClass);
            $label = $key ?? 'Empty';

            if ($rawKey !== null && $fieldClass instanceof Select) {
                $presented = $fieldClass->presentValue(
                    $rawKey,
                    $fieldDefinition,
                    $prototype,
                    FieldValueContext::Export,
                );
                $label = $presented instanceof Htmlable ? $presented->toHtml() : (string) $presented;
            }

            return new ReportingGroupPoint(
                key: $key,
                label: $label,
                value: (int) $row->getAttribute('aggregate'),
                rowCount: (int) $row->getAttribute('aggregate'),
            );
        });

        return $points->sort(static function (ReportingGroupPoint $left, ReportingGroupPoint $right) use ($fieldClass): int {
            if ($left->key === null || $right->key === null) {
                return $left->key === $right->key ? 0 : ($left->key === null ? 1 : -1);
            }

            $leftKey = $fieldClass instanceof Number ? ExactDecimal::sortableKey($left->key) : $left->key;
            $rightKey = $fieldClass instanceof Number ? ExactDecimal::sortableKey($right->key) : $right->key;

            return strcmp($leftKey, $rightKey);
        })->values()->all();
    }

    /** @return Builder<resource> */
    public function query(Resource $prototype): Builder
    {
        Gate::authorize('viewAny', $prototype);

        $query = $prototype->newQuery();

        if (method_exists($prototype, 'indexQuery')) {
            $query = $prototype->indexQuery($query);
        }

        return $query;
    }

    private function canonicalGroupKey(mixed $value, object $fieldClass): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($fieldClass instanceof Boolean) {
            return (bool) $value ? '1' : '0';
        }

        if (! $fieldClass instanceof Number) {
            return (string) $value;
        }

        $decimal = trim((string) $value);

        if (preg_match('/\A([+-]?)(\d+)(?:\.(\d{1,6}))?\z/', $decimal, $matches) !== 1) {
            throw new RuntimeException('The reporting number group key is not an exact six-decimal value.');
        }

        $integer = ltrim($matches[2], '0');
        $integer = $integer === '' ? '0' : $integer;
        $fraction = str_pad($matches[3] ?? '', 6, '0');
        $negative = $matches[1] === '-' && ($integer !== '0' || trim($fraction, '0') !== '');

        return ($negative ? '-' : '').$integer.'.'.$fraction;
    }
}
