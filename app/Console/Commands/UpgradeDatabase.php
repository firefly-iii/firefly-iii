<?php
/**
 * UpgradeDatabase.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Console\Commands;


use DB;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Log;

/**
 * Class UpgradeDatabase
 *
 * @package FireflyIII\Console\Commands
 */
class UpgradeDatabase extends Command
{

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will run various commands to update database records.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly:upgrade-database';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->setTransactionIdentifier();


    }

    /**
     * This is strangely complex, because the HAVING modifier is a no-no. And subqueries in Laravel are weird.
     */
    private function setTransactionIdentifier()
    {
        $subQuery = TransactionJournal
            ::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->whereNull('transaction_journals.deleted_at')
            ->whereNull('transactions.deleted_at')
            ->groupBy(['transaction_journals.id'])
            ->select(['transaction_journals.id', DB::raw('COUNT(transactions.id) AS t_count')]);

        $result     = DB::table(DB::raw('(' . $subQuery->toSql() . ') AS derived'))
                        ->mergeBindings($subQuery->getQuery())
                        ->where('t_count', '>', 2)
                        ->select(['id', 't_count']);
        $journalIds = array_unique($result->pluck('id')->toArray());

        foreach ($journalIds as $journalId) {
            // grab all positive transactiosn from this journal that are not deleted.
            // for each one, grab the negative opposing one which has 0 as an identifier and give it the same identifier.
            $identifier   = 0;
            $processed    = [];
            $transactions = Transaction::where('transaction_journal_id', $journalId)->where('amount', '>', 0)->get();
            /** @var Transaction $transaction */
            foreach ($transactions as $transaction) {
                // find opposing:
                $amount = bcmul(strval($transaction->amount), '-1');

                try {
                    /** @var Transaction $opposing */
                    $opposing = Transaction
                        ::where('transaction_journal_id', $journalId)
                        ->where('amount', $amount)->where('identifier', '=', 0)
                        ->whereNotIn('id', $processed)
                        ->first();
                } catch (QueryException $e) {
                    Log::error($e->getMessage());
                    $this->error('Firefly III could not find the "identifier" field in the "transactions" table.');
                    $this->error('This field is required for Firefly III version ' . config('firefly.version') . ' to run.');
                    $this->error('Please run "php artisan migrate" to add this field to the table.');
                    $this->info('Then, run "php artisan firefly:upgrade-database" to try again.');
                    break 2;
                }

                // give both a new identifier:
                $transaction->identifier = $identifier;
                $transaction->save();
                $opposing->identifier = $identifier;
                $opposing->save();
                $processed[] = $transaction->id;
                $processed[] = $opposing->id;
                $this->line(sprintf('Database upgrade for journal #%d, transactions #%d and #%d', $journalId, $transaction->id, $opposing->id));
                $identifier++;
            }
        }
    }
}