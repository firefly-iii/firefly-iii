<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table): void {
            $table->string('flag_src')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table): void {
            $table->dropColumn('flag_src');
        });
    }
};
