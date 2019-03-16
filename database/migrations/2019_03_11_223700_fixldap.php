<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class ChangesForV4713
 */
class ChangesForV4713 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table(
            'users', function (Blueprint $table) {
            $table->dropColumn(['objectguid']);
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
        /**
         * ADLdap2 appears to require the ability to store an objectguid for LDAP users
         * now. To support this, we add the column.
         */
        Schema::table(
            'users', function (Blueprint $table) {
            $table->uuid('objectguid')->nullable()->after('id');
        }
        );
    }
}
