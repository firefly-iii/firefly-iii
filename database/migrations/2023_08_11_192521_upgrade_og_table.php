<?php

declare(strict_types=1);

use Doctrine\DBAL\Schema\Exception\ColumnDoesNotExist;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 *
 */
return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Schema::table(
                'object_groups',
                function (Blueprint $table) {
                    if (!Schema::hasColumn('object_groups', 'user_group_id')) {
                        $table->bigInteger('user_group_id', false, true)->nullable()->after('user_id');
                        $table->foreign('user_group_id', sprintf('%s_to_ugi', 'object_groups'))->references('id')->on('user_groups')->onDelete(
                            'set null'
                        )->onUpdate('cascade');
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
    public function down(): void
    {
        try {
            Schema::table(
                'object_groups',
                function (Blueprint $table) {
                    if ('sqlite' !== config('database.default')) {
                        $table->dropForeign(sprintf('%s_to_ugi', 'object_groups'));
                    }
                    if (Schema::hasColumn('object_groups', 'user_group_id')) {
                        $table->dropColumn('user_group_id');
                    }
                }
            );
        } catch (QueryException | ColumnDoesNotExist $e) {
            app('log')->error(sprintf('Could not execute query: %s', $e->getMessage()));
            app('log')->error('If the column or index already exists (see error), this is not an problem. Otherwise, please open a GitHub discussion.');
        }
    }
};
