<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('account_balances')) {
            Schema::create('account_balances', function (Blueprint $table): void {
                $table->id();
                $table->timestamps();
                $table->string('title', 100)->nullable();
                $table->unsignedBigInteger('account_id'); // Make sure to use unsigned
                $table->unsignedBigInteger('transaction_currency_id'); // Make sure to use unsigned
                $table->date('date')->nullable();
                $table->unsignedBigInteger('transaction_journal_id')->nullable(); // Make sure to use unsigned

                // Adjust balance precision based on your requirements
                $table->decimal('balance', 15, 2);

                // Foreign key constraints
                $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
                $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('cascade')->nullable(); // Ensure reference allows null
                $table->foreign('transaction_currency_id')->references('id')->on('transaction_currencies')->onDelete('cascade');

                // Unique constraint
                $table->unique(['account_id', 'transaction_currency_id', 'transaction_journal_id', 'date', 'title'], 'unique_account_currency');
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
