<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class ChangesForV540
 */
class ChangesForV540 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table(
            'accounts', static function (Blueprint $table) {
            $table->dropColumn('order');
        }
        );
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table(
            'accounts', static function (Blueprint $table) {
            $table->integer('order', false, true)->default(0);
        }
        );
    }
}
