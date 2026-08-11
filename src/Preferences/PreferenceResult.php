<?php

namespace Aura\Base\Preferences;

final readonly class PreferenceResult
{
    public function __construct(
        public mixed $value,
        public ?PreferenceScope $scope,
        public bool $resourceSpecific,
        public bool $isDefault = false,
        public bool $isLegacy = false,
    ) {}
}
