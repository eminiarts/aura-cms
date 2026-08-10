<?php

namespace Aura\Base\Services;

use Aura\Base\Contracts\EmbeddedLivewireComponent;
use Aura\Base\Traits\AuthorizesEmbeddedComponent;
use Livewire\Component;
use WeakMap;

final class EmbeddedComponentAuthorizer
{
    /** @var WeakMap<Component, AuthorizedEmbeddedComponentContext> */
    private WeakMap $contexts;

    public function __construct(
        private readonly EmbeddedComponentContextCodec $codec,
        private readonly EmbeddedComponentContextStore $store,
    ) {
        $this->contexts = new WeakMap;
    }

    public function authorize(Component $component, bool $fresh = false): AuthorizedEmbeddedComponentContext
    {
        abort_unless($this->supports($component), 403);
        $embeddedContext = $component->all()['auraEmbeddedContext'] ?? null;

        abort_unless(is_array($embeddedContext), 403);
        abort_unless(
            ($embeddedContext['component_alias'] ?? null) === $component->getName(),
            403,
        );

        $context = $this->codec->authorize($embeddedContext, $fresh);
        $this->contexts[$component] = $context;

        return $context;
    }

    public function context(Component $component): AuthorizedEmbeddedComponentContext
    {
        $context = $this->contexts[$component] ?? null;

        abort_unless($context instanceof AuthorizedEmbeddedComponentContext, 403);

        return $context;
    }

    public function finishAction(AuthorizedEmbeddedComponentContext $context): void
    {
        $this->store->forgetCanonical($context->resource);
    }

    public function supports(Component $component): bool
    {
        return $component instanceof EmbeddedLivewireComponent
            && in_array(AuthorizesEmbeddedComponent::class, class_uses_recursive($component), true);
    }
}
