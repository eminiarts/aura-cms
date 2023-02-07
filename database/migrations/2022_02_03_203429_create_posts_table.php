<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('posts');
        Schema::dropIfExists('post_meta');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->text('title')->nullable();
            $table->longText('content')->nullable();
            $table->string('type', 20);
            $table->string('status', 20)->default('publish');
            $table->string('slug')->index();
            $table->foreignId('user_id')->nullable()->index();
            $table->foreignId('parent_id')->nullable()->index();
            $table->integer('order')->nullable();
            $table->foreignId('team_id')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'slug', 'type', 'status', 'created_at', 'id']);
        });


        Schema::create('post_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id');
            $table->string('key')->nullable();
            $table->longText('value')->nullable();
        });
    }
};
