<?php

namespace Aura\Base\Commands;

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

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
        $this->output->progressStart(count($postMeta));
        foreach ($postMeta as $meta) {

            $post = DB::table('posts')->where('id', $meta->post_id)->first();

            // Skip if post doesn't exist anymore
            if (! $post) {
                $this->output->progressAdvance();

                continue;
            }

            $type = $post->type;
            $metableType = Aura::findResourceBySlug($type);

            // Skip if metableType is not found
            if (! $metableType) {
                $this->output->progressAdvance();

                continue;
            }

            DB::table('meta')->insert([
                'key' => $meta->key,
                'value' => $meta->value,
                'metable_id' => $meta->post_id,
                'metable_type' => $metableType::class,
            ]);
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
        $this->info('Migrated post_meta to meta table.');

        // Migrate team_meta
        $teamMeta = DB::table('team_meta')->get();
        $this->output->progressStart(count($teamMeta));
        foreach ($teamMeta as $meta) {
            $team = DB::table('teams')->where('id', $meta->team_id)->first();

            // Skip if team doesn't exist anymore
            if (! $team) {
                $this->output->progressAdvance();

                continue;
            }

            DB::table('meta')->insert([
                'key' => $meta->key,
                'value' => $meta->value,
                'metable_id' => $meta->team_id,
                'metable_type' => Team::class,
            ]);
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
        $this->info('Migrated team_meta to meta table.');

        // Migrate user_meta
        $userMeta = DB::table('user_meta')->get();
        $this->output->progressStart(count($userMeta));
        foreach ($userMeta as $meta) {
            $user = DB::table('users')->where('id', $meta->user_id)->first();

            // Skip if user doesn't exist anymore
            if (! $user) {
                $this->output->progressAdvance();

                continue;
            }

            DB::table('meta')->insert([
                'key' => $meta->key,
                'value' => $meta->value,
                'metable_id' => $meta->user_id,
                'metable_type' => User::class,
            ]);
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
        $this->info('Migrated user_meta to meta table.');

        $this->info('Migration completed successfully.');
    }
}
