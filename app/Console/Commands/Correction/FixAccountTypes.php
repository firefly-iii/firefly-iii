<?php
/**
 * FixAccountTypes.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Illuminate\Console\Command;

/**
 * Class FixAccountTypes
 */
class FixAccountTypes extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make sure all journals have the correct from/to account types.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:fix-account-types';
    /** @var array */
    private $expected;
    /** @var AccountFactory */
    private $factory;
    /** @var array */
    private $fixable;
    /** @var int */
    private $count;

    /**
     * Execute the console command.
     *
     * @return int
     * @throws FireflyException
     */
    public function handle(): int
    {
        $this->stupidLaravel();
        $start         = microtime(true);
        $this->factory = app(AccountFactory::class);
        // some combinations can be fixed by this script:
        $this->fixable = [
            // transfers from asset to liability and vice versa
            sprintf('%s%s%s', TransactionType::TRANSFER, AccountType::ASSET, AccountType::LOAN),
            sprintf('%s%s%s', TransactionType::TRANSFER, AccountType::ASSET, AccountType::DEBT),
            sprintf('%s%s%s', TransactionType::TRANSFER, AccountType::ASSET, AccountType::MORTGAGE),
            sprintf('%s%s%s', TransactionType::TRANSFER, AccountType::LOAN, AccountType::ASSET),
            sprintf('%s%s%s', TransactionType::TRANSFER, AccountType::DEBT, AccountType::ASSET),
            sprintf('%s%s%s', TransactionType::TRANSFER, AccountType::MORTGAGE, AccountType::ASSET),

            // withdrawals with a revenue account as destination instead of an expense account.
            sprintf('%s%s%s', TransactionType::WITHDRAWAL, AccountType::ASSET, AccountType::REVENUE),

            // deposits with an expense account as source instead of a revenue account.
            sprintf('%s%s%s', TransactionType::DEPOSIT, AccountType::EXPENSE, AccountType::ASSET),
        ];


        $this->expected = config('firefly.source_dests');
        $journals       = TransactionJournal::with(['TransactionType', 'transactions', 'transactions.account', 'transactions.account.accounttype'])->get();
        foreach ($journals as $journal) {
            $this->inspectJournal($journal);
        }
        if (0 === $this->count) {
            $this->info('All account types are OK!');
        }
        if (0 !== $this->count) {
            $this->info(sprintf('Acted on %d transaction(s)!', $this->count));
        }

        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Verifying account types took %s seconds', $end));

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
        $this->count = 0;
    }

    /**
     * @param TransactionJournal $journal
     * @param string $type
     * @param Transaction $source
     * @param Transaction $dest
     * @throws FireflyException
     */
    private function fixJournal(TransactionJournal $journal, string $type, Transaction $source, Transaction $dest): void
    {
        $this->count++;
        // variables:
        $combination = sprintf('%s%s%s', $type, $source->account->accountType->type, $dest->account->accountType->type);

        switch ($combination) {
            case sprintf('%s%s%s', TransactionType::TRANSFER, AccountType::ASSET, AccountType::LOAN):
            case sprintf('%s%s%s', TransactionType::TRANSFER, AccountType::ASSET, AccountType::DEBT):
            case sprintf('%s%s%s', TransactionType::TRANSFER, AccountType::ASSET, AccountType::MORTGAGE):
                // from an asset to a liability should be a withdrawal:
                $withdrawal = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
                $journal->transactionType()->associate($withdrawal);
                $journal->save();
                $this->info(sprintf('Converted transaction #%d from a transfer to a withdrawal.', $journal->id));
                // check it again:
                $this->inspectJournal($journal);
                break;
            case sprintf('%s%s%s', TransactionType::TRANSFER, AccountType::LOAN, AccountType::ASSET):
            case sprintf('%s%s%s', TransactionType::TRANSFER, AccountType::DEBT, AccountType::ASSET):
            case sprintf('%s%s%s', TransactionType::TRANSFER, AccountType::MORTGAGE, AccountType::ASSET):
                // from a liability to an asset should be a deposit.
                $deposit = TransactionType::whereType(TransactionType::DEPOSIT)->first();
                $journal->transactionType()->associate($deposit);
                $journal->save();
                $this->info(sprintf('Converted transaction #%d from a transfer to a deposit.', $journal->id));
                // check it again:
                $this->inspectJournal($journal);

                break;
            case sprintf('%s%s%s', TransactionType::WITHDRAWAL, AccountType::ASSET, AccountType::REVENUE):
                // withdrawals with a revenue account as destination instead of an expense account.
                $this->factory->setUser($journal->user);
                $oldDest = $dest->account;
                $result  = $this->factory->findOrCreate($dest->account->name, AccountType::EXPENSE);
                $dest->account()->associate($result);
                $dest->save();
                $this->info(
                    sprintf(
                        'Transaction journal #%d, destination account changed from #%d ("%s") to #%d ("%s").', $journal->id,
                        $oldDest->id, $oldDest->name,
                        $result->id, $result->name
                    )
                );
                $this->inspectJournal($journal);
                break;
            case sprintf('%s%s%s', TransactionType::DEPOSIT, AccountType::EXPENSE, AccountType::ASSET):
                // deposits with an expense account as source instead of a revenue account.
                // find revenue account.
                $this->factory->setUser($journal->user);
                $result    = $this->factory->findOrCreate($source->account->name, AccountType::REVENUE);
                $oldSource = $dest->account;
                $source->account()->associate($result);
                $source->save();
                $this->info(
                    sprintf(
                        'Transaction journal #%d, source account changed from #%d ("%s") to #%d ("%s").', $journal->id,
                        $oldSource->id, $oldSource->name,
                        $result->id, $result->name
                    )
                );
                $this->inspectJournal($journal);
                break;
            default:
                $this->info(sprintf('The source account of %s #%d cannot be of type "%s".', $type, $journal->id, $source->account->accountType->type));
                $this->info(sprintf('The destination account of %s #%d cannot be of type "%s".', $type, $journal->id, $dest->account->accountType->type));

                break;

        }

    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Transaction
     */
    private function getDestinationTransaction(TransactionJournal $journal): Transaction
    {
        return $journal->transactions->firstWhere('amount', '>', 0);
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Transaction
     */
    private function getSourceTransaction(TransactionJournal $journal): Transaction
    {
        return $journal->transactions->firstWhere('amount', '<', 0);
    }

    /**
     * @param TransactionJournal $journal
     *
     * @throws FireflyException
     */
    private function inspectJournal(TransactionJournal $journal): void
    {
        $count = $journal->transactions()->count();
        if (2 !== $count) {
            $this->info(sprintf('Cannot inspect transaction journal #%d because it has %d transaction(s) instead of 2.', $journal->id, $count));

            return;
        }
        $type              = $journal->transactionType->type;
        $sourceTransaction = $this->getSourceTransaction($journal);
        $sourceAccount     = $sourceTransaction->account;
        $sourceAccountType = $sourceAccount->accountType->type;
        $destTransaction   = $this->getDestinationTransaction($journal);
        $destAccount       = $destTransaction->account;
        $destAccountType   = $destAccount->accountType->type;
        if (!isset($this->expected[$type])) {
            // @codeCoverageIgnoreStart
            $this->info(sprintf('No source/destination info for transaction type %s.', $type));

            return;
            // @codeCoverageIgnoreEnd
        }
        if (!isset($this->expected[$type][$sourceAccountType])) {
            $this->fixJournal($journal, $type, $sourceTransaction, $destTransaction);

            return;
        }
        $expectedTypes = $this->expected[$type][$sourceAccountType];
        if (!in_array($destAccountType, $expectedTypes, true)) {
            $this->fixJournal($journal, $type, $sourceTransaction, $destTransaction);
        }
    }

}
