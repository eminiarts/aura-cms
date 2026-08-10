<?php

namespace Aura\Base\GlobalSearch;

use Aura\Base\Exceptions\GlobalSearchExecutionFailed;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;

final class GlobalSearchGuardedConnectionFactory extends ConnectionFactory
{
    public function __construct(
        private readonly ConnectionFactory $factory,
        private readonly GlobalSearchQueryGuard $queryGuard,
    ) {
        parent::__construct(app());
    }

    public function make(array $config, $name = null)
    {
        $connection = $this->factory->make($config, $name);

        if (! $connection instanceof Connection) {
            throw new GlobalSearchExecutionFailed('Laravel created an unsupported global search connection.');
        }

        $this->queryGuard->guard($connection);

        return $connection;
    }
}
