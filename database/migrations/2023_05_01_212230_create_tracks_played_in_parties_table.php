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
        Schema::create('tracks_played_in_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained('parties', 'id')->onDelete('cascade');
            $table->foreignId('added_by')->constrained('users', 'id')->onDelete('cascade');
            $table->enum('platform', ['Spotify', 'YouTube']);
            $table->string('track_uri');
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
        Schema::dropIfExists('tracks_played_in_parties');
    }
};
