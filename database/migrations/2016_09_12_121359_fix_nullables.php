<?php
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
