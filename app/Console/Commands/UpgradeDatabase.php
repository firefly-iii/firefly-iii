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
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Log;
use Preferences;
use Schema;

/**
 * Class UpgradeDatabase
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) // it just touches a lot of things.
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
        $this->updateAccountCurrencies();
        $this->updateTransferCurrencies();
        $this->updateOtherCurrencies();
        $this->info('Firefly III database is up to date.');

        return;


    }

    /**
     * Migrate budget repetitions to new format where the end date is in the budget limit as well,
     * making the limit_repetition table obsolete.
     */
    public function migrateRepetitions(): void
    {
        $set = BudgetLimit::whereNull('end_date')->get();
        /** @var BudgetLimit $budgetLimit */
        foreach ($set as $budgetLimit) {
            /** @var LimitRepetition $repetition */
            $repetition = $budgetLimit->limitrepetitions()->first();
            if (!is_null($repetition)) {
                $budgetLimit->end_date = $repetition->enddate;
                $budgetLimit->save();
                $this->line(sprintf('Updated budget limit #%d', $budgetLimit->id));
                $repetition->delete();
            }
        }

        return;
    }

    /**
     * This method gives all transactions which are part of a split journal (so more than 2) a sort of "order" so they are easier
     * to easier to match to their counterpart. When a journal is split, it has two or three transactions: -3, -4 and -5 for example.
     *
     * In the database this is reflected as 6 transactions: -3/+3, -4/+4, -5/+5.
     *
     * When either of these are the same amount, FF3 can't keep them apart: +3/-3, +3/-3, +3/-3. This happens more often than you would
     * think. So each set gets a number (1,2,3) to keep them apart.
     */
    public function setTransactionIdentifier(): void
    {
        // if table does not exist, return false
        if (!Schema::hasTable('transaction_journals')) {
            return;
        }
        $subQuery   = TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
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
            $this->updateJournalidentifiers(intval($journalId));
        }

        return;
    }

    /**
     * Each (asset) account must have a reference to a preferred currency. If the account does not have one, it's forced upon the account.
     */
    public function updateAccountCurrencies(): void
    {
        $accounts = Account::leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                           ->whereIn('account_types.type', [AccountType::DEFAULT, AccountType::ASSET])->get(['accounts.*']);

        $accounts->each(
            function (Account $account) {
                // get users preference, fall back to system pref.
                $defaultCurrencyCode = Preferences::getForUser($account->user, 'currencyPreference', config('firefly.default_currency', 'EUR'))->data;
                $defaultCurrency     = TransactionCurrency::where('code', $defaultCurrencyCode)->first();
                $accountCurrency     = intval($account->getMeta('currency_id'));
                $openingBalance      = $account->getOpeningBalance();
                $obCurrency          = intval($openingBalance->transaction_currency_id);

                // both 0? set to default currency:
                if ($accountCurrency === 0 && $obCurrency === 0) {
                    AccountMeta::create(['account_id' => $account->id, 'name' => 'currency_id', 'data' => $defaultCurrency->id]);
                    $this->line(sprintf('Account #%d ("%s") now has a currency setting (%s).', $account->id, $account->name, $defaultCurrencyCode));

                    return true;
                }

                // account is set to 0, opening balance is not?
                if ($accountCurrency === 0 && $obCurrency > 0) {
                    AccountMeta::create(['account_id' => $account->id, 'name' => 'currency_id', 'data' => $obCurrency]);
                    $this->line(sprintf('Account #%d ("%s") now has a currency setting (%s).', $account->id, $account->name, $defaultCurrencyCode));

                    return true;
                }

                // do not match and opening balance id is not null.
                if ($accountCurrency !== $obCurrency && $openingBalance->id > 0) {
                    // update opening balance:
                    $openingBalance->transaction_currency_id = $accountCurrency;
                    $openingBalance->save();
                    $this->line(sprintf('Account #%d ("%s") now has a correct currency for opening balance.', $account->id, $account->name));

                    return true;
                }

                return true;
            }
        );

        return;
    }

    /**
     * This routine verifies that withdrawals, deposits and opening balances have the correct currency settings for
     * the accounts they are linked to.
     *
     * Both source and destination must match the respective currency preference of the related asset account.
     * So FF3 must verify all transactions.
     */
    public function updateOtherCurrencies(): void
    {
        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);
        $set        = TransactionJournal
            ::leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
            ->whereIn('transaction_types.type', [TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::OPENING_BALANCE])
            ->get(['transaction_journals.*']);

        $set->each(
            function (TransactionJournal $journal) use ($repository) {
                // get the transaction with the asset account in it:
                /** @var Transaction $transaction */
                $transaction = $journal->transactions()
                                       ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                                       ->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                                       ->whereIn('account_types.type', [AccountType::DEFAULT, AccountType::ASSET])->first(['transactions.*']);
                /** @var Account $account */
                $account      = $transaction->account;
                $currency     = $repository->find(intval($account->getMeta('currency_id')));
                $transactions = $journal->transactions()->get();
                $transactions->each(
                    function (Transaction $transaction) use ($currency) {
                        if (is_null($transaction->transaction_currency_id)) {
                            $transaction->transaction_currency_id = $currency->id;
                            $transaction->save();
                            $this->line(sprintf('Transaction #%d is set to %s', $transaction->id, $currency->code));
                        }

                        // when mismatch in transaction:
                        if ($transaction->transaction_currency_id !== $currency->id) {
                            $this->line(
                                sprintf(
                                    'Transaction #%d is set to %s and foreign %s', $transaction->id, $currency->code, $transaction->transactionCurrency->code
                                )
                            );
                            $transaction->foreign_currency_id     = $transaction->transaction_currency_id;
                            $transaction->foreign_amount          = $transaction->amount;
                            $transaction->transaction_currency_id = $currency->id;
                            $transaction->save();
                        }
                    }
                );
                // also update the journal, of course:
                $journal->transaction_currency_id = $currency->id;
                $journal->save();
            }
        );

        return;
    }

    /**
     * This routine verifies that transfers have the correct currency settings for the accounts they are linked to.
     * For transfers, this is can be a destructive routine since we FORCE them into a currency setting whether they
     * like it or not. Previous routines MUST have set the currency setting for both accounts for this to work.
     *
     * Both source and destination must match the respective currency preference. So FF3 must verify ALL
     * transactions.
     */
    public function updateTransferCurrencies()
    {
        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);
        $set        = TransactionJournal
            ::leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
            ->where('transaction_types.type', TransactionType::TRANSFER)
            ->get(['transaction_journals.*']);

        $set->each(
            function (TransactionJournal $transfer) use ($repository) {
                /** @var Collection $transactions */
                $transactions = $transfer->transactions()->where('amount', '<', 0)->get();
                $transactions->each(
                    function (Transaction $transaction) {
                        $this->updateTransactionCurrency($transaction);
                        $this->updateJournalCurrency($transaction);
                    }
                );


                /** @var Collection $transactions */
                $transactions = $transfer->transactions()->where('amount', '>', 0)->get();
                $transactions->each(
                    function (Transaction $transaction) {
                        $this->updateTransactionCurrency($transaction);
                    }
                );
            }
        );
    }


    /**
     * This method makes sure that the transaction journal uses the currency given in the transaction.
     *
     * @param Transaction $transaction
     */
    private function updateJournalCurrency(Transaction $transaction): void
    {
        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);
        $currency   = $repository->find(intval($transaction->account->getMeta('currency_id')));
        $journal    = $transaction->transactionJournal;

        if ($currency->id !== $journal->transaction_currency_id) {
            $this->line(
                sprintf(
                    'Transfer #%d ("%s") has been updated to use %s instead of %s.', $journal->id, $journal->description, $currency->code,
                    $journal->transactionCurrency->code
                )
            );
            $journal->transaction_currency_id = $currency->id;
            $journal->save();
        }

        return;
    }

    /**
     * grab all positive transactiosn from this journal that are not deleted. for each one, grab the negative opposing one
     * which has 0 as an identifier and give it the same identifier.
     *
     * @param int $journalId
     */
    private function updateJournalidentifiers(int $journalId): void
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
                $opposing->identifier    = $identifier;
                $transaction->save();
                $opposing->save();
                $processed[] = $transaction->id;
                $processed[] = $opposing->id;
            }
            $identifier++;
        }

        return;
    }

    /**
     * This method makes sure that the tranaction uses the same currency as the main account does.
     * If not, the currency is updated to include a reference to its original currency as the "foreign" currency.
     *
     * @param Transaction $transaction
     */
    private function updateTransactionCurrency(Transaction $transaction): void
    {
        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);
        $currency   = $repository->find(intval($transaction->account->getMeta('currency_id')));

        if (is_null($transaction->transaction_currency_id)) {
            $transaction->transaction_currency_id = $currency->id;
            $transaction->save();
            $this->line(sprintf('Transaction #%d is set to %s', $transaction->id, $currency->code));
        }

        // when mismatch in transaction:
        if ($transaction->transaction_currency_id !== $currency->id) {
            $this->line(sprintf('Transaction #%d is set to %s and foreign %s', $transaction->id, $currency->code, $transaction->transactionCurrency->code));
            $transaction->foreign_currency_id     = $transaction->transaction_currency_id;
            $transaction->foreign_amount          = $transaction->amount;
            $transaction->transaction_currency_id = $currency->id;
            $transaction->save();
        }

        return;
    }
}
