<?php

namespace Eminiarts\Aura\Widgets;

class BaseWidget extends Widget
{
    use Concerns\CanPoll;

    protected ?array $cachedCards = null;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'widgets.base-widget';

    protected function getCachedCards(): array
    {
        return $this->cachedCards ??= $this->getCards();
    }

    protected function getCards(): array
    {
        return [];
    }

    protected function getColumns(): int
    {
        return match ($count = count($this->getCachedCards())) {
            5, 6, 9, 11 => 3,
            7, 8, 10, 12 => 4,
            default => $count,
        };
    }
}