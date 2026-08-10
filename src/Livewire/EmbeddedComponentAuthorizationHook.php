<?php

namespace Aura\Base\Livewire;

use Aura\Base\Services\EmbeddedComponentAuthorizer;
use Aura\Base\Services\EmbeddedComponentContextCodec;
use Livewire\ComponentHook;

use function Livewire\on;

final class EmbeddedComponentAuthorizationHook extends ComponentHook
{
    public function call(): callable
    {
        return $this->authorizeOperation();
    }

    public static function provide(): void
    {
        on('request', function (array $requestPayload): void {
            app(EmbeddedComponentContextCodec::class)->primeLivewireRequest($requestPayload);
        });
    }

    public function skip(): bool
    {
        return ! app(EmbeddedComponentAuthorizer::class)->supports($this->component);
    }

    public function update(): callable
    {
        $authorizer = app(EmbeddedComponentAuthorizer::class);
        $component = $this->component;
        $context = $authorizer->authorize($component, fresh: true);

        return static function () use ($authorizer, $component, $context): void {
            $authorizer->finishAction($context);

            $updatedContext = $authorizer->authorize($component, fresh: true);
            $authorizer->finishAction($updatedContext);
        };
    }

    private function authorizeOperation(): callable
    {
        $authorizer = app(EmbeddedComponentAuthorizer::class);
        $context = $authorizer->authorize($this->component, fresh: true);

        return static function () use ($authorizer, $context): void {
            $authorizer->finishAction($context);
        };
    }
}
