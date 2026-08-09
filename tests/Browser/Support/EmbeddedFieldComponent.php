<?php

namespace Aura\Base\Tests\Browser\Support;

use Aura\Base\Contracts\EmbeddedLivewireComponent;
use Aura\Base\Traits\AuthorizesEmbeddedComponent;
use Livewire\Component;

class EmbeddedFieldComponent extends Component implements EmbeddedLivewireComponent
{
    use AuthorizesEmbeddedComponent;

    public string $context = '';

    public string $fieldSlug = '';

    public int|string|null $resourceId = null;

    public string $resourceType = '';

    public function render(): string
    {
        return <<<'HTML'
            <div data-browser-embedded-field>{{ $context }}:{{ $resourceId ?? 'new' }}</div>
        HTML;
    }
}
