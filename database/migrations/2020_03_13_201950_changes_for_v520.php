<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV520
 */
class ChangesForV520 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_budgets');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (!Schema::hasTable('auto_budgets')) {
            Schema::create(
                'auto_budgets',
                static function (Blueprint $table) {
                    $table->increments('id');
                    $table->timestamps();
                    $table->softDeletes();
                    $table->integer('budget_id', false, true);
                    $table->integer('transaction_currency_id', false, true);
                    $table->tinyInteger('auto_budget_type', false, true)->default(1);
                    $table->decimal('amount', 22, 12);
                    $table->string('period', 50);


                    //$table->string('password', 60);
                    //$table->string('remember_token', 100)->nullable();
                    //$table->string('reset', 32)->nullable();
                    //$table->tinyInteger('blocked', false, true)->default('0');
                    //$table->string('blocked_code', 25)->nullable();

                    $table->foreign('transaction_currency_id')->references('id')->on('transaction_currencies')->onDelete('cascade');
                    $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');
                }
            );
        }
    }
}
