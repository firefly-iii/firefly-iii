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
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Console\Command;
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
    /** @var array */
    private $accountCurrencies;
    /** @var AccountRepositoryInterface */
    private $accountRepos;
    /** @var CurrencyRepositoryInterface */
    private $currencyRepos;
    /** @var JournalRepositoryInterface */
    private $journalRepos;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->accountCurrencies = [];
        $this->accountRepos      = app(AccountRepositoryInterface::class);
        $this->currencyRepos     = app(CurrencyRepositoryInterface::class);
        $this->journalRepos      = app(JournalRepositoryInterface::class);

        $start = microtime(true);
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->warn('This command has already been executed.');

            return 0;
        }

        $this->updateTransferCurrencies();
        $this->updateOtherJournalsCurrencies();
        $this->markAsExecuted();
        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Verified and fixed transaction currencies in %s seconds.', $end));

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
     * @param TransactionJournal $journal
     *
     * @return Transaction|null
     */
    private function getFirstAssetTransaction(TransactionJournal $journal): ?Transaction
    {
        $result = $journal->transactions->first(
            function (Transaction $transaction) {
                return AccountType::ASSET === $transaction->account->accountType->type;
            }
        );

        return $result;
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
     * This method makes sure that the transaction journal uses the currency given in the transaction.
     *
     * @param TransactionJournal $journal
     * @param Transaction        $transaction
     */
    private function updateJournalCurrency(TransactionJournal $journal, Transaction $transaction): void
    {
        $currency     = $this->getCurrency($transaction->account);
        $currencyCode = $journal->transactionCurrency->code ?? '(nothing)';

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
                    $currencyCode
                )
            );
            $journal->transaction_currency_id = $currency->id;
            $journal->save();
        }
    }

    /**
     * @param TransactionJournal $journal
     */
    private function updateOtherJournalCurrency(TransactionJournal $journal): void
    {
        $transaction = $this->getFirstAssetTransaction($journal);
        if (null === $transaction) {
            return;
        }
        /** @var Account $account */
        $account  = $transaction->account;
        $currency = $this->getCurrency($account);
        if (null === $currency) {
            return;
        }

        $journal->transactions->each(
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
    private function updateOtherJournalsCurrencies(): void
    {
        $set = TransactionJournal
            ::leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
            ->whereNotIn('transaction_types.type', [TransactionType::TRANSFER])
            ->with(['transactions', 'transactions.account', 'transactions.account.accountType'])
            ->get(['transaction_journals.*']);

        /** @var TransactionJournal $journal */
        foreach ($set as $journal) {
            $this->updateOtherJournalCurrency($journal);
        }
    }

    /**
     * This method makes sure that the transaction uses the same currency as the source account does.
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
    private function updateTransactionCurrency(TransactionJournal $journal, Transaction $source, Transaction $destination): void
    {
        $user          = $journal->user;
        $sourceAccount = $source->account;
        $destAccount   = $destination->account;
        $this->accountRepos->setUser($user);
        $this->journalRepos->setUser($user);
        $this->currencyRepos->setUser($user);

        $sourceAccountCurrency = $this->getCurrency($sourceAccount);
        $destAccountCurrency   = $this->getCurrency($destAccount);
        if (null === $sourceAccountCurrency) {
            Log::error(sprintf('Account #%d ("%s") must have currency preference but has none.', $sourceAccount->id, $sourceAccount->name));

            return;
        }

        // has no currency ID? Must have, so fill in using account preference:
        if (null === $source->transaction_currency_id) {
            $source->transaction_currency_id = (int)$sourceAccountCurrency->id;
            Log::debug(sprintf('Transaction #%d has no currency setting, now set to %s', $source->id, $sourceAccountCurrency->code));
            $source->save();
        }

        // does not match the source account (see above)? Can be fixed
        // when mismatch in transaction and NO foreign amount is set:
        if (!((int)$source->transaction_currency_id === (int)$sourceAccountCurrency->id) && null === $source->foreign_amount) {
            Log::debug(
                sprintf(
                    'Transaction #%d has a currency setting #%d that should be #%d. Amount remains %s, currency is changed.',
                    $source->id,
                    $source->transaction_currency_id,
                    $sourceAccountCurrency->id,
                    $source->amount
                )
            );
            $source->transaction_currency_id = (int)$sourceAccountCurrency->id;
            $source->save();
        }


        if (null === $destAccountCurrency) {
            Log::error(sprintf('Account #%d ("%s") must have currency preference but has none.', $destAccount->id, $destAccount->name));

            return;
        }

        // if the destination account currency is the same, both foreign_amount and foreign_currency_id must be NULL for both transactions:
        if ((int)$destAccountCurrency->id === (int)$sourceAccountCurrency->id) {
            // update both transactions to match:
            $source->foreign_amount           = null;
            $source->foreign_currency_id      = null;
            $destination->foreign_amount      = null;
            $destination->foreign_currency_id = null;
            $source->save();
            $destination->save();
            Log::debug(
                sprintf(
                    'Currency for account "%s" is %s, and currency for account "%s" is also
             %s, so %s #%d (#%d and #%d) has been verified to be to %s exclusively.',
                    $destAccount->name, $destAccountCurrency->code,
                    $sourceAccount->name, $sourceAccountCurrency->code,
                    $journal->transactionType->type, $journal->id,
                    $source->id, $destination->id, $sourceAccountCurrency->code
                )
            );

            return;
        }

        // if destination account currency is different, both transactions must have this currency as foreign currency id.
        if (!((int)$destAccountCurrency->id === (int)$sourceAccountCurrency->id)) {
            $source->foreign_currency_id      = $destAccountCurrency->id;
            $destination->foreign_currency_id = $destAccountCurrency->id;
            $source->save();
            $destination->save();
            Log::debug(sprintf('Verified foreign currency ID of transaction #%d and #%d', $source->id, $destination->id));
        }

        // if foreign amount of one is null and the other is not, use this to restore:
        if (null === $source->foreign_amount && null !== $destination->foreign_amount) {
            $source->foreign_amount = bcmul((string)$destination->foreign_amount, '-1');
            $source->save();
            Log::debug(sprintf('Restored foreign amount of source transaction (1) #%d to %s', $source->id, $source->foreign_amount));
        }

        // if foreign amount of one is null and the other is not, use this to restore (other way around)
        if (null === $destination->foreign_amount && null !== $destination->foreign_amount) {
            $destination->foreign_amount = bcmul((string)$destination->foreign_amount, '-1');
            $destination->save();
            Log::debug(sprintf('Restored foreign amount of destination transaction (2) #%d to %s', $destination->id, $destination->foreign_amount));
        }

        // when both are zero, try to grab it from journal:
        if (null === $source->foreign_amount && null === $destination->foreign_amount) {
            $foreignAmount = $this->journalRepos->getMetaField($journal, 'foreign_amount');
            if (null === $foreignAmount) {
                Log::debug(sprintf('Journal #%d has missing foreign currency data, forced to do 1:1 conversion :(.', $source->transaction_journal_id));
                $source->foreign_amount      = $source->amount;
                $destination->foreign_amount = $destination->amount;
                $source->save();
                $destination->save();

                return;
            }
            $foreignPositive = app('steam')->positive((string)$foreignAmount);
            Log::debug(
                sprintf(
                    'Journal #%d has missing foreign currency info, try to restore from meta-data ("%s").',
                    $source->transaction_journal_id,
                    $foreignAmount
                )
            );
            $source->foreign_amount      = bcmul($foreignPositive, '-1');
            $destination->foreign_amount = $foreignPositive;
            $source->save();
            $destination->save();
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
    private function updateTransferCurrencies(): void
    {
        $set = TransactionJournal
            ::leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
            ->where('transaction_types.type', TransactionType::TRANSFER)
            ->with(['user', 'transactionType', 'transactionCurrency', 'transactions', 'transactions.account'])
            ->get(['transaction_journals.*']);

        /** @var TransactionJournal $journal */
        foreach ($set as $journal) {
            $this->updateTransferCurrency($journal);
        }
    }

    /**
     * @param TransactionJournal $transfer
     */
    private function updateTransferCurrency(TransactionJournal $transfer): void
    {
        $sourceTransaction = $this->getSourceTransaction($transfer);
        $destTransaction   = $this->getDestinationTransaction($transfer);

        if (null === $sourceTransaction) {
            $this->info(sprintf('Source transaction for journal #%d is null.', $transfer->id));

            return;
        }
        if (null === $destTransaction) {
            $this->info(sprintf('Destination transaction for journal #%d is null.', $transfer->id));

            return;
        }

        $this->updateTransactionCurrency($transfer, $sourceTransaction, $destTransaction);
        $this->updateJournalCurrency($transfer, $sourceTransaction);
    }
}