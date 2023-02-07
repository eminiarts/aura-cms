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
        Schema::dropIfExists('flows');
        Schema::dropIfExists('flow_operations');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('flows', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('trigger')->nullable();
            $table->json('options')->nullable();
            $table->json('data')->nullable();
            $table->string('status')->nullable()->default('active');
            $table->foreignId('operation_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->nullable();
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade')->nullable();
        });

        Schema::create('flow_operations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('key')->nullable();
            $table->unsignedBigInteger('flow_id');
            $table->unsignedBigInteger('resolve_id')->nullable();
            $table->unsignedBigInteger('reject_id')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->json('options')->nullable();
            $table->timestamps();
            $table->foreign('flow_id')->references('id')->on('flows')->onDelete('cascade');
            $table->foreign('resolve_id')->references('id')->on('flow_operations')->onDelete('set null');
            $table->foreign('reject_id')->references('id')->on('flow_operations')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->nullable();
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade')->nullable();
        });

        // flows: operation_id is not yet created, so we need to constrain it manually
        // Schema::table('flows', function (Blueprint $table) {
        //     $table->foreign('operation_id')->references('id')->on('flow_operations')->onDelete('set null');
        // });
    }
};
