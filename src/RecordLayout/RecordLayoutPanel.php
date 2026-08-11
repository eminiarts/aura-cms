<?php

namespace Aura\Base\RecordLayout;

use InvalidArgumentException;
use Livewire\Component;

final readonly class RecordLayoutPanel
{
    /**
     * @param  class-string<Component>  $component
     * @param  list<class-string|non-empty-string>  $resources
     * @param  list<non-empty-string>  $eagerLoad
     */
    public function __construct(
        public string $key,
        public RecordLayoutRegion $region,
        public string $component,
        public int $order = 0,
        public array $resources = ['*'],
        public ?string $ability = null,
        public bool $visible = true,
        public ?string $preferenceKey = null,
        public array $eagerLoad = [],
    ) {
        if (preg_match('/\A[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?\z/D', $key) !== 1) {
            throw new InvalidArgumentException("Record layout panel key [{$key}] is invalid.");
        }

        if ($resources === [] || count($resources) > RecordLayoutRegistry::MAX_RESOURCES_PER_PANEL) {
            throw new InvalidArgumentException("Record layout panel [{$key}] must declare a bounded resource list.");
        }

        foreach ($resources as $resource) {
            if (! is_string($resource) || trim($resource) === '' || str_contains($resource, "\0")) {
                throw new InvalidArgumentException("Record layout panel [{$key}] contains an invalid resource.");
            }
        }

        if ($ability !== null && (trim($ability) === '' || str_contains($ability, "\0"))) {
            throw new InvalidArgumentException("Record layout panel [{$key}] contains an invalid ability.");
        }

        if ($preferenceKey !== null
            && preg_match('/\A[a-z][a-z0-9_.-]*\z/D', $preferenceKey) !== 1) {
            throw new InvalidArgumentException("Record layout panel [{$key}] contains an invalid preference key.");
        }

        if (count($eagerLoad) > RecordLayoutRegistry::MAX_RELATIONSHIPS_PER_PANEL) {
            throw new InvalidArgumentException("Record layout panel [{$key}] declares too many eager-loaded relationships.");
        }

        foreach ($eagerLoad as $relationship) {
            if (! is_string($relationship)
                || preg_match('/\A[A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_][A-Za-z0-9_]*)*\z/D', $relationship) !== 1) {
                throw new InvalidArgumentException("Record layout panel [{$key}] contains an invalid relationship.");
            }
        }
    }
}
