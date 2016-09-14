<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class FixNullables
 */
class FixNullables extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'rule_groups', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        }
        );

        Schema::table(
            'rules', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        }
        );
    }
}
