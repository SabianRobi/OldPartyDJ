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
        Schema::create('party_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->onDelete('cascade')->onUpdate('cascade')->constrained();
            $table->foreignId('party_id')->onDelete('cascade')->onUpdate('cascade')->constrained();
            $table->enum('role', ['creator', 'participant']);
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
        Schema::dropIfExists('party_participants');
    }
};
