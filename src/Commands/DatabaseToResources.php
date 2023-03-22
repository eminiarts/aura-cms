<?php

namespace Eminiarts\Aura\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class DatabaseToResources extends Command
{
    protected $description = 'Create resources based on existing database tables';

    protected $signature = 'aura:database-to-resources';

    public function handle()
    {
        $tables = $this->getAllTables();

        // dd($tables);

        foreach ($tables as $table) {
            if (in_array($table, ['migrations', 'failed_jobs', 'password_resets', 'settions'])) {
                continue;
            }

            $this->call('aura:transform-table-to-resource', ['table' => $table]);
        }

        $this->info('Resources generated successfully');
    }

    private function getAllTables()
    {
        return Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();
    }
}
