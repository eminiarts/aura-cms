<?php

namespace Aura\Base\Tests\Fixtures\Reporting;

use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Number;
use Aura\Base\Fields\Select;
use Aura\Base\Fields\Text;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use RuntimeException;

final class AuthorizedReportingQueryProbe
{
    /**
     * @return list<array{key: string|null, label: string, value: int, row_count: int}>
     */
    public function groupedCount(Resource $prototype, string $field, int $maximumGroups = 100): array
    {
        $fieldClass = $prototype->fieldClassBySlug($field);

        if (! $prototype->isTableField($field)
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

        return $rows->map(static function (Resource $row): array {
            $key = $row->getAttribute('group_key');

            return [
                'key' => $key === null ? null : (string) $key,
                'label' => $key === null ? 'Empty' : (string) $key,
                'value' => (int) $row->getAttribute('aggregate'),
                'row_count' => (int) $row->getAttribute('aggregate'),
            ];
        })->all();
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
}
