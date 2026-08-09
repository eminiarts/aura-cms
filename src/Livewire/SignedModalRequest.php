<?php

namespace Aura\Base\Livewire;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Facades\Auth;

final class SignedModalRequest
{
    private const PREFIX = 'aura-modal:';

    public function __construct(private readonly Encrypter $encrypter) {}

    /**
     * @param  array<string, mixed>  $arguments
     * @param  array<string, mixed>  $modalAttributes
     */
    public function issue(string $component, array $arguments, array $modalAttributes = []): string
    {
        if (preg_match('/\A[A-Za-z0-9][A-Za-z0-9._:-]*\z/', $component) !== 1) {
            abort(422, 'The declared modal component is invalid.');
        }

        return self::PREFIX.$this->encrypter->encrypt([
            'arguments' => $arguments,
            'component' => $component,
            'expires_at' => now()->addMinutes(2)->getTimestamp(),
            'modal_attributes' => $modalAttributes,
            'team_id' => data_get(Auth::user(), 'current_team_id'),
            'user_id' => Auth::id(),
        ]);
    }

    /**
     * @return array{arguments: array<string, mixed>, component: string, modalAttributes: array<string, mixed>}
     */
    public function resolve(string $request): array
    {
        if (! str_starts_with($request, self::PREFIX)) {
            abort(422, 'The modal request is invalid.');
        }

        try {
            $payload = $this->encrypter->decrypt(substr($request, strlen(self::PREFIX)));
        } catch (DecryptException) {
            abort(422, 'The modal request is invalid.');
        }

        if (
            ! is_array($payload)
            || array_keys($payload) !== [
                'arguments',
                'component',
                'expires_at',
                'modal_attributes',
                'team_id',
                'user_id',
            ]
            || ! is_array($payload['arguments'])
            || ! is_string($payload['component'])
            || ! is_int($payload['expires_at'])
            || ! is_array($payload['modal_attributes'])
            || $payload['expires_at'] < now()->getTimestamp()
            || (string) $payload['user_id'] !== (string) Auth::id()
            || (string) $payload['team_id'] !== (string) data_get(Auth::user(), 'current_team_id')
        ) {
            abort(422, 'The modal request is invalid.');
        }

        return [
            'arguments' => $payload['arguments'],
            'component' => $payload['component'],
            'modalAttributes' => $payload['modal_attributes'],
        ];
    }

    public function supports(string $request): bool
    {
        return str_starts_with($request, self::PREFIX);
    }
}
