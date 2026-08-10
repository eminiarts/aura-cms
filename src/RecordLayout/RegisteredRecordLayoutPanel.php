<?php

namespace Aura\Base\RecordLayout;

final readonly class RegisteredRecordLayoutPanel
{
    public function __construct(
        public string $source,
        public RecordLayoutPanel $panel,
    ) {}

    public function identity(): string
    {
        return $this->source.':'.$this->panel->key;
    }

    public function transport(): string
    {
        return 'aura-record-panel-'.hash('sha256', $this->identity());
    }
}
