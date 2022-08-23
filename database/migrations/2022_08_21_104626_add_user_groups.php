<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 *
 */
return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table(
            'currency_exchange_rates', function (Blueprint $table) {

            if (!Schema::hasColumn('currency_exchange_rates', 'user_group_id')) {
                $table->bigInteger('user_group_id', false, true)->nullable()->after('user_id');
                $table->foreign('user_group_id', 'cer_to_ugi')->references('id')->on('user_groups')->onDelete('set null')->onUpdate('cascade');
            }
        }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table(
            'currency_exchange_rates', function (Blueprint $table) {

            $table->dropForeign('cer_to_ugi');
            if (Schema::hasColumn('currency_exchange_rates', 'user_group_id')) {
                $table->dropColumn('user_group_id');
            }
        }
        );
    }
};
