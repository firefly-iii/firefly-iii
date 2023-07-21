<?php
/**
 * ConvertToWithdrawal.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\TransactionRules\Actions;

use DB;
use FireflyIII\Events\TriggeredAuditLog;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Support\Facades\Log;
use JsonException;

/**
 *
 * Class ConvertToWithdrawal
 */
class ConvertToWithdrawal implements ActionInterface
{
    private RuleAction $action;

    /**
     * TriggerInterface constructor.
     *
     * @param RuleAction $action
     */
    public function __construct(RuleAction $action)
    {
        $this->action = $action;
    }

    /**
     * @inheritDoc
     */
    public function actOnArray(array $journal): bool
    {
        // make object from array (so the data is fresh).
        /** @var TransactionJournal|null $object */
        $object = TransactionJournal::where('user_id', $journal['user_id'])->find($journal['transaction_journal_id']);
        if (null === $object) {
            Log::error(sprintf('Cannot find journal #%d, cannot convert to withdrawal.', $journal['transaction_journal_id']));
            return false;
        }
        $groupCount = TransactionJournal::where('transaction_group_id', $journal['transaction_group_id'])->count();
        if ($groupCount > 1) {
            Log::error(sprintf('Group #%d has more than one transaction in it, cannot convert to withdrawal.', $journal['transaction_group_id']));
            return false;
        }

        $type = $object->transactionType->type;
        if (TransactionType::WITHDRAWAL === $type) {
            Log::error(sprintf('Journal #%d is already a withdrawal (rule #%d).', $journal['transaction_journal_id'], $this->action->rule_id));

            return false;
        }

        if (TransactionType::DEPOSIT === $type) {
            Log::debug('Going to transform a deposit to a withdrawal.');
            try {
                $res = $this->convertDepositArray($object);
            } catch (JsonException | FireflyException $e) {
                Log::debug('Could not convert transfer to deposit.');
                Log::error($e->getMessage());
                return false;
            }
            event(new TriggeredAuditLog($this->action->rule, $object, 'update_transaction_type', TransactionType::DEPOSIT, TransactionType::WITHDRAWAL));

            return $res;
        }
        if (TransactionType::TRANSFER === $type) {
            Log::debug('Going to transform a transfer to a withdrawal.');

            try {
                $res = $this->convertTransferArray($object);
            } catch (JsonException | FireflyException $e) {
                Log::debug('Could not convert transfer to deposit.');
                Log::error($e->getMessage());
                return false;
            }
            event(new TriggeredAuditLog($this->action->rule, $object, 'update_transaction_type', TransactionType::TRANSFER, TransactionType::WITHDRAWAL));

            return $res;
        }

        return false;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return bool
     * @throws FireflyException
     * @throws JsonException
     */
    private function convertDepositArray(TransactionJournal $journal): bool
    {
        $user = $journal->user;
        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($user);

        $repository = app(AccountRepositoryInterface::class);
        $repository->setUser($user);

        $sourceAccount = $this->getSourceAccount($journal);
        $destAccount   = $this->getDestinationAccount($journal);

        // get the action value, or use the original source name in case the action value is empty:
        // this becomes a new or existing (expense) account, which is the destination of the new withdrawal.
        $opposingName = '' === $this->action->action_value ? $sourceAccount->name : $this->action->action_value;
        // we check all possible source account types if one exists:
        $validTypes      = config('firefly.expected_source_types.destination.Withdrawal');
        $opposingAccount = $repository->findByName($opposingName, $validTypes);
        if (null === $opposingAccount) {
            $opposingAccount = $factory->findOrCreate($opposingName, AccountType::EXPENSE);
        }

        Log::debug(sprintf('ConvertToWithdrawal. Action value is "%s", expense name is "%s"', $this->action->action_value, $opposingName));

        // update source transaction(s) to be the original destination account
        DB::table('transactions')
          ->where('transaction_journal_id', '=', $journal->id)
          ->where('amount', '<', 0)
          ->update(['account_id' => $destAccount->id]);

        // update destination transaction(s) to be new expense account.
        DB::table('transactions')
          ->where('transaction_journal_id', '=', $journal->id)
          ->where('amount', '>', 0)
          ->update(['account_id' => $opposingAccount->id]);

        // change transaction type of journal:
        $newType = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        DB::table('transaction_journals')
          ->where('id', '=', $journal->id)
          ->update(['transaction_type_id' => $newType->id]);

        Log::debug('Converted deposit to withdrawal.');

        return true;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Account
     * @throws FireflyException
     */
    private function getSourceAccount(TransactionJournal $journal): Account
    {
        /** @var Transaction|null $sourceTransaction */
        $sourceTransaction = $journal->transactions()->where('amount', '<', 0)->first();
        if (null === $sourceTransaction) {
            throw new FireflyException(sprintf('Cannot find source transaction for journal #%d', $journal->id));
        }
        return $sourceTransaction->account;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Account
     * @throws FireflyException
     */
    private function getDestinationAccount(TransactionJournal $journal): Account
    {
        /** @var Transaction|null $destAccount */
        $destAccount = $journal->transactions()->where('amount', '>', 0)->first();
        if (null === $destAccount) {
            throw new FireflyException(sprintf('Cannot find destination transaction for journal #%d', $journal->id));
        }
        return $destAccount->account;
    }

    /**
     * Input is a transfer from A to B.
     * Output is a withdrawal from A to C.
     *
     * @param TransactionJournal $journal
     *
     * @return bool
     * @throws FireflyException
     * @throws JsonException
     */
    private function convertTransferArray(TransactionJournal $journal): bool
    {
        // find or create expense account.
        $user = $journal->user;
        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($user);

        $repository = app(AccountRepositoryInterface::class);
        $repository->setUser($user);

        $destAccount = $this->getDestinationAccount($journal);

        // get the action value, or use the original source name in case the action value is empty:
        // this becomes a new or existing (expense) account, which is the destination of the new withdrawal.
        $opposingName = '' === $this->action->action_value ? $destAccount->name : $this->action->action_value;
        // we check all possible source account types if one exists:
        $validTypes      = config('firefly.expected_source_types.destination.Withdrawal');
        $opposingAccount = $repository->findByName($opposingName, $validTypes);
        if (null === $opposingAccount) {
            $opposingAccount = $factory->findOrCreate($opposingName, AccountType::EXPENSE);
        }

        Log::debug(sprintf('ConvertToWithdrawal. Action value is "%s", destination name is "%s"', $this->action->action_value, $opposingName));

        // update destination transaction(s) to be new expense account.
        DB::table('transactions')
          ->where('transaction_journal_id', '=', $journal->id)
          ->where('amount', '>', 0)
          ->update(['account_id' => $opposingAccount->id]);

        // change transaction type of journal:
        $newType = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        DB::table('transaction_journals')
          ->where('id', '=', $journal->id)
          ->update(['transaction_type_id' => $newType->id]);

        Log::debug('Converted transfer to withdrawal.');

        return true;
    }
}
