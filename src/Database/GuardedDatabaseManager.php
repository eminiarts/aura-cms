<?php

namespace Aura\Base\Database;

use Aura\Base\Resource;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

use function Illuminate\Support\enum_value;

final class GuardedDatabaseManager extends DatabaseManager
{
    /**
     * @param  UnitEnum|string|null  $name
     */
    public function connection($name = null)
    {
        $resolvedName = enum_value($name) ?: $this->getDefaultConnection();

        Resource::assertGlobalWriteConnectionMayBeAcquired(
            $resolvedName,
            $this->connections[$resolvedName] ?? null,
        );

        return parent::connection($name);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function connectUsing(UnitEnum|string $name, array $config, bool $force = false)
    {
        Resource::assertGlobalWriteConnectionMayBeAcquired(enum_value($name), replacing: true);

        return parent::connectUsing($name, $config, $force);
    }

    /**
     * @param  UnitEnum|string|null  $name
     */
    public function reconnect($name = null)
    {
        $resolvedName = enum_value($name) ?: $this->getDefaultConnection();

        $this->disconnect($resolvedName);
        Resource::assertGlobalWriteConnectionMayBeAcquired($resolvedName, replacing: true);

        return parent::reconnect($name);
    }

    public static function wrap(DatabaseManager $manager): self
    {
        if ($manager instanceof self) {
            return $manager;
        }

        $guarded = new self($manager->app, $manager->factory);
        $guarded->connections = $manager->connections;
        $guarded->dynamicConnectionConfigurations = $manager->dynamicConnectionConfigurations;
        $guarded->extensions = $manager->extensions;

        foreach ($guarded->connections as $connection) {
            $connection->setReconnector($guarded->reconnector);
        }

        Model::setConnectionResolver($guarded);

        return $guarded;
    }
}
