<?php
/**
 * JournalCurrencies.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Console\Commands\Upgrade;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Log;

/**
 * Class JournalCurrencies
 */
class JournalCurrencies extends Command
{

    public const CONFIG_NAME = '4780_journal_currencies';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all transaction and journal currencies.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:journal-currencies {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->warn('This command has already been executed.');

            return 0;
        }

        $this->updateTransferCurrencies();
        $this->updateOtherCurrencies();
        $this->markAsExecuted();

        return 0;
    }

    /**
     * This routine verifies that withdrawals, deposits and opening balances have the correct currency settings for
     * the accounts they are linked to.
     *
     * Both source and destination must match the respective currency preference of the related asset account.
     * So FF3 must verify all transactions.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function updateOtherCurrencies(): void
    {
        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);
        /** @var AccountRepositoryInterface $accountRepos */
        $accountRepos = app(AccountRepositoryInterface::class);
        $set          = TransactionJournal
            ::leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
            ->whereIn('transaction_types.type', [TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::OPENING_BALANCE])
            ->get(['transaction_journals.*']);

        $set->each(
            function (TransactionJournal $journal) use ($repository, $accountRepos) {
                // get the transaction with the asset account in it:
                /** @var Transaction $transaction */
                $transaction = $journal->transactions()
                                       ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                                       ->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                                       ->whereIn('account_types.type', [AccountType::DEFAULT, AccountType::ASSET])->first(['transactions.*']);
                if (null === $transaction) {
                    return;
                }
                $accountRepos->setUser($journal->user);
                /** @var Account $account */
                $account  = $transaction->account;
                $currency = $repository->findNull((int)$accountRepos->getMetaValue($account, 'currency_id'));
                if (null === $currency) {
                    return;
                }
                $transactions = $journal->transactions()->get();
                $transactions->each(
                    function (Transaction $transaction) use ($currency) {
                        if (null === $transaction->transaction_currency_id) {
                            $transaction->transaction_currency_id = $currency->id;
                            $transaction->save();
                        }

                        // when mismatch in transaction:
                        if (!((int)$transaction->transaction_currency_id === (int)$currency->id)) {
                            $transaction->foreign_currency_id     = (int)$transaction->transaction_currency_id;
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
    public function updateTransferCurrencies(): void
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

    /**
     * @return bool
     */
    private function isExecuted(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);
        if (null !== $configVar) {
            return (bool)$configVar->data;
        }

        return false; // @codeCoverageIgnore
    }

    /**
     *
     */
    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
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
        /** @var AccountRepositoryInterface $accountRepos */
        $accountRepos = app(AccountRepositoryInterface::class);
        $accountRepos->setUser($transaction->account->user);
        $currency = $repository->findNull((int)$accountRepos->getMetaValue($transaction->account, 'currency_id'));
        $journal  = $transaction->transactionJournal;

        if (null === $currency) {
            return;
        }

        if (!((int)$currency->id === (int)$journal->transaction_currency_id)) {
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

    }

    /**
     * This method makes sure that the tranaction uses the same currency as the source account does.
     * If not, the currency is updated to include a reference to its original currency as the "foreign" currency.
     *
     * The transaction that is sent to this function MUST be the source transaction (amount negative).
     *
     * Method is long and complex but I'll allow it. https://imgur.com/gallery/dVDJiez
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @param Transaction $transaction
     */
    private function updateTransactionCurrency(Transaction $transaction): void
    {
        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);
        /** @var AccountRepositoryInterface $accountRepos */
        $accountRepos = app(AccountRepositoryInterface::class);
        /** @var JournalRepositoryInterface $journalRepos */
        $journalRepos = app(JournalRepositoryInterface::class);

        $accountRepos->setUser($transaction->account->user);
        $journalRepos->setUser($transaction->account->user);
        $currency = $repository->findNull((int)$accountRepos->getMetaValue($transaction->account, 'currency_id'));

        if (null === $currency) {
            Log::error(sprintf('Account #%d ("%s") must have currency preference but has none.', $transaction->account->id, $transaction->account->name));

            return;
        }

        // has no currency ID? Must have, so fill in using account preference:
        if (null === $transaction->transaction_currency_id) {
            $transaction->transaction_currency_id = (int)$currency->id;
            Log::debug(sprintf('Transaction #%d has no currency setting, now set to %s', $transaction->id, $currency->code));
            $transaction->save();
        }

        // does not match the source account (see above)? Can be fixed
        // when mismatch in transaction and NO foreign amount is set:
        if (!((int)$transaction->transaction_currency_id === (int)$currency->id) && null === $transaction->foreign_amount) {
            Log::debug(
                sprintf(
                    'Transaction #%d has a currency setting #%d that should be #%d. Amount remains %s, currency is changed.',
                    $transaction->id,
                    $transaction->transaction_currency_id,
                    $currency->id,
                    $transaction->amount
                )
            );
            $transaction->transaction_currency_id = (int)$currency->id;
            $transaction->save();
        }

        // grab opposing transaction:
        /** @var TransactionJournal $journal */
        $journal = $transaction->transactionJournal;
        /** @var Transaction $opposing */
        $opposing         = $journal->transactions()->where('amount', '>', 0)->where('identifier', $transaction->identifier)->first();
        $opposingCurrency = $repository->findNull((int)$accountRepos->getMetaValue($opposing->account, 'currency_id'));

        if (null === $opposingCurrency) {
            Log::error(sprintf('Account #%d ("%s") must have currency preference but has none.', $opposing->account->id, $opposing->account->name));

            return;
        }

        // if the destination account currency is the same, both foreign_amount and foreign_currency_id must be NULL for both transactions:
        if ((int)$opposingCurrency->id === (int)$currency->id) {
            // update both transactions to match:
            $transaction->foreign_amount       = null;
            $transaction->foreign_currency_id  = null;
            $opposing->foreign_amount          = null;
            $opposing->foreign_currency_id     = null;
            $opposing->transaction_currency_id = $currency->id;
            $transaction->save();
            $opposing->save();
            Log::debug(
                sprintf(
                    'Currency for account "%s" is %s, and currency for account "%s" is also
             %s, so %s #%d (#%d and #%d) has been verified to be to %s exclusively.',
                    $opposing->account->name, $opposingCurrency->code,
                    $transaction->account->name, $transaction->transactionCurrency->code,
                    $journal->transactionType->type, $journal->id,
                    $transaction->id, $opposing->id, $currency->code
                )
            );

            return;
        }
        // if destination account currency is different, both transactions must have this currency as foreign currency id.
        if (!((int)$opposingCurrency->id === (int)$currency->id)) {
            $transaction->foreign_currency_id = $opposingCurrency->id;
            $opposing->foreign_currency_id    = $opposingCurrency->id;
            $transaction->save();
            $opposing->save();
            Log::debug(sprintf('Verified foreign currency ID of transaction #%d and #%d', $transaction->id, $opposing->id));
        }

        // if foreign amount of one is null and the other is not, use this to restore:
        if (null === $transaction->foreign_amount && null !== $opposing->foreign_amount) {
            $transaction->foreign_amount = bcmul((string)$opposing->foreign_amount, '-1');
            $transaction->save();
            Log::debug(sprintf('Restored foreign amount of transaction (1) #%d to %s', $transaction->id, $transaction->foreign_amount));
        }

        // if foreign amount of one is null and the other is not, use this to restore (other way around)
        if (null === $opposing->foreign_amount && null !== $transaction->foreign_amount) {
            $opposing->foreign_amount = bcmul((string)$transaction->foreign_amount, '-1');
            $opposing->save();
            Log::debug(sprintf('Restored foreign amount of transaction (2) #%d to %s', $opposing->id, $opposing->foreign_amount));
        }

        // when both are zero, try to grab it from journal:
        if (null === $opposing->foreign_amount && null === $transaction->foreign_amount) {
            $foreignAmount = $journalRepos->getMetaField($journal, 'foreign_amount');
            if (null === $foreignAmount) {
                Log::debug(sprintf('Journal #%d has missing foreign currency data, forced to do 1:1 conversion :(.', $transaction->transaction_journal_id));
                $transaction->foreign_amount = bcmul((string)$transaction->amount, '-1');
                $opposing->foreign_amount    = bcmul((string)$opposing->amount, '-1');
                $transaction->save();
                $opposing->save();

                return;
            }
            $foreignPositive = app('steam')->positive((string)$foreignAmount);
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

    }
}