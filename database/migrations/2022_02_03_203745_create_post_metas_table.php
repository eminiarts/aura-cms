<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('meta');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('post_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id');
            $table->string('key')->nullable();
            $table->longText('value')->nullable();
        });
    }
};
