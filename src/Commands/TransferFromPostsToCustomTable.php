<?php

namespace Aura\Base\Commands;

use ReflectionClass;
use Aura\Base\Facades\Aura;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use function Laravel\Prompts\info;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\error;
use function Laravel\Prompts\select;
use function Laravel\Prompts\confirm;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class TransferFromPostsToCustomTable extends Command
{
    protected $signature = 'aura:transfer-from-posts-to-custom-table {resource?}';
    protected $description = 'Transfer resources from posts and meta tables to custom tables';

    public function handle()
    {
        // Get resource class from argument or prompt user to select
        $resourceClass = $this->argument('resource');

        if ($resourceClass) {
            // Validate that the provided resource class exists
            if (!class_exists($resourceClass)) {
                error("Resource class '{$resourceClass}' does not exist.");
                return Command::FAILURE;
            }
        } else {
            // Step 1: Ask which resource to use if not provided
            $resources = Aura::getResources();
            $resourceOptions = [];

            foreach ($resources as $resourceClass) {
                $resourceInstance = new $resourceClass;
                $resourceName = $resourceInstance->name ?? class_basename($resourceClass);
                $resourceOptions[$resourceName] = $resourceClass;
            }

            $resourceName = select(
                'Which resource do you want to migrate?',
                array_keys($resourceOptions)
            );
            $resourceClass = $resourceOptions[$resourceName];
        }

        $this->transferData($resourceClass);
        info('Transfer process completed.');
    }


    protected function transferData($resourceClass)
    {
        $resourceInstance = new $resourceClass;
        $modelClass = $resourceInstance->model ?? $resourceInstance->getModel();
        $tableName = (new $modelClass)->getTable();
        $type = $resourceInstance->getType();

        info('Transferring data to new table: ' . $tableName);

        // Get the columns that exist in the target table
        $columns = Schema::getColumnListing($tableName);

        // Fetch posts of the specific type
        $posts = DB::table('posts')->where('type', $type)->get();

        foreach ($posts as $post) {
            $newRecord = [];
            
            // Only include fields that exist in the target table
            foreach ((array) $post as $key => $value) {
                if (in_array($key, $columns)) {
                    $newRecord[$key] = $value;
                }
            }
            
            unset($newRecord['id']); // Remove the id to let it auto-increment

            // Insert into the new custom table
            $newId = DB::table($tableName)->insertGetId($newRecord);

            // Transfer meta data
            $metas = DB::table('meta')
                ->where('metable_type', 'post')
                ->where('metable_id', $post->id)
                ->get();

            foreach ($metas as $meta) {
                // Adjust metable_type and metable_id to point to the new table and new record
                $metaData = (array) $meta;
                $metaData['metable_type'] = $modelClass;
                $metaData['metable_id'] = $newId;

                unset($metaData['id']); // Remove the id to let it auto-increment
                DB::table('meta')->insert($metaData);
            }
        }

        info('Data transfer completed.');
    }
}
