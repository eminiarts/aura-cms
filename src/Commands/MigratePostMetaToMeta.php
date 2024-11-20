<?php

namespace Aura\Base\Commands;

use Aura\Base\Resources\Post;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigratePostMetaToMeta extends Command
{
    protected $description = 'Migrate post_meta to meta';

    protected $signature = 'aura:migrate-post-meta-to-meta';

    public function handle()
    {
        $this->info('Starting migration of post_meta, team_meta, and user_meta to meta table...');

        if (! Schema::hasTable('meta')) {
            Schema::create('meta', function (Blueprint $table) {
                $table->id();
                $table->morphs('metable');
                $table->string('key')->nullable()->index();
                $table->longText('value')->nullable();
                $table->index(['metable_type', 'metable_id', 'key']);
            });
        }

        // Migrate post_meta
        $postMeta = DB::table('post_meta')->get();
        foreach ($postMeta as $meta) {
            $post = DB::table('posts')->where('id', $meta->post_id)->first();
            $type = $post->type;
            $metableType = \Aura\Base\Facades\Aura::findResourceBySlug($type);

            // dd($metableType::class);

            DB::table('meta')->insert([
                'key' => $meta->key,
                'value' => $meta->value,
                'metable_id' => $meta->post_id,
                'metable_type' => $metableType::class,
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
