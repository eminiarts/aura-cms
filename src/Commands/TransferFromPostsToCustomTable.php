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
        $type = $resourceInstance->getType();

        info('Transferring data from posts to: ' . $resourceClass);

        // Fetch posts of the specific type along with their meta data
        $posts = DB::table('posts')->where('type', $type)->get();

        foreach ($posts as $post) {

            // Get all meta for this post
            $metas = DB::table('meta')
                ->where('metable_type', get_class($resourceInstance))
                ->where('metable_id', $post->id)
                ->get();

            // Prepare record data combining post and meta fields
            $newRecord = [
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ];

            foreach ($metas as $meta) {
                $newRecord[$meta->key] = $meta->value;
            }

            // Create new record using the resource
            app($resourceClass)->create($newRecord);
        }

        info('Data transfer completed.');
    }
}
