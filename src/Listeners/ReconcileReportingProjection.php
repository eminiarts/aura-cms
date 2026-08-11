<?php

namespace Aura\Base\Listeners;

use Aura\Base\Events\ResourceEvent;
use Aura\Base\Reporting\CurrentStateProjectionReconciler;

final class ReconcileReportingProjection
{
    public function __construct(private readonly CurrentStateProjectionReconciler $reconciler) {}

    public function handle(ResourceEvent $event): void
    {
        if (! config('aura.reporting.projection.enabled', false)) {
            return;
        }

        $this->reconciler->reconcile($event);
    }
}
