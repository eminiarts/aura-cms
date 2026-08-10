<?php

namespace Aura\Base\Table;

use InvalidArgumentException;

final class FilterGroupStateMutator
{
    /**
     * @param  list<array<string, mixed>>  $groups
     * @param  array<string, mixed>  $filter
     * @return list<array<string, mixed>>
     */
    public function addFilter(array $groups, int $groupIndex, array $filter): array
    {
        if (! isset($groups[$groupIndex]['filters']) || ! is_array($groups[$groupIndex]['filters'])) {
            throw new InvalidArgumentException('Unknown filter group.');
        }

        $groups[$groupIndex]['filters'][] = $filter;

        return array_values($groups);
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     * @param  array<string, mixed>  $filter
     * @return list<array<string, mixed>>
     */
    public function addGroup(array $groups, array $filter): array
    {
        $groups[] = ['filters' => [$filter]];

        return array_values($groups);
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     * @return list<array<string, mixed>>
     */
    public function removeFilter(array $groups, int $groupIndex, int $filterIndex): array
    {
        if (! isset($groups[$groupIndex]['filters'][$filterIndex])) {
            throw new InvalidArgumentException('Unknown filter.');
        }

        unset($groups[$groupIndex]['filters'][$filterIndex]);
        $groups[$groupIndex]['filters'] = array_values($groups[$groupIndex]['filters']);

        if ($groups[$groupIndex]['filters'] === []) {
            return $this->removeGroup($groups, $groupIndex);
        }

        return array_values($groups);
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     * @return list<array<string, mixed>>
     */
    public function removeGroup(array $groups, int $groupIndex): array
    {
        if (! array_key_exists($groupIndex, $groups)) {
            throw new InvalidArgumentException('Unknown filter group.');
        }

        unset($groups[$groupIndex]);

        return array_values($groups);
    }
}
