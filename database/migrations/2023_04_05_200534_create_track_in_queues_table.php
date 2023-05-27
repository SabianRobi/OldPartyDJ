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
        Schema::create('track_in_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained('parties', 'id')->onDelete('cascade');
            $table->foreignId('addedBy')->constrained('users', 'id')->onDelete('cascade');
            $table->enum('platform', ['Spotify']);
            $table->string('track_uri');
            $table->integer('score')->default(0);
            $table->boolean('currently_playing')->default(false);
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
        Schema::dropIfExists('track_in_queues');
    }
};
