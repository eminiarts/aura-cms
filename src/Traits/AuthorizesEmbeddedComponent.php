<?php

namespace Aura\Base\Traits;

use Aura\Base\Services\EmbeddedComponentContextCodec;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

trait AuthorizesEmbeddedComponent
{
    /** @var array<string, mixed> */
    #[Locked]
    public array $auraEmbeddedContext = [];

    private ?Model $authorizedEmbeddedResource = null;

    final public function bootAuthorizesEmbeddedComponent(): void
    {
        abort_unless(
            ($this->auraEmbeddedContext['component_alias'] ?? null) === $this->getName(),
            403,
        );

        $this->authorizedEmbeddedResource = app(EmbeddedComponentContextCodec::class)
            ->authorize($this->auraEmbeddedContext);
    }

    final protected function embeddedResource(): Model
    {
        abort_unless($this->authorizedEmbeddedResource instanceof Model, 403);

        return $this->authorizedEmbeddedResource;
    }
}
