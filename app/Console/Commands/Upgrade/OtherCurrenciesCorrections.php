<?php
/**
 * OtherCurrenciesCorrections.php
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

/**
 * Class OtherCurrenciesCorrections
 */
class OtherCurrenciesCorrections extends Command
{

    public const CONFIG_NAME = '4780_other_currencies';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all journal currency information.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:other-currencies {--F|force : Force the execution of this command.}';
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

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->stupidLaravel();
        $start = microtime(true);
        // @codeCoverageIgnoreStart
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->warn('This command has already been executed.');

            return 0;
        }
        // @codeCoverageIgnoreEnd

        $this->updateOtherJournalsCurrencies();
        $this->markAsExecuted();

        $this->line(sprintf('Verified %d transaction(s) and journal(s).', $this->count));
        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Verified and fixed transaction currencies in %s seconds.', $end));

        return 0;
    }

    /**
     * Laravel will execute ALL __construct() methods for ALL commands whenever a SINGLE command is
     * executed. This leads to noticeable slow-downs and class calls. To prevent this, this method should
     * be called from the handle method instead of using the constructor to initialize the command.
     *
     * @codeCoverageIgnore
     */
    private function stupidLaravel(): void
    {
        $this->count             = 0;
        $this->accountCurrencies = [];
        $this->accountRepos      = app(AccountRepositoryInterface::class);
        $this->currencyRepos     = app(CurrencyRepositoryInterface::class);
        $this->journalRepos      = app(JournalRepositoryInterface::class);
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
            return null; // @codeCoverageIgnore
        }
        if (isset($this->accountCurrencies[$accountId]) && $this->accountCurrencies[$accountId] instanceof TransactionCurrency) {
            return $this->accountCurrencies[$accountId]; // @codeCoverageIgnore
        }
        $currencyId = (int)$this->accountRepos->getMetaValue($account, 'currency_id');
        $result     = $this->currencyRepos->findNull($currencyId);
        if (null === $result) {
            // @codeCoverageIgnoreStart
            $this->accountCurrencies[$accountId] = 0;

            return null;
            // @codeCoverageIgnoreEnd
        }
        $this->accountCurrencies[$accountId] = $result;

        return $result;


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
     * @param TransactionJournal $journal
     */
    private function updateJournalCurrency(TransactionJournal $journal): void
    {
        $this->accountRepos->setUser($journal->user);
        $this->journalRepos->setUser($journal->user);
        $this->currencyRepos->setUser($journal->user);

        $leadTransaction = $this->getLeadTransaction($journal);

        if (null === $leadTransaction) {
            // @codeCoverageIgnoreStart
            $this->error(sprintf('Could not reliably determine which transaction is in the lead for transaction journal #%d.', $journal->id));

            return;
            // @codeCoverageIgnoreEnd
        }

        /** @var Account $account */
        $account  = $leadTransaction->account;
        $currency = $this->getCurrency($account);
        if (null === $currency) {
            // @codeCoverageIgnoreStart
            $this->error(sprintf('Account #%d ("%s") has no currency preference, so transaction journal #%d can\'t be corrected',
                                 $account->id, $account->name, $journal->id));
            $this->count++;

            return;
            // @codeCoverageIgnoreEnd
        }
        // fix each transaction:
        $journal->transactions->each(
            static function (Transaction $transaction) use ($currency) {
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
        $this->count++;
        $journal->save();
    }

    /**
     * This routine verifies that withdrawals, deposits and opening balances have the correct currency settings for
     * the accounts they are linked to.
     *
     * Both source and destination must match the respective currency preference of the related asset account.
     * So FF3 must verify all transactions.
     *
     */
    private function updateOtherJournalsCurrencies(): void
    {
        $set =
            $this->journalRepos->getAllJournals(
                [
                    TransactionType::WITHDRAWAL,
                    TransactionType::DEPOSIT,
                    TransactionType::OPENING_BALANCE,
                    TransactionType::RECONCILIATION,
                ]
            );

        /** @var TransactionJournal $journal */
        foreach ($set as $journal) {
            $this->updateJournalCurrency($journal);
        }
    }

    /**
     * Gets the transaction that determines the transaction that "leads" and will determine
     * the currency to be used by all transactions, and the journal itself.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @param TransactionJournal $journal
     * @return Transaction|null
     */
    private function getLeadTransaction(TransactionJournal $journal): ?Transaction
    {
        /** @var Transaction $lead */
        $lead = null;
        switch ($journal->transactionType->type) {
            case TransactionType::WITHDRAWAL:
                $lead = $journal->transactions()->where('amount', '<', 0)->first();
                break;
            case TransactionType::DEPOSIT:
                $lead = $journal->transactions()->where('amount', '>', 0)->first();
                break;
            case TransactionType::OPENING_BALANCE:
                // whichever isn't an initial balance account:
                $lead = $journal->transactions()
                                ->leftJoin('accounts', 'transactions.account_id', '=', 'accounts.id')
                                ->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id')
                                ->where('account_types.type', '!=', AccountType::INITIAL_BALANCE)
                                ->first(['transactions.*']);
                break;
            case TransactionType::RECONCILIATION:
                // whichever isn't the reconciliation account:
                $lead = $journal->transactions()
                                ->leftJoin('accounts', 'transactions.account_id', '=', 'accounts.id')
                                ->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id')
                                ->where('account_types.type', '!=', AccountType::RECONCILIATION)
                                ->first(['transactions.*']);
                break;
        }

        return $lead;
    }
}