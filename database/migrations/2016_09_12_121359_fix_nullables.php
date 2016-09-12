<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FixNullables extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'rule_groups', function (Blueprint $table)
        {
            $table->text('description')->nullable()->change();
        }
        );

        Schema::table(
            'rules', function (Blueprint $table)
        {
            $table->text('description')->nullable()->change();
        }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
