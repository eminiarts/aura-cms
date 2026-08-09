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
        $authorizer = app(EmbeddedComponentAuthorizer::class);
        $context = $authorizer->authorize($this->component, fresh: true);

        return static function () use ($authorizer, $context): void {
            $authorizer->finishAction($context);
        };
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
}
