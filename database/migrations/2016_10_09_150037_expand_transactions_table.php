<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ExpandTransactionsTable
 */
class ExpandTransactionsTable extends Migration
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
            'transactions',
            function (Blueprint $table) {
                $table->smallInteger('identifier', false, true)->default(0);
            }
        );
    }
}
