<?php

namespace Aura\Base\Services;

use Aura\Base\Resources\User;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvitationConnectionResolver
{
    public function resolve(Request $request, Connection $legacyCandidate): Connection
    {
        $connectionName = $request->query('invitation_connection');

        if ($connectionName === null) {
            $legacyConnection = DB::connection($this->legacyConnectionName());

            abort_unless(
                User::connectionCacheIdentity($legacyCandidate)
                    === User::connectionCacheIdentity($legacyConnection),
                404,
            );

            return $legacyConnection;
        }

        abort_unless(is_string($connectionName) && $connectionName !== '', 404);
        $this->ensureAllowed($connectionName);

        $connection = DB::connection($connectionName);
        $signedIdentity = $request->query('invitation_connection_identity');

        abort_unless(
            is_string($signedIdentity)
                && hash_equals(User::connectionCacheIdentity($connection), $signedIdentity),
            404,
        );

        return $connection;
    }

    /**
     * @return array{invitation_connection: string, invitation_connection_identity: string}
     */
    public function signedParameters(Model $invitation): array
    {
        $connection = $invitation->getConnection();
        $this->ensureAllowed($connection->getName());

        return [
            'invitation_connection' => $connection->getName(),
            'invitation_connection_identity' => User::connectionCacheIdentity($connection),
        ];
    }

    private function allowedConnectionNames(): array
    {
        $configured = config('aura.auth.invitation_connections', []);
        $configured = is_array($configured) ? $configured : [];

        /** @var Model $team */
        $team = app(config('aura.resources.team'));
        /** @var Model $invitation */
        $invitation = app(config('aura.resources.team-invitation'));

        return collect($configured)
            ->push($team->getConnectionName(), $invitation->getConnectionName(), $this->legacyConnectionName())
            ->filter(fn (mixed $name): bool => is_string($name) && $name !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function ensureAllowed(string $connectionName): void
    {
        abort_unless(in_array($connectionName, $this->allowedConnectionNames(), true), 404);
    }

    private function legacyConnectionName(): string
    {
        $configured = config('aura.auth.invitation_legacy_connection');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        /** @var Model $invitation */
        $invitation = app(config('aura.resources.team-invitation'));

        return $invitation->getConnection()->getName();
    }
}
