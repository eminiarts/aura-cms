<?php

namespace Aura\Base\RecordLayout;

final readonly class RecordLayout
{
    /**
     * @param  array<string, list<RegisteredRecordLayoutPanel>>  $regions
     */
    public function __construct(private array $regions) {}

    public static function empty(): self
    {
        return new self([]);
    }

    public function hasPanels(): bool
    {
        return $this->regions !== [];
    }

    /** @return list<RegisteredRecordLayoutPanel> */
    public function panels(RecordLayoutRegion $region): array
    {
        return $this->regions[$region->value] ?? [];
    }
}
