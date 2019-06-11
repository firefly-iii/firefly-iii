<?php
/**
 * TransferCurrenciesCorrections.php
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

namespace FireflyIII\Console\Commands\Upgrade;


use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Console\Command;
use Log;

/**
 * Class TransferCurrenciesCorrections
 */
class TransferCurrenciesCorrections extends Command
{

    public const CONFIG_NAME = '4780_transfer_currencies';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates transfer currency information.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:transfer-currencies {--F|force : Force the execution of this command.}';
    /** @var array */
    private $accountCurrencies;
    /** @var AccountRepositoryInterface */
    private $accountRepos;
    /** @var CurrencyRepositoryInterface */
    private $currencyRepos;
    /** @var JournalRepositoryInterface */
    private $journalRepos;
    /** @var int */
    private $count;

    /** @var Transaction The source transaction of the current journal. */
    private $sourceTransaction;
    /** @var Account The source account of the current journal. */
    private $sourceAccount;
    /** @var TransactionCurrency The currency preference of the source account of the current journal. */
    private $sourceCurrency;
    /** @var Transaction The destination transaction of the current journal. */
    private $destinationTransaction;
    /** @var Account The destination account of the current journal. */
    private $destinationAccount;
    /** @var TransactionCurrency The currency preference of the destination account of the current journal. */
    private $destinationCurrency;


    /**
     * JournalCurrencies constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->count             = 0;
        $this->accountRepos      = app(AccountRepositoryInterface::class);
        $this->currencyRepos     = app(CurrencyRepositoryInterface::class);
        $this->journalRepos      = app(JournalRepositoryInterface::class);
        $this->accountCurrencies = [];
        $this->resetInformation();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $start = microtime(true);
        // @codeCoverageIgnoreStart
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->warn('This command has already been executed.');

            return 0;
        }
        // @codeCoverageIgnoreEnd

        $this->startUpdateRoutine();
        $this->markAsExecuted();

        if (0 === $this->count) {
            $this->line('All transfers have correct currency information.');
        }
        if (0 !== $this->count) {
            $this->line(sprintf('Verified currency information of %d transfer(s).', $this->count));
        }
        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Verified and fixed currency information for transfers in %s seconds.', $end));

        return 0;
    }

    /**
     * @param Account $account
     *
     * @return TransactionCurrency|null
     */
    private function getCurrency(Account $account): ?TransactionCurrency
    {
        $accountId = $account->id;
        if (isset($this->accountCurrencies[$accountId]) && 0 === $this->accountCurrencies[$accountId]) {
            return null;
        }
        if (isset($this->accountCurrencies[$accountId]) && $this->accountCurrencies[$accountId] instanceof TransactionCurrency) {
            return $this->accountCurrencies[$accountId];
        }
        $currencyId = (int)$this->accountRepos->getMetaValue($account, 'currency_id');
        $result     = $this->currencyRepos->findNull($currencyId);
        if (null === $result) {
            $this->accountCurrencies[$accountId] = 0;

            return null;
        }
        $this->accountCurrencies[$accountId] = $result;

        return $result;


    }

    /**
     * @param TransactionJournal $transfer
     *
     * @return Transaction|null
     */
    private function getDestinationTransaction(TransactionJournal $transfer): ?Transaction
    {
        return $transfer->transactions->firstWhere('amount', '>', 0);
    }

    /**
     * @param TransactionJournal $transfer
     *
     * @return Transaction|null
     */
    private function getSourceTransaction(TransactionJournal $transfer): ?Transaction
    {
        return $transfer->transactions->firstWhere('amount', '<', 0);
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
     * This method makes sure that the transaction journal uses the currency given in the source transaction.
     *
     * @param TransactionJournal $journal
     */
    private function fixTransactionJournalCurrency(TransactionJournal $journal): void
    {
        if ($journal->transaction_currency_id !== $this->sourceCurrency->id) {
            $oldCurrencyCode                  = $journal->transactionCurrency->code ?? '(nothing)';
            $journal->transaction_currency_id = $this->sourceCurrency->id;
            $this->count++;
            $this->line(
                sprintf(
                    'Transfer #%d ("%s") has been updated to use %s instead of %s.',
                    $journal->id,
                    $journal->description,
                    $this->sourceCurrency->code,
                    $oldCurrencyCode
                )
            );
            $journal->save();
        }
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
    private function startUpdateRoutine(): void
    {
        $set = $this->journalRepos->getAllJournals([TransactionType::TRANSFER]);
        /** @var TransactionJournal $journal */
        foreach ($set as $journal) {
            $this->updateTransferCurrency($journal);
        }
    }

    /**
     * Reset all the class fields for the current transfer
     */
    private function resetInformation(): void
    {
        $this->sourceTransaction      = null;
        $this->sourceAccount          = null;
        $this->sourceCurrency         = null;
        $this->destinationTransaction = null;
        $this->destinationAccount     = null;
        $this->destinationCurrency    = null;
    }

    /**
     * Extract source transaction, source account + source account currency from the journal.
     * @param TransactionJournal $journal
     */
    private function getSourceInformation(TransactionJournal $journal): void
    {
        $this->sourceTransaction = $this->getSourceTransaction($journal);
        $this->sourceAccount     = null === $this->sourceTransaction ? null : $this->sourceTransaction->account;
        $this->sourceCurrency    = null === $this->sourceAccount ? null : $this->getCurrency($this->sourceAccount);
    }

    /**
     * Extract destination transaction, destination account + destination account currency from the journal.
     * @param TransactionJournal $journal
     */
    private function getDestinationInformation(TransactionJournal $journal): void
    {
        $this->destinationTransaction = $this->getDestinationTransaction($journal);
        $this->destinationAccount     = null === $this->destinationTransaction ? null : $this->destinationTransaction->account;
        $this->destinationCurrency    = null === $this->destinationAccount ? null : $this->getCurrency($this->destinationAccount);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @param TransactionJournal $transfer
     */
    private function updateTransferCurrency(TransactionJournal $transfer): void
    {
        $this->resetInformation();

        if ($this->isSplitJournal($transfer)) {
            $this->line(sprintf(sprintf('Transaction journal #%d is a split journal. Cannot continue.', $transfer->id)));
        }

        $this->getSourceInformation($transfer);
        $this->getDestinationInformation($transfer);

        // unexpectedly, either one is null:
        if ($this->isEmptyTransactions()) {
            $this->error(sprintf('Source or destination information for transaction journal #%d is null. Cannot fix this one.', $transfer->id));

            return;
        }

        // both accounts must have currency preference:
        if ($this->isNoCurrencyPresent()) {
            return;
        }

        // fix source transaction having no currency.
        $this->fixSourceNoCurrency();

        // fix source transaction having bad currency.
        $this->fixSourceUnmatchedCurrency();

        // fix destination transaction having no currency.
        $this->fixDestNoCurrency();

        // fix destination transaction having bad currency.
        $this->fixDestinationUnmatchedCurrency();

        // remove foreign currency information if not necessary.
        $this->fixInvalidForeignCurrency();

        // correct foreign currency info if necessary.
        $this->fixMismatchedForeignCurrency();

        // restore missing foreign currency amount.
        $this->fixSourceNullForeignAmount();
        $this->fixDestNullForeignAmount();

        // fix journal itself:
        $this->fixTransactionJournalCurrency($transfer);
    }

    /**
     * The source transaction must have a currency. If not, it will be added by
     * taking it from the source account's preference.
     */
    private function fixSourceNoCurrency(): void
    {
        if (null === $this->sourceTransaction->transaction_currency_id && null !== $this->sourceCurrency) {
            $this->sourceTransaction
                ->transaction_currency_id = (int)$this->sourceCurrency->id;
            $message                      = sprintf('Transaction #%d has no currency setting, now set to %s.',
                                                    $this->sourceTransaction->id, $this->sourceCurrency->code);
            Log::debug($message);
            $this->line($message);
            $this->count++;
            $this->sourceTransaction->save();
        }
    }

    /**
     * The destination transaction must have a currency. If not, it will be added by
     * taking it from the destination account's preference.
     */
    private function fixDestNoCurrency(): void
    {
        if (null === $this->destinationTransaction->transaction_currency_id && null !== $this->destinationCurrency) {
            $this->destinationTransaction
                ->transaction_currency_id = (int)$this->destinationCurrency->id;
            $message                      = sprintf('Transaction #%d has no currency setting, now set to %s.',
                                                    $this->destinationTransaction->id, $this->destinationCurrency->code);
            Log::debug($message);
            $this->line($message);
            $this->count++;
            $this->destinationTransaction->save();
        }
    }

    /**
     * The source transaction must have the correct currency. If not, it will be set by
     * taking it from the source account's preference.
     */
    private function fixSourceUnmatchedCurrency(): void
    {
        if (null !== $this->sourceCurrency &&
            null === $this->sourceTransaction->foreign_amount &&
            (int)$this->sourceTransaction->transaction_currency_id !== (int)$this->sourceCurrency->id
        ) {


            $message = sprintf(
                'Transaction #%d has a currency setting #%d that should be #%d. Amount remains %s, currency is changed.',
                $this->sourceTransaction->id,
                $this->sourceTransaction->transaction_currency_id,
                $this->sourceAccount->id,
                $this->sourceTransaction->amount
            );
            Log::debug($message);
            $this->line($message);
            $this->count++;
            $this->sourceTransaction->transaction_currency_id = (int)$this->sourceCurrency->id;
            $this->sourceTransaction->save();
        }
    }

    /**
     * The destination transaction must have the correct currency. If not, it will be set by
     * taking it from the destination account's preference.
     */
    private function fixDestinationUnmatchedCurrency(): void
    {
        if (null !== $this->destinationCurrency &&
            null === $this->destinationTransaction->foreign_amount &&
            (int)$this->destinationTransaction->transaction_currency_id !== (int)$this->destinationCurrency->id
        ) {
            $message = sprintf(
                'Transaction #%d has a currency setting #%d that should be #%d. Amount remains %s, currency is changed.',
                $this->destinationTransaction->id,
                $this->destinationTransaction->transaction_currency_id,
                $this->destinationAccount->id,
                $this->destinationTransaction->amount
            );
            Log::debug($message);
            $this->line($message);
            $this->count++;
            $this->destinationTransaction->transaction_currency_id = (int)$this->destinationCurrency->id;
            $this->destinationTransaction->save();
        }
    }

    /**
     * Is this a split transaction journal?
     *
     * @param TransactionJournal $transfer
     * @return bool
     */
    private function isSplitJournal(TransactionJournal $transfer): bool
    {
        return $transfer->transactions->count() > 2;
    }

    /**
     * Is either the source or destination transaction NULL?
     * @return bool
     */
    private function isEmptyTransactions(): bool
    {
        return null === $this->sourceTransaction || null === $this->destinationTransaction ||
               null === $this->sourceAccount || null === $this->destinationAccount;
    }

    /**
     * If the destination account currency is the same as the source currency,
     * both foreign_amount and foreign_currency_id fields must be NULL
     * for both transactions (because foreign currency info would not make sense)
     */
    private function fixInvalidForeignCurrency(): void
    {
        if ((int)$this->destinationCurrency->id === (int)$this->sourceCurrency->id) {
            // update both transactions to match:
            $this->sourceTransaction->foreign_amount      = null;
            $this->sourceTransaction->foreign_currency_id = null;

            $this->destinationTransaction->foreign_amount      = null;
            $this->destinationTransaction->foreign_currency_id = null;

            $this->sourceTransaction->save();
            $this->destinationTransaction->save();

            Log::debug(
                sprintf(
                    'Currency for account "%s" is %s, and currency for account "%s" is also
             %s, so transactions #%d and #%d has been verified to be to %s exclusively.',
                    $this->destinationAccount->name, $this->destinationCurrency->code,
                    $this->sourceAccount->name, $this->sourceCurrency->code,
                    $this->sourceTransaction->id, $this->destinationTransaction->id, $this->sourceCurrency->code
                )
            );
            $this->count++;

            return;
        }
    }

    /**
     * If destination account currency is different from source account currency, then
     * both transactions must have each others currency as foreign currency id.
     */
    private function fixMismatchedForeignCurrency(): void
    {
        if ((int)$this->sourceCurrency->id !== (int)$this->destinationCurrency->id) {
            $this->sourceTransaction->foreign_currency_id      = $this->destinationCurrency->id;
            $this->destinationTransaction->foreign_currency_id = $this->sourceCurrency->id;

            $this->sourceTransaction->save();
            $this->destinationTransaction->save();
            $this->count++;
            Log::debug(sprintf('Verified foreign currency ID of transaction #%d and #%d', $this->sourceTransaction->id, $this->destinationTransaction->id));
        }
    }

    /**
     * If the foreign amount of the source transaction is null, but that of the other isn't, use this piece of code
     * to restore it.
     */
    private function fixSourceNullForeignAmount(): void
    {
        if (null === $this->sourceTransaction->foreign_amount && null !== $this->destinationTransaction->foreign_amount) {
            $this->sourceTransaction->foreign_amount = bcmul((string)$this->destinationTransaction->foreign_amount, '-1');
            $this->sourceTransaction->save();
            $this->count++;
            Log::debug(sprintf('Restored foreign amount of source transaction #%d to %s',
                               $this->sourceTransaction->id, $this->sourceTransaction->foreign_amount));
        }
    }

    /**
     * If the foreign amount of the destination transaction is null, but that of the other isn't, use this piece of code
     * to restore it.
     */
    private function fixDestNullForeignAmount(): void
    {
        if (null === $this->destinationTransaction->foreign_amount && null !== $this->sourceTransaction->foreign_amount) {
            $this->destinationTransaction->foreign_amount = bcmul((string)$this->sourceTransaction->foreign_amount, '-1');
            $this->destinationTransaction->save();
            $this->count++;
            Log::debug(sprintf('Restored foreign amount of destination transaction #%d to %s',
                               $this->destinationTransaction->id, $this->destinationTransaction->foreign_amount));
        }
    }

    /**
     * @return bool
     */
    private function isNoCurrencyPresent(): bool
    {
        // source account must have a currency preference.
        if (null === $this->sourceCurrency) {
            $message = sprintf('Account #%d ("%s") must have currency preference but has none.', $this->sourceAccount->id, $this->sourceAccount->name);
            Log::error($message);
            $this->error($message);

            return false;
        }

        // destination account must have a currency preference.
        if (null === $this->destinationCurrency) {
            $message = sprintf('Account #%d ("%s") must have currency preference but has none.',
                               $this->destinationAccount->id, $this->destinationAccount->name);
            Log::error($message);
            $this->error($message);

            return false;
        }

        return true;
    }

}