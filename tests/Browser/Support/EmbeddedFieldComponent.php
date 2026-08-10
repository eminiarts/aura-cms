<?php

namespace Aura\Base\Tests\Browser\Support;

use Aura\Base\Contracts\EmbeddedLivewireComponent;
use Aura\Base\Traits\AuthorizesEmbeddedComponent;
use Livewire\Component;

class EmbeddedFieldComponent extends Component implements EmbeddedLivewireComponent
{
    use AuthorizesEmbeddedComponent;

    public function render(): string
    {
        $context = $this->embeddedContext();

        return sprintf(
            '<div data-browser-embedded-field>%s:%s</div>',
            e($context->surface->value),
            e((string) ($context->resource->getKey() ?? 'new')),
        );
    }
}
