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
    public function up(): void
    {
        Schema::create('invited_users', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('user_id', false, true);
            $table->string('email', 255);
            $table->string('invite_code', 64);
            $table->dateTime('expires');
            $table->boolean('redeemed');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('invited_users');
    }
};
