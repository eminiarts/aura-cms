<?php

namespace Aura\Base\Tests\Fixtures;

class GlobalSearchProcessDefaultConnectionResource extends GlobalSearchProcessResource
{
    public static ?string $slug = 'process-search-default-connection';

    public static string $type = 'ProcessSearchDefaultConnection';

    protected $connection;
}
