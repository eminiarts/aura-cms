<?php

namespace Aura\Base\Preferences;

use Aura\Base\Resources\Option;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use InvalidArgumentException;

final readonly class PreferenceContext
{
    public function __construct(
        public string $application,
        public ?User $user = null,
        public ?Team $team = null,
        public ?string $resource = null,
    ) {
        foreach (['application' => $application, 'resource' => $resource] as $name => $value) {
            if ($value !== null && (trim($value) === '' || str_contains($value, "\0"))) {
                throw new InvalidArgumentException("Preference context {$name} is invalid.");
            }
        }

        if ($team !== null && Option::isEveryoneTeamId($team->getKey())) {
            throw new InvalidArgumentException('The reserved everyone preference owner cannot be used as a team context.');
        }
    }

    public function forApplication(): self
    {
        return new self($this->application, $this->user, $this->team);
    }
}
