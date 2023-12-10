<?php
/**
 * FixAccountTypes.php
 * Copyright (c) 2020 james@firefly-iii.org
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

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use JsonException;

/**
 * Class FixAccountTypes
 */
class FixAccountTypes extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Make sure all journals have the correct from/to account types.';
    protected $signature   = 'firefly-iii:fix-account-types';
    private int            $count;
    private array          $expected;
    private AccountFactory $factory;

    /**
     * Execute the console command.
     *
     * @return int
     * @throws FireflyException|JsonException
     */
    public function handle(): int
    {
        $this->stupidLaravel();
        $this->factory  = app(AccountFactory::class);
        $this->expected = config('firefly.source_dests');
        $expected       = config('firefly.source_dests');

        $query = TransactionJournal::leftJoin('transaction_types', 'transaction_journals.transaction_type_id', '=', 'transaction_types.id')
                                   ->leftJoin(
                                       'transactions as source',
                                       static function (JoinClause $join) {
                                           $join->on('transaction_journals.id', '=', 'source.transaction_journal_id')->where('source.amount', '<', 0);
                                       }
                                   )
                                   ->leftJoin(
                                       'transactions as destination',
                                       static function (JoinClause $join) {
                                           $join->on('transaction_journals.id', '=', 'destination.transaction_journal_id')->where('destination.amount', '>', 0);
                                       }
                                   )
                                   ->leftJoin('accounts as source_account', 'source.account_id', '=', 'source_account.id')
                                   ->leftJoin('accounts as destination_account', 'destination.account_id', '=', 'destination_account.id')
                                   ->leftJoin('account_types as source_account_type', 'source_account.account_type_id', '=', 'source_account_type.id')
                                   ->leftJoin('account_types as destination_account_type', 'destination_account.account_type_id', '=', 'destination_account_type.id');

        // list all valid combinations, those are allowed. So we select those which are broken.
        $query->where(static function (Builder $q) use ($expected) {
            foreach ($expected as $transactionType => $info) {
                foreach ($info as $source => $destinations) {
                    foreach ($destinations as $destination) {
                        $q->whereNot(static function (Builder $q1) use ($transactionType, $source, $destination) {
                            $q1->where('transaction_types.type', $transactionType);
                            $q1->where('source_account_type.type', $source);
                            $q1->where('destination_account_type.type', $destination);
                        });
                    }
                }
            }
        });

        $resultSet = $query->get(
            [
                'transaction_journals.id',
                //'transaction_type_id as type_id',
                'transaction_types.type as journal_type',
                //'source.id as source_transaction_id',
                //'source_account.id as source_account_id',
                //'source_account_type.id as source_account_type_id',
                'source_account_type.type as source_account_type',
                //'destination.id as destination_transaction_id',
                //'destination_account.id as destination_account_id',
                //'destination_account_type.id as destination_account_type_id',
                'destination_account_type.type as destination_account_type',
            ]
        );
        if ($resultSet->count() > 0) {
            $this->friendlyLine(sprintf('Found %d journals that need to be fixed.', $resultSet->count()));
            foreach ($resultSet as $entry) {
                app('log')->debug(sprintf('Now fixing journal #%d', $entry->id));
                $journal = TransactionJournal::find($entry->id);
                if (null !== $journal) {
                    $this->inspectJournal($journal);
                }
            }
        }
        if (0 === $this->count) {
            $this->friendlyPositive('All account types are OK');
        }
        if (0 !== $this->count) {
            app('log')->debug(sprintf('%d journals had to be fixed.', $this->count));
            $this->friendlyInfo(sprintf('Acted on %d transaction(s)', $this->count));
        }

        return 0;
    }

    /**
     * @return void
     */
    private function stupidLaravel(): void
    {
        $this->count = 0;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @throws FireflyException
     * @throws JsonException
     */
    private function inspectJournal(TransactionJournal $journal): void
    {
        app('log')->debug(sprintf('Now inspecting journal #%d', $journal->id));
        $transactions = $journal->transactions()->count();
        if (2 !== $transactions) {
            app('log')->debug(sprintf('Journal has %d transactions, so can\'t fix.', $transactions));
            $this->friendlyError(sprintf('Cannot inspect transaction journal #%d because it has %d transaction(s) instead of 2.', $journal->id, $transactions));

            return;
        }
        $type              = $journal->transactionType->type;
        $sourceTransaction = $this->getSourceTransaction($journal);
        $destTransaction   = $this->getDestinationTransaction($journal);
        $sourceAccount     = $sourceTransaction->account;
        $sourceAccountType = $sourceAccount->accountType->type;
        $destAccount       = $destTransaction->account;
        $destAccountType   = $destAccount->accountType->type;

        if (!array_key_exists($type, $this->expected)) {
            app('log')->info(sprintf('No source/destination info for transaction type %s.', $type));
            $this->friendlyError(sprintf('No source/destination info for transaction type %s.', $type));

            return;
        }
        if (!array_key_exists($sourceAccountType, $this->expected[$type])) {
            app('log')->debug(sprintf('[a] Going to fix journal #%d', $journal->id));
            $this->fixJournal($journal, $type, $sourceTransaction, $destTransaction);

            return;
        }
        $expectedTypes = $this->expected[$type][$sourceAccountType];
        if (!in_array($destAccountType, $expectedTypes, true)) {
            app('log')->debug(sprintf('[b] Going to fix journal #%d', $journal->id));
            $this->fixJournal($journal, $type, $sourceTransaction, $destTransaction);
        }
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
     * @return Transaction
     */
    private function getDestinationTransaction(TransactionJournal $journal): Transaction
    {
        return $journal->transactions->firstWhere('amount', '>', 0);
    }

    /**
     * @param TransactionJournal $journal
     * @param string             $type
     * @param Transaction        $source
     * @param Transaction        $dest
     *
     * @throws FireflyException
     * @throws JsonException
     */
    private function fixJournal(TransactionJournal $journal, string $type, Transaction $source, Transaction $dest): void
    {
        app('log')->debug(sprintf('Going to fix journal #%d', $journal->id));
        $this->count++;
        // variables:
        $combination = sprintf('%s%s%s', $type, $source->account->accountType->type, $dest->account->accountType->type);
        app('log')->debug(sprintf('Combination is "%s"', $combination));
        switch ($combination) {
            case sprintf('%s%s%s', TransactionType::TRANSFER, AccountType::ASSET, AccountType::LOAN):
            case sprintf('%s%s%s', TransactionType::TRANSFER, AccountType::ASSET, AccountType::DEBT):
            case sprintf('%s%s%s', TransactionType::TRANSFER, AccountType::ASSET, AccountType::MORTGAGE):
                // from an asset to a liability should be a withdrawal:
                $withdrawal = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
                $journal->transactionType()->associate($withdrawal);
                $journal->save();
                $message = sprintf('Converted transaction #%d from a transfer to a withdrawal.', $journal->id);
                $this->friendlyInfo($message);
                app('log')->debug($message);
                // check it again:
                $this->inspectJournal($journal);
                return;
            case sprintf('%s%s%s', TransactionType::TRANSFER, AccountType::LOAN, AccountType::ASSET):
            case sprintf('%s%s%s', TransactionType::TRANSFER, AccountType::DEBT, AccountType::ASSET):
            case sprintf('%s%s%s', TransactionType::TRANSFER, AccountType::MORTGAGE, AccountType::ASSET):
                // from a liability to an asset should be a deposit.
                $deposit = TransactionType::whereType(TransactionType::DEPOSIT)->first();
                $journal->transactionType()->associate($deposit);
                $journal->save();
                $message = sprintf('Converted transaction #%d from a transfer to a deposit.', $journal->id);
                $this->friendlyInfo($message);
                app('log')->debug($message);
                // check it again:
                $this->inspectJournal($journal);

                return;
            case sprintf('%s%s%s', TransactionType::WITHDRAWAL, AccountType::ASSET, AccountType::REVENUE):
                // withdrawals with a revenue account as destination instead of an expense account.
                $this->factory->setUser($journal->user);
                $oldDest = $dest->account;
                $result  = $this->factory->findOrCreate($dest->account->name, AccountType::EXPENSE);
                $dest->account()->associate($result);
                $dest->save();
                $message = sprintf(
                    'Transaction journal #%d, destination account changed from #%d ("%s") to #%d ("%s").',
                    $journal->id,
                    $oldDest->id,
                    $oldDest->name,
                    $result->id,
                    $result->name
                );
                $this->friendlyWarning($message);
                app('log')->debug($message);
                $this->inspectJournal($journal);
                return;
            case sprintf('%s%s%s', TransactionType::DEPOSIT, AccountType::EXPENSE, AccountType::ASSET):
                // deposits with an expense account as source instead of a revenue account.
                // find revenue account.
                $this->factory->setUser($journal->user);
                $result    = $this->factory->findOrCreate($source->account->name, AccountType::REVENUE);
                $oldSource = $dest->account;
                $source->account()->associate($result);
                $source->save();
                $message = sprintf(
                    'Transaction journal #%d, source account changed from #%d ("%s") to #%d ("%s").',
                    $journal->id,
                    $oldSource->id,
                    $oldSource->name,
                    $result->id,
                    $result->name
                );
                $this->friendlyWarning($message);
                app('log')->debug($message);
                $this->inspectJournal($journal);
                return;
        }
        app('log')->debug(sprintf('Fallback to fix transaction journal #%d of type "%s".', $journal->id, $type));

        // transaction has no valid source.
        $validSources = array_keys($this->expected[$type]);
        if (!in_array($source->account->accountType->type, $validSources, true)) {
            app('log')->debug('Journal has no valid source.');
            // perhaps we can create the account of type we need:

            if (in_array(AccountTypeEnum::REVENUE->value, $validSources, true)) {
                app('log')->debug(sprintf('An account of type "%s" could be a valid source.', AccountTypeEnum::REVENUE->value));
                $this->factory->setUser($journal->user);
                $newSource = $this->factory->findOrCreate($source->account->name, AccountTypeEnum::REVENUE->value);
                $source->account()->associate($newSource);
                $source->save();
                $this->friendlyPositive(sprintf('Firefly III gave transaction #%d a new source %s: #%d ("%s").', $journal->transaction_group_id, AccountTypeEnum::REVENUE->value, $newSource->id, $newSource->name));
                app('log')->debug(sprintf('Associated account #%d with transaction #%d', $newSource->id, $source->id));
                $this->inspectJournal($journal);
                return;
            }
            if (!in_array(AccountTypeEnum::REVENUE->value, $validSources, true)) {
                app('log')->debug('This transaction type has no source we can create. Just give error.');
                $message = sprintf('The source account of %s #%d cannot be of type "%s". Firefly III cannot fix this. You may have to remove the transaction yourself.', $type, $journal->id, $source->account->accountType->type);
                $this->friendlyError($message);
                app('log')->debug($message);
            }
        }

        // transaction has no valid destination:
        $sourceType        = $source->account->accountType->type;
        $validDestinations = $this->expected[$type][$sourceType] ?? [];
        if (!in_array($dest->account->accountType->type, $validDestinations, true)) {
            app('log')->debug('Journal has no valid destination (perhaps because the source is also broken).');
            // perhaps we can create the account of type we need:
            if (in_array(AccountTypeEnum::EXPENSE->value, $validDestinations, true)) {
                app('log')->debug(sprintf('An account of type "%s" could be a valid destination.', AccountTypeEnum::EXPENSE->value));
                $this->factory->setUser($journal->user);
                $newDestination = $this->factory->findOrCreate($dest->account->name, AccountTypeEnum::EXPENSE->value);
                $dest->account()->associate($newDestination);
                $dest->save();
                $this->friendlyPositive(sprintf('Firefly III gave transaction #%d a new destination %s: #%d ("%s").', $journal->transaction_group_id, AccountTypeEnum::EXPENSE->value, $newDestination->id, $newDestination->name));
                app('log')->debug(sprintf('Associated account #%d with transaction #%d', $newDestination->id, $source->id));
                $this->inspectJournal($journal);
                return;
            }
            if (!in_array(AccountTypeEnum::EXPENSE->value, $validSources, true)) {
                app('log')->debug('This transaction type has no destination we can create. Just give error.');
                $message = sprintf('The destination account of %s #%d cannot be of type "%s". Firefly III cannot fix this. You may have to remove the transaction yourself.', $type, $journal->id, $dest->account->accountType->type);
                $this->friendlyError($message);
                app('log')->debug($message);
                return;
            }
        }
    }
}
