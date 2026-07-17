<?php

namespace Aura\Base\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class DatabaseToResources extends Command
{
    protected $description = 'Create resources based on existing database tables';

    protected $signature = 'aura:database-to-resources';

    public function handle()
    {
        $tables = $this->getAllTables();

        foreach ($tables as $table) {
            if ($this->isSystemTable($table)) {
                continue;
            }

            $this->transformTable($table);
        }

        $this->info('Resources generated successfully');

        return self::SUCCESS;
    }

    protected function getAllTables(): array
    {
        return Schema::getTableListing(schemaQualified: false);
    }

    protected function isSystemTable(string $table): bool
    {
        return in_array($table, ['migrations', 'failed_jobs', 'password_resets', 'sessions'], true);
    }

    protected function transformTable(string $table): int
    {
        return $this->call('aura:transform-table-to-resource', ['table' => $table]);
    }
}
