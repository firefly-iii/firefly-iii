<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private array $tables = [
        'accounts' => ['native_virtual_balance'], // works.
        'account_piggy_bank' => ['native_current_amount'], // works
        'auto_budgets' => ['native_amount'], // works
        'available_budgets' => ['native_amount'], // works
        'bills' => ['native_amount_min', 'native_amount_max'], // works
        'budget_limits' => ['native_amount'], // works
        'piggy_bank_events' => ['native_amount'], // works
        'piggy_banks' => ['native_target_amount'], // works
        'transactions' => ['native_amount', 'native_foreign_amount'], // works

        // TODO native currency changes, reset everything.
        // TODO button to recalculate all native amounts on selected pages?
        // TODO check if you use the correct date for the excange rate

    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $table => $fields) {
            foreach ($fields as $field) {
                Schema::table($table, static function (Blueprint $table) use ($field): void {
                    // add amount column
                    $table->decimal($field, 32, 12)->nullable();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $table => $fields) {
            foreach ($fields as $field) {
                Schema::table($table, static function (Blueprint $table) use ($field): void {
                    // add amount column
                    $table->dropColumn($field);
                });
            }
        }
    }
};
