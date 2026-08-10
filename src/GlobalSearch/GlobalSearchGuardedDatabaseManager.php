<?php

namespace Aura\Base\GlobalSearch;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;

final class GlobalSearchGuardedDatabaseManager extends DatabaseManager
{
    public function __construct(
        DatabaseManager $database,
        private readonly GlobalSearchQueryGuard $queryGuard,
    ) {
        parent::__construct($database->app, $database->factory);

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
}
