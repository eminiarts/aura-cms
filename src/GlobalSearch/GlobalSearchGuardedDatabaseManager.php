<?php

namespace Aura\Base\GlobalSearch;

use Aura\Base\Exceptions\GlobalSearchExecutionFailed;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;

final class GlobalSearchGuardedDatabaseManager extends DatabaseManager
{
    public function __construct(
        DatabaseManager $database,
        private readonly GlobalSearchQueryGuard $queryGuard,
    ) {
        $guardedFactory = new GlobalSearchGuardedConnectionFactory($database->factory, $queryGuard);
        $database->factory = $guardedFactory;
        $database->extensions = $this->guardedExtensions($database->extensions);

        parent::__construct($database->app, $guardedFactory);

        $this->connections = $database->connections;
        $this->dynamicConnectionConfigurations = $database->dynamicConnectionConfigurations;
        $this->extensions = $database->extensions;

        foreach ($this->connections as $connection) {
            $connection->setReconnector($this->reconnector);
            $this->queryGuard->guard($connection);
        }
    }

    protected function configure(Connection $connection, $type)
    {
        $connection = parent::configure($connection, $type);
        $this->queryGuard->guard($connection);

        return $connection;
    }

    /**
     * @param  array<string, callable>  $extensions
     * @return array<string, callable>
     */
    private function guardedExtensions(array $extensions): array
    {
        foreach ($extensions as $name => $extension) {
            $extensions[$name] = function (array $configuration, string $connectionName) use ($extension): Connection {
                $connection = $extension($configuration, $connectionName);

                if (! $connection instanceof Connection) {
                    throw new GlobalSearchExecutionFailed('Laravel created an unsupported global search connection.');
                }

                $this->queryGuard->guard($connection);

                return $connection;
            };
        }

        return $extensions;
    }
}
