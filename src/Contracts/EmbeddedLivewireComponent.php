<?php

namespace Aura\Base\Contracts;

/**
 * Marker contract for Livewire components that may be mounted by an Aura field.
 *
 * Components must also use AuthorizesEmbeddedComponent. The field resolver
 * verifies both requirements before an alias can cross the embedded boundary.
 */
interface EmbeddedLivewireComponent {}
