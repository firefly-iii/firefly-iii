<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Schema::table(
                'preferences',
                static function (Blueprint $table): void {
                    if (!Schema::hasColumn('preferences', 'user_group_id')) {
                        $table->bigInteger('user_group_id', false, true)->nullable()->after('user_id');
                        $table->foreign('user_group_id', 'preferences_to_ugi')->references('id')->on('user_groups')->onDelete('set null')->onUpdate('cascade');
                    }
                }
            );
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
            app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
