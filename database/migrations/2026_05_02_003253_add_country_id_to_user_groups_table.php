<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_groups', static function (Blueprint $table): void {
            $table->foreignId('country_id')
                ->nullable()
                ->after('title')
                ->constrained('countries')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_groups', static function (Blueprint $table): void {
            $table->dropConstrainedForeignId('country_id');
        });
    }
};
