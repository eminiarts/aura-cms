<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_meta');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('key')->nullable();
            $table->longText('value')->nullable();
            $table->foreignId('team_id')->index();
        });
    }
};
