<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('account_balances')) {
            Schema::create('account_balances', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
                $table->string('title', 100)->nullable();
                $table->integer('account_id', false, true);
                $table->integer('transaction_currency_id', false, true);
                $table->decimal('balance', 32, 12);
                $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
                $table->foreign('transaction_currency_id')->references('id')->on('transaction_currencies')->onDelete('cascade');
                $table->unique(['account_id', 'transaction_currency_id', 'title'], 'unique_account_currency');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_balances');
    }
};
