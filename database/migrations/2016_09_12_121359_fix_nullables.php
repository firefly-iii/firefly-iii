<?php
/**
 * 2016_09_12_121359_fix_nullables.php
 * Copyright (C) 2016 https://github.com/sandermulders
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class FixNullables
 */
class FixNullables extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down()
    {
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function up()
    {
        Schema::table(
            'rule_groups',
            function (Blueprint $table) {
                $table->text('description')->nullable()->change();
            }
        );

        Schema::table(
            'rules',
            function (Blueprint $table) {
                $table->text('description')->nullable()->change();
            }
        );
    }
}
