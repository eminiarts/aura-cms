<?php

namespace Eminiarts\Aura\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DatabaseToResources extends Command
{
    protected $signature = 'aura:database-to-resources';

    protected $description = 'Create resources based on existing database tables';

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
