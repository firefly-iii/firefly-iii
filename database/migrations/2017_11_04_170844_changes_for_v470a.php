<?php
declare(strict_types=1);


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangesForV470a extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     * @return void
     */
    public function up()
    {
        Schema::table(
            'transactions', function (Blueprint $table) {
            $table->boolean('reconciled')->after('deleted_at')->default(0);
        }
        );
    }
}
