<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('spotify_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id');
            $table->foreignId('user_id');
            $table->string('platform');
            $table->string('track_uri');
            $table->integer('score');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('spotify_queues');
    }
};
