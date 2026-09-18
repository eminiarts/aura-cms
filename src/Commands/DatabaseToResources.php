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

    /**
     * Tables owned by Laravel or by Aura itself. Aura already ships resources for
     * its own tables, so generating resources for them would produce duplicates.
     */
    protected function isSystemTable(string $table): bool
    {
        return in_array($table, [
            // Laravel
            'cache',
            'cache_locks',
            'failed_jobs',
            'job_batches',
            'jobs',
            'migrations',
            'notifications',
            'password_reset_tokens',
            'password_resets',
            'personal_access_tokens',
            'sessions',
            // Aura (database/migrations/create_aura_tables.php.stub)
            'aura_migration_ownership',
            'meta',
            'options',
            'permissions',
            'post_relations',
            'posts',
            'roles',
            'teams',
            'user_role',
            'users',
        ], true);
    }

    protected function transformTable(string $table): int
    {
        return $this->call('aura:transform-table-to-resource', ['table' => $table]);
    }
}
