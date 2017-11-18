<?php
/**
 * UpgradeDatabase.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Console\Commands;

use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Models\Note;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Log;
use Preferences;
use Schema;

/**
 * Class UpgradeDatabase.
 *
 * Upgrade user database.
 *
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) // it just touches a lot of things.
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
        $this->createNewTypes();
        $this->line('Updating currency information..');
        $this->updateTransferCurrencies();
        $this->updateOtherCurrencies();
        $this->line('Done updating currency information..');
        $this->migrateNotes();
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
            if (null !== $repetition) {
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
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) // it's seven but it can't really be helped.
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
                if (0 === $accountCurrency && 0 === $obCurrency) {
                    AccountMeta::create(['account_id' => $account->id, 'name' => 'currency_id', 'data' => $defaultCurrency->id]);
                    $this->line(sprintf('Account #%d ("%s") now has a currency setting (%s).', $account->id, $account->name, $defaultCurrencyCode));

                    return true;
                }

                // account is set to 0, opening balance is not?
                if (0 === $accountCurrency && $obCurrency > 0) {
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
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
                if (null === $transaction) {
                    return;
                }
                /** @var Account $account */
                $account      = $transaction->account;
                $currency     = $repository->find(intval($account->getMeta('currency_id')));
                $transactions = $journal->transactions()->get();
                $transactions->each(
                    function (Transaction $transaction) use ($currency) {
                        if (null === $transaction->transaction_currency_id) {
                            $transaction->transaction_currency_id = $currency->id;
                            $transaction->save();
                        }

                        // when mismatch in transaction:
                        if ($transaction->transaction_currency_id !== $currency->id) {
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
     * A transfer always has the
     *
     * Both source and destination must match the respective currency preference. So FF3 must verify ALL
     * transactions.
     */
    public function updateTransferCurrencies()
    {
        $set = TransactionJournal
            ::leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
            ->where('transaction_types.type', TransactionType::TRANSFER)
            ->get(['transaction_journals.*']);

        $set->each(
            function (TransactionJournal $transfer) {
                // select all "source" transactions:
                /** @var Collection $transactions */
                $transactions = $transfer->transactions()->where('amount', '<', 0)->get();
                $transactions->each(
                    function (Transaction $transaction) {
                        $this->updateTransactionCurrency($transaction);
                        $this->updateJournalCurrency($transaction);
                    }
                );
            }
        );
    }

    private function createNewTypes(): void
    {
        // create transaction type "Reconciliation".
        $type = TransactionType::where('type', TransactionType::RECONCILIATION)->first();
        if (is_null($type)) {
            TransactionType::create(['type' => TransactionType::RECONCILIATION]);
        }
        $account = AccountType::where('type', AccountType::RECONCILIATION)->first();
        if (is_null($account)) {
            AccountType::create(['type' => AccountType::RECONCILIATION]);
        }
    }

    /**
     * Move all the journal_meta notes to their note object counter parts.
     */
    private function migrateNotes(): void
    {
        $set = TransactionJournalMeta::whereName('notes')->get();
        /** @var TransactionJournalMeta $meta */
        foreach ($set as $meta) {
            $journal = $meta->transactionJournal;
            $note    = $journal->notes()->first();
            if (null === $note) {
                $note = new Note();
                $note->noteable()->associate($journal);
            }

            $note->text = $meta->data;
            $note->save();
            Log::debug(sprintf('Migrated meta note #%d to Note #%d', $meta->id, $note->id));
            $meta->delete();
        }
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

        if (!(intval($currency->id) === intval($journal->transaction_currency_id))) {
            $this->line(
                sprintf(
                    'Transfer #%d ("%s") has been updated to use %s instead of %s.',
                    $journal->id,
                    $journal->description,
                    $currency->code,
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
            if (null !== $opposing) {
                // give both a new identifier:
                $transaction->identifier = $identifier;
                $opposing->identifier    = $identifier;
                $transaction->save();
                $opposing->save();
                $processed[] = $transaction->id;
                $processed[] = $opposing->id;
            }
            ++$identifier;
        }

        return;
    }

    /**
     * This method makes sure that the tranaction uses the same currency as the source account does.
     * If not, the currency is updated to include a reference to its original currency as the "foreign" currency.
     *
     * The transaction that is sent to this function MUST be the source transaction (amount negative).
     *
     * Method is long and complex bit I'm taking it for granted.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param Transaction $transaction
     */
    private function updateTransactionCurrency(Transaction $transaction): void
    {
        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);
        $currency   = $repository->find(intval($transaction->account->getMeta('currency_id')));

        // has no currency ID? Must have, so fill in using account preference:
        if (null === $transaction->transaction_currency_id) {
            $transaction->transaction_currency_id = $currency->id;
            Log::debug(sprintf('Transaction #%d has no currency setting, now set to %s', $transaction->id, $currency->code));
            $transaction->save();
        }

        // does not match the source account (see above)? Can be fixed
        // when mismatch in transaction and NO foreign amount is set:
        if ($transaction->transaction_currency_id !== $currency->id && null === $transaction->foreign_amount) {
            Log::debug(
                sprintf(
                    'Transaction #%d has a currency setting (#%d) that should be #%d. Amount remains %s, currency is changed.',
                    $transaction->id,
                    $transaction->transaction_currency_id,
                    $currency->id,
                    $transaction->amount
                )
            );
            $transaction->transaction_currency_id = $currency->id;
            $transaction->save();
        }

        // grab opposing transaction:
        /** @var TransactionJournal $journal */
        $journal = $transaction->transactionJournal;
        /** @var Transaction $opposing */
        $opposing         = $journal->transactions()->where('amount', '>', 0)->where('identifier', $transaction->identifier)->first();
        $opposingCurrency = $repository->find(intval($opposing->account->getMeta('currency_id')));

        if (null === $opposingCurrency->id) {
            Log::error(sprintf('Account #%d ("%s") must have currency preference but has none.', $opposing->account->id, $opposing->account->name));

            return;
        }

        // if the destination account currency is the same, both foreign_amount and foreign_currency_id must be NULL for both transactions:
        if ($opposingCurrency->id === $currency->id) {
            // update both transactions to match:
            $transaction->foreign_amount       = null;
            $transaction->foreign_currency_id  = null;
            $opposing->foreign_amount          = null;
            $opposing->foreign_currency_id     = null;
            $opposing->transaction_currency_id = $currency->id;
            $transaction->save();
            $opposing->save();
            Log::debug(sprintf('Cleaned up transaction #%d and #%d', $transaction->id, $opposing->id));

            return;
        }
        // if destination account currency is different, both transactions must have this currency as foreign currency id.
        if ($opposingCurrency->id !== $currency->id) {
            $transaction->foreign_currency_id = $opposingCurrency->id;
            $opposing->foreign_currency_id    = $opposingCurrency->id;
            $transaction->save();
            $opposing->save();
            Log::debug(sprintf('Verified foreign currency ID of transaction #%d and #%d', $transaction->id, $opposing->id));
        }

        // if foreign amount of one is null and the other is not, use this to restore:
        if (null === $transaction->foreign_amount && null !== $opposing->foreign_amount) {
            $transaction->foreign_amount = bcmul(strval($opposing->foreign_amount), '-1');
            $transaction->save();
            Log::debug(sprintf('Restored foreign amount of transaction (1) #%d to %s', $transaction->id, $transaction->foreign_amount));
        }

        // if foreign amount of one is null and the other is not, use this to restore (other way around)
        if (null === $opposing->foreign_amount && null !== $transaction->foreign_amount) {
            $opposing->foreign_amount = bcmul(strval($transaction->foreign_amount), '-1');
            $opposing->save();
            Log::debug(sprintf('Restored foreign amount of transaction (2) #%d to %s', $opposing->id, $opposing->foreign_amount));
        }

        // when both are zero, try to grab it from journal:
        if (null === $opposing->foreign_amount && null === $transaction->foreign_amount) {
            $foreignAmount = $journal->getMeta('foreign_amount');
            if (null === $foreignAmount) {
                Log::debug(sprintf('Journal #%d has missing foreign currency data, forced to do 1:1 conversion :(.', $transaction->transaction_journal_id));
                $transaction->foreign_amount = bcmul(strval($transaction->amount), '-1');
                $opposing->foreign_amount    = bcmul(strval($opposing->amount), '-1');
                $transaction->save();
                $opposing->save();

                return;
            }
            $foreignPositive = app('steam')->positive(strval($foreignAmount));
            Log::debug(
                sprintf(
                    'Journal #%d has missing foreign currency info, try to restore from meta-data ("%s").',
                    $transaction->transaction_journal_id,
                    $foreignAmount
                )
            );
            $transaction->foreign_amount = bcmul($foreignPositive, '-1');
            $opposing->foreign_amount    = $foreignPositive;
            $transaction->save();
            $opposing->save();
        }

        return;
    }
}
