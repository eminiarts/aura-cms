<?php

namespace Aura\Base\Contracts;

/**
 * Marker contract for Livewire components that may be mounted by an Aura field.
 *
 * Components must also use AuthorizesEmbeddedComponent. The field resolver
 * verifies both requirements before a secure alias can cross the embedded
 * boundary. Legacy edit-only aliases without either marker continue to use
 * Aura's historical model/field mount contract.
 */
interface EmbeddedLivewireComponent {}
