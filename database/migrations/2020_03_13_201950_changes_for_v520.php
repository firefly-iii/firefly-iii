<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV520.
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
        Schema::dropIfExists('telemetry');
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

                    $table->foreign('transaction_currency_id')->references('id')->on('transaction_currencies')->onDelete('cascade');
                    $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');
                }
            );
        }

        if (!Schema::hasTable('telemetry')) {
            Schema::create(
                'telemetry',
                static function (Blueprint $table) {
                    $table->increments('id');
                    $table->timestamps();
                    $table->dateTime('submitted')->nullable();
                    $table->integer('user_id', false, true)->nullable();
                    $table->string('installation_id', 50);
                    $table->string('type', 25);
                    $table->string('key', 50);
                    $table->text('value');

                    $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                }
            );
        }
    }
}
