<?php

namespace Aura\Base\Policies;

use Illuminate\Auth\Access\Gate;

final class ResourcePolicyGate extends Gate
{
    /**
     * Normalize Aura's class-string subject only for the inherited Aura
     * policy method. Global and policy before callbacks receive Laravel's
     * original arguments and retain their native ordering semantics.
     *
     * @param  mixed  $policy
     * @param  mixed  $method
     * @param  mixed  $user
     * @param  array<int, mixed>  $arguments
     */
    protected function callPolicyMethod($policy, $method, $user, array $arguments): mixed
    {
        $subject = $arguments[0] ?? null;

        if (
            is_string($method)
            && is_string($subject)
            && ($policy instanceof ResourcePolicy || $policy instanceof TeamPolicy)
            && ResourcePolicySubject::supports($method, $subject)
            && ResourcePolicySubject::usesAuraSubject($policy, $method)
        ) {
            $arguments[0] = ResourcePolicySubject::normalize($subject);
        }

        return parent::callPolicyMethod($policy, $method, $user, $arguments);
    }
}
