<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    private const string QUERY_ERROR = 'Could not execute query (table "%s", field "%s"): %s';
    private const string EXPL        = 'If the index already exists (see error), or if MySQL can\'t do it, this is not an problem. Otherwise, please open a GitHub discussion.';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // add missing indices
        $set = [
            'account_meta'                 => ['account_id'],
            'accounts'                     => ['user_id', 'user_group_id', 'account_type_id'],
            'budgets'                      => ['user_id', 'user_group_id'],
            'journal_meta'                 => ['transaction_journal_id', 'data', 'name'],
            'category_transaction_journal' => ['transaction_journal_id'],
            'categories'                   => ['user_id', 'user_group_id'],
            'transaction_groups'           => ['user_id', 'user_group_id'],
            'transaction_journals'         => ['user_id', 'user_group_id', 'date', 'transaction_group_id', 'transaction_type_id', 'transaction_currency_id', 'bill_id'],
            'transactions'                 => ['account_id', 'transaction_journal_id', 'transaction_currency_id', 'foreign_currency_id'],
        ];

        foreach ($set as $table => $fields) {
            foreach ($fields as $field) {
                try {
                    Schema::table(
                        $table,
                        static function (Blueprint $blueprint) use ($field): void {
                            $blueprint->index($field);
                        }
                    );
                } catch (QueryException $e) {
                    app('log')->error(sprintf(self::QUERY_ERROR, $table, $field, $e->getMessage()));
                    app('log')->error(self::EXPL);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
