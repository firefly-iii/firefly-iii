<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class ChangesForV540
 * @codeCoverageIgnore
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
            'oauth_clients', static function (Blueprint $table) {
            $table->dropColumn('provider');
        }
        );

        Schema::table(
            'accounts', static function (Blueprint $table) {
            $table->dropColumn('order');
        }
        );

        Schema::table(
            'bills', static function (Blueprint $table) {
            $table->dropColumn('end_date');
            $table->dropColumn('extension_date');
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
        Schema::table(
            'oauth_clients', static function (Blueprint $table) {
            $table->string('provider')->nullable();
        }
        );
        Schema::table(
            'bills', static function (Blueprint $table) {
            $table->date('end_date')->nullable()->after('date');
            $table->date('extension_date')->nullable()->after('end_date');
        }
        );


        // make column nullable:
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->string('secret', 100)->nullable()->change();
        });
    }
}
