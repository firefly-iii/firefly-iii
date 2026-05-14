<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('user_groups', 'country_id')) {
            Schema::table('user_groups', function (Blueprint $table) {
                $table->unsignedBigInteger('country_id')->nullable()->after('title');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('user_groups', 'country_id')) {
            Schema::table('user_groups', function (Blueprint $table) {
                $table->dropColumn('country_id');
            });
        }
    }
};
