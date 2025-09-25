<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('period_statistics', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->integer('primary_statable_id', false, true)->nullable();
            $table->string('primary_statable_type', 255)->nullable();

            $table->integer('secondary_statable_id', false, true)->nullable();
            $table->string('secondary_statable_type', 255)->nullable();

            $table->integer('tertiary_statable_id', false, true)->nullable();
            $table->string('tertiary_statable_type', 255)->nullable();

            $table->integer('transaction_currency_id', false, true);
            $table->foreign('transaction_currency_id')->references('id')->on('transaction_currencies')->onDelete('cascade');

            $table->dateTime('start')->nullable();
            $table->string('start_tz', 50)->nullable();
            $table->dateTime('end')->nullable();
            $table->string('end_tz', 50)->nullable();
            $table->string('type',255);
            $table->integer('count', false, true)->default(0);
            $table->decimal('amount', 32, 12);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('period_statistics');
    }
};
