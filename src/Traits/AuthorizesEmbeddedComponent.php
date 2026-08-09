<?php

namespace Aura\Base\Traits;

use Aura\Base\Services\AuthorizedEmbeddedComponentContext;
use Aura\Base\Services\EmbeddedComponentContextCodec;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

trait AuthorizesEmbeddedComponent
{
    /** @var array<string, mixed> */
    #[Locked]
    public array $auraEmbeddedContext = [];

    private ?AuthorizedEmbeddedComponentContext $authorizedEmbeddedContext = null;

    final public function bootAuthorizesEmbeddedComponent(): void
    {
        abort_unless(
            ($this->auraEmbeddedContext['component_alias'] ?? null) === $this->getName(),
            403,
        );

        $this->authorizedEmbeddedContext = app(EmbeddedComponentContextCodec::class)
            ->authorize($this->auraEmbeddedContext);
    }

    /**
     * Livewire invokes this trait-prefixed lifecycle hook before every action,
     * including each action inside a single batched update request.
     *
     * @param  array<int|string, mixed>  $params
     * @param  callable(mixed=): void  $returnEarly
     * @param  array<string, mixed>  $metadata
     */
    final public function callAuthorizesEmbeddedComponent(
        string $methodName,
        array $params,
        callable $returnEarly,
        array $metadata,
    ): void {
        $this->authorizedEmbeddedContext = app(EmbeddedComponentContextCodec::class)
            ->authorize($this->auraEmbeddedContext, fresh: true);
    }

    final protected function embeddedContext(): AuthorizedEmbeddedComponentContext
    {
        abort_unless(
            $this->authorizedEmbeddedContext instanceof AuthorizedEmbeddedComponentContext,
            403,
        );

        return $this->authorizedEmbeddedContext;
    }

    final protected function embeddedResource(): Model
    {
        return $this->embeddedContext()->resource;
    }
}
