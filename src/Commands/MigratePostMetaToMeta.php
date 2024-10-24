<?php

namespace Aura\Base\Commands;

use Aura\Base\Resources\Post;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigratePostMetaToMeta extends Command
{
    protected $description = 'Migrate post_meta to meta';

    protected $signature = 'aura:migrate-post-meta-to-meta';

    public function handle()
    {
        $this->info('Starting migration of post_meta, team_meta, and user_meta to meta table...');

        // Migrate post_meta
        $postMeta = DB::table('post_meta')->get();
        foreach ($postMeta as $meta) {
            DB::table('meta')->insert([
                'key' => $meta->key,
                'value' => $meta->value,
                'metable_id' => $meta->post_id,
                'metable_type' => Post::class,
            ]);
        }
        $this->info('Migrated post_meta to meta table.');

        // Migrate team_meta
        $teamMeta = DB::table('team_meta')->get();
        foreach ($teamMeta as $meta) {
            DB::table('meta')->insert([
                'key' => $meta->key,
                'value' => $meta->value,
                'metable_id' => $meta->team_id,
                'metable_type' => Team::class,
            ]);
        }
        $this->info('Migrated team_meta to meta table.');

        // Migrate user_meta
        $userMeta = DB::table('user_meta')->get();
        foreach ($userMeta as $meta) {
            DB::table('meta')->insert([
                'key' => $meta->key,
                'value' => $meta->value,
                'metable_id' => $meta->user_id,
                'metable_type' => User::class,
            ]);
        }
        $this->info('Migrated user_meta to meta table.');

        $this->info('Migration completed successfully.');
    }
}
