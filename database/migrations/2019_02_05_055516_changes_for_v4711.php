<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangesForV4711 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        //
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table(
            'transaction_journals', function (Blueprint $table) {
            $table->dateTimeTz('date')->change();
        }
        );

        Schema::table('preferences', function (Blueprint $table) {
            $table->text('data')->nullable()->change();
        }
        );
    }
}
