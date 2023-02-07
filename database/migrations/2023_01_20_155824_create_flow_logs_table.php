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
        //
        Schema::dropIfExists('flow_logs');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('flow_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained()->cascadeOnDelete();
            $table->string('status')->nullable();

            // startet_at and finished_at are nullable because the Flow might not be finished yet
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->json('options')->nullable();

            $table->timestamps();

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->nullable();
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade')->nullable();
        });
    }
};
