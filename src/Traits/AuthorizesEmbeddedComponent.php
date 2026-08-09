<?php

namespace Aura\Base\Traits;

use Aura\Base\Services\AuthorizedEmbeddedComponentContext;
use Aura\Base\Services\EmbeddedComponentAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

trait AuthorizesEmbeddedComponent
{
    /** @var array<string, mixed> */
    #[Locked]
    public array $auraEmbeddedContext = [];

    final public function bootAuthorizesEmbeddedComponent(): void
    {
        app(EmbeddedComponentAuthorizer::class)->authorize($this);
    }

    final protected function embeddedContext(): AuthorizedEmbeddedComponentContext
    {
        return app(EmbeddedComponentAuthorizer::class)->context($this);
    }

    final protected function embeddedResource(): Model
    {
        return $this->embeddedContext()->resource;
    }
}
