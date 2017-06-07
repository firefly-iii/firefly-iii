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

declare(strict_types=1);

namespace FireflyIII\Console\Commands;


use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\QueryException;
use Log;
use Preferences;
use Schema;
use Steam;

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
        $this->migrateRepetitions();
        $this->repairPiggyBanks();
        $this->updateAccountCurrencies();
        $this->updateJournalCurrencies();
        $this->currencyInfoToTransactions();
        $this->verifyCurrencyInfo();
        $this->info('Firefly III database is up to date.');
    }

    /**
     * Moves the currency id info to the transaction instead of the journal.
     */
    private function currencyInfoToTransactions()
    {
        $count = 0;
        $set   = TransactionJournal::with('transactions')->get();
        /** @var TransactionJournal $journal */
        foreach ($set as $journal) {
            /** @var Transaction $transaction */
            foreach ($journal->transactions as $transaction) {
                if (is_null($transaction->transaction_currency_id)) {
                    $transaction->transaction_currency_id = $journal->transaction_currency_id;
                    $transaction->save();
                    $count++;
                }
            }


            // read and use the foreign amounts when present.
            if ($journal->hasMeta('foreign_amount')) {
                $amount = Steam::positive($journal->getMeta('foreign_amount'));

                // update both transactions:
                foreach ($journal->transactions as $transaction) {
                    $transaction->foreign_amount = $amount;
                    if (bccomp($transaction->amount, '0') === -1) {
                        // update with negative amount:
                        $transaction->foreign_amount = bcmul($amount, '-1');
                    }
                    // set foreign currency id:
                    $transaction->foreign_currency_id = intval($journal->getMeta('foreign_currency_id'));
                    $transaction->save();
                }
                $journal->deleteMeta('foreign_amount');
                $journal->deleteMeta('foreign_currency_id');
            }

        }

        $this->line(sprintf('Updated currency information for %d transactions', $count));
    }

    /**
     *  Migrate budget repetitions to new format.
     */
    private function migrateRepetitions()
    {
        if (!Schema::hasTable('budget_limits')) {
            return;
        }
        // get all budget limits with end_date NULL
        $set = BudgetLimit::whereNull('end_date')->get();
        if ($set->count() > 0) {
            $this->line(sprintf('Found %d budget limit(s) to update', $set->count()));
        }
        /** @var BudgetLimit $budgetLimit */
        foreach ($set as $budgetLimit) {
            // get limit repetition (should be just one):
            /** @var LimitRepetition $repetition */
            $repetition = $budgetLimit->limitrepetitions()->first();
            if (!is_null($repetition)) {
                $budgetLimit->end_date = $repetition->enddate;
                $budgetLimit->save();
                $this->line(sprintf('Updated budget limit #%d', $budgetLimit->id));
                $repetition->delete();
            }
        }
    }

    /**
     * Make sure there are only transfers linked to piggy bank events.
     */
    private function repairPiggyBanks()
    {
        // if table does not exist, return false
        if (!Schema::hasTable('piggy_bank_events')) {
            return;
        }
        $set = PiggyBankEvent::with(['PiggyBank', 'TransactionJournal', 'TransactionJournal.TransactionType'])->get();
        /** @var PiggyBankEvent $event */
        foreach ($set as $event) {

            if (is_null($event->transaction_journal_id)) {
                continue;
            }
            /** @var TransactionJournal $journal */
            $journal = $event->transactionJournal()->first();
            if (is_null($journal)) {
                continue;
            }

            $type = $journal->transactionType->type;
            if ($type !== TransactionType::TRANSFER) {
                $event->transaction_journal_id = null;
                $event->save();
                $this->line(sprintf('Piggy bank #%d was referenced by an invalid event. This has been fixed.', $event->piggy_bank_id));
            }
        }
    }

    /**
     * This is strangely complex, because the HAVING modifier is a no-no. And subqueries in Laravel are weird.
     */
    private function setTransactionIdentifier()
    {
        // if table does not exist, return false
        if (!Schema::hasTable('transaction_journals')) {
            return;
        }


        $subQuery = TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
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
            $this->updateJournal(intval($journalId));
        }
    }

    /**
     *
     */
    private function updateAccountCurrencies()
    {
        $accounts = Account::leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                           ->whereIn('account_types.type', [AccountType::DEFAULT, AccountType::ASSET])->get(['accounts.*']);

        /** @var Account $account */
        foreach ($accounts as $account) {
            // get users preference, fall back to system pref.
            $defaultCurrencyCode    = Preferences::getForUser($account->user, 'currencyPreference', config('firefly.default_currency', 'EUR'))->data;
            $defaultCurrency        = TransactionCurrency::where('code', $defaultCurrencyCode)->first();
            $accountCurrency        = intval($account->getMeta('currency_id'));
            $openingBalance         = $account->getOpeningBalance();
            $openingBalanceCurrency = intval($openingBalance->transaction_currency_id);

            // both 0? set to default currency:
            if ($accountCurrency === 0 && $openingBalanceCurrency === 0) {
                AccountMeta::create(['account_id' => $account->id, 'name' => 'currency_id', 'data' => $defaultCurrency->id]);
                $this->line(sprintf('Account #%d ("%s") now has a currency setting (%s).', $account->id, $account->name, $defaultCurrencyCode));
                continue;
            }

            // opening balance 0, account not zero? just continue:
            if ($accountCurrency > 0 && $openingBalanceCurrency === 0) {
                continue;
            }
            // account is set to 0, opening balance is not?
            if ($accountCurrency === 0 && $openingBalanceCurrency > 0) {
                AccountMeta::create(['account_id' => $account->id, 'name' => 'currency_id', 'data' => $openingBalanceCurrency]);
                $this->line(sprintf('Account #%d ("%s") now has a currency setting (%s).', $account->id, $account->name, $defaultCurrencyCode));
                continue;
            }

            // both are equal, just continue:
            if ($accountCurrency === $openingBalanceCurrency) {
                continue;
            }
            // do not match:
            if ($accountCurrency !== $openingBalanceCurrency) {
                // update opening balance:
                $openingBalance->transaction_currency_id = $accountCurrency;
                $openingBalance->save();
                $this->line(sprintf('Account #%d ("%s") now has a correct currency for opening balance.', $account->id, $account->name));
                continue;
            }
        }

    }

    /**
     * grab all positive transactiosn from this journal that are not deleted. for each one, grab the negative opposing one
     * which has 0 as an identifier and give it the same identifier.
     *
     * @param int $journalId
     */
    private function updateJournal(int $journalId)
    {
        $identifier   = 0;
        $processed    = [];
        $transactions = Transaction::where('transaction_journal_id', $journalId)->where('amount', '>', 0)->get();
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            // find opposing:
            $amount = bcmul(strval($transaction->amount), '-1');

            try {
                /** @var Transaction $opposing */
                $opposing = Transaction::where('transaction_journal_id', $journalId)
                                       ->where('amount', $amount)->where('identifier', '=', 0)
                                       ->whereNotIn('id', $processed)
                                       ->first();
            } catch (QueryException $e) {
                Log::error($e->getMessage());
                $this->error('Firefly III could not find the "identifier" field in the "transactions" table.');
                $this->error(sprintf('This field is required for Firefly III version %s to run.', config('firefly.version')));
                $this->error('Please run "php artisan migrate" to add this field to the table.');
                $this->info('Then, run "php artisan firefly:upgrade-database" to try again.');

                return;
            }
            if (!is_null($opposing)) {
                // give both a new identifier:
                $transaction->identifier = $identifier;
                $transaction->save();
                $opposing->identifier = $identifier;
                $opposing->save();
                $processed[] = $transaction->id;
                $processed[] = $opposing->id;
            }
            $identifier++;
        }
    }

    /**
     * Makes sure that withdrawals, deposits and transfers have
     * a currency setting matching their respective accounts
     */
    private function updateJournalCurrencies()
    {
        $types        = [
            TransactionType::WITHDRAWAL => '<',
            TransactionType::DEPOSIT    => '>',
        ];
        $repository   = app(CurrencyRepositoryInterface::class);
        $notification = '%s #%d uses %s but should use %s. It has been updated. Please verify this in Firefly III.';
        $transfer     = 'Transfer #%d has been updated to use the correct currencies. Please verify this in Firefly III.';
        $driver       = DB::connection()->getDriverName();

        foreach ($types as $type => $operator) {
            $query = TransactionJournal
                ::leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')->leftJoin(
                    'transactions', function (JoinClause $join) use ($operator) {
                    $join->on('transaction_journals.id', '=', 'transactions.transaction_journal_id')->where('transactions.amount', $operator, '0');
                }
                )
                ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                ->leftJoin('account_meta', 'account_meta.account_id', '=', 'accounts.id')
                ->where('transaction_types.type', $type)
                ->where('account_meta.name', 'currency_id');
            if ($driver === 'postgresql') {
                $query->where('transaction_journals.transaction_currency_id', '!=', DB::raw('cast(account_meta.data as int)'));
            }
            if ($driver !== 'postgresql') {
                $query->where('transaction_journals.transaction_currency_id', '!=', DB::raw('account_meta.data'));
            }

            $set = $query->get(['transaction_journals.*', 'account_meta.data as expected_currency_id', 'transactions.amount as transaction_amount']);
            /** @var TransactionJournal $journal */
            foreach ($set as $journal) {
                $expectedCurrency = $repository->find(intval($journal->expected_currency_id));
                $line             = sprintf($notification, $type, $journal->id, $journal->transactionCurrency->code, $expectedCurrency->code);

                $journal->setMeta('foreign_amount', $journal->transaction_amount);
                $journal->setMeta('foreign_currency_id', $journal->transaction_currency_id);
                $journal->transaction_currency_id = $expectedCurrency->id;
                $journal->save();
                $this->line($line);
            }
        }
        /*
         * For transfers it's slightly different. Both source and destination
         * must match the respective currency preference. So we must verify ALL
         * transactions.
         */
        $set = TransactionJournal
            ::leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
            ->where('transaction_types.type', TransactionType::TRANSFER)
            ->get(['transaction_journals.*']);
        /** @var TransactionJournal $journal */
        foreach ($set as $journal) {
            $updated = false;
            /** @var Transaction $sourceTransaction */
            $sourceTransaction = $journal->transactions()->where('amount', '<', 0)->first();
            $sourceCurrency    = $repository->find(intval($sourceTransaction->account->getMeta('currency_id')));

            if ($sourceCurrency->id !== $journal->transaction_currency_id) {
                $updated                          = true;
                $journal->transaction_currency_id = $sourceCurrency->id;
                $journal->save();
            }

            // destination
            $destinationTransaction = $journal->transactions()->where('amount', '>', 0)->first();
            $destinationCurrency    = $repository->find(intval($destinationTransaction->account->getMeta('currency_id')));

            if ($destinationCurrency->id !== $journal->transaction_currency_id) {
                $updated = true;
                $journal->deleteMeta('foreign_amount');
                $journal->deleteMeta('foreign_currency_id');
                $journal->setMeta('foreign_amount', $destinationTransaction->amount);
                $journal->setMeta('foreign_currency_id', $destinationCurrency->id);
            }
            if ($updated) {
                $line = sprintf($transfer, $journal->id);
                $this->line($line);
            }
        }
    }

    /**
     *
     */
    private function verifyCurrencyInfo()
    {
        $count        = 0;
        $transactions = Transaction::get();
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $currencyId = intval($transaction->transaction_currency_id);
            $foreignId  = intval($transaction->foreign_currency_id);
            if ($currencyId === $foreignId) {
                $transaction->foreign_currency_id = null;
                $transaction->foreign_amount      = null;
                $transaction->save();
                $count++;
            }
        }
        $this->line(sprintf('Updated currency information for %d transactions', $count));
    }
}
