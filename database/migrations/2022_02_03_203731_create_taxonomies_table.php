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
        Schema::dropIfExists('taxonomies');
        Schema::dropIfExists('terms');
        Schema::dropIfExists('taxonomy_relations');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('taxonomy_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taxonomy_id');
            $table->string('key');
            $table->longText('value');
        });

        Schema::create('taxonomies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('taxonomy');
            $table->longtext('description');
            $table->foreignId('parent');
            $table->bigInteger('count');
            $table->foreignId('team_id')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('taxonomy_relations', function (Blueprint $table) {
            $table->morphs('relatable');
            $table->foreignId('taxonomy_id');
            $table->integer('order');
        });
    }
};
