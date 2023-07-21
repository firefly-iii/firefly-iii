<?php
/**
 * ConvertToTransfer.php
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
use FireflyIII\Models\Account;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 *
 * Class ConvertToTransfer
 */
class ConvertToTransfer implements ActionInterface
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
            Log::error(sprintf('Cannot find journal #%d, cannot convert to transfer.', $journal['transaction_journal_id']));
            return false;
        }
        $groupCount = TransactionJournal::where('transaction_group_id', $journal['transaction_group_id'])->count();
        if ($groupCount > 1) {
            Log::error(sprintf('Group #%d has more than one transaction in it, cannot convert to transfer.', $journal['transaction_group_id']));
            return false;
        }

        $type      = $object->transactionType->type;
        $user      = $object->user;
        $journalId = (int)$object->id;
        if (TransactionType::TRANSFER === $type) {
            Log::error(
                sprintf('Journal #%d is already a transfer so cannot be converted (rule #%d).', $object->id, $this->action->rule_id)
            );

            return false;
        }

        // find the asset account in the action value.
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $repository->setUser($user);
        $opposing     = null;
        $expectedType = null;
        if (TransactionType::WITHDRAWAL === $type) {
            $expectedType = $this->getSourceType($journalId);
            // Withdrawal? Replace destination with account with same type as source.
        }
        if (TransactionType::DEPOSIT === $type) {
            $expectedType = $this->getDestinationType($journalId);
            // Deposit? Replace source with account with same type as destination.
        }
        $opposing = $repository->findByName($this->action->action_value, [$expectedType]);

        if (null === $opposing) {
            Log::error(
                sprintf(
                    'Journal #%d cannot be converted because no valid %s account with name "%s" exists (rule #%d).',
                    $expectedType,
                    $journalId,
                    $this->action->action_value,
                    $this->action->rule_id
                )
            );

            return false;
        }

        if (TransactionType::WITHDRAWAL === $type) {
            Log::debug('Going to transform a withdrawal to a transfer.');
            try {
                $res = $this->convertWithdrawalArray($object, $opposing);
            } catch (FireflyException $e) {
                Log::debug('Could not convert withdrawal to transfer.');
                Log::error($e->getMessage());
                return false;
            }
            event(new TriggeredAuditLog($this->action->rule, $object, 'update_transaction_type', TransactionType::WITHDRAWAL, TransactionType::TRANSFER));
            return $res;
        }
        if (TransactionType::DEPOSIT === $type) {
            Log::debug('Going to transform a deposit to a transfer.');
            try {
                $res = $this->convertDepositArray($object, $opposing);
            } catch (FireflyException $e) {
                Log::debug('Could not convert deposit to transfer.');
                Log::error($e->getMessage());
                return false;
            }
            event(new TriggeredAuditLog($this->action->rule, $object, 'update_transaction_type', TransactionType::DEPOSIT, TransactionType::TRANSFER));
            return $res;
        }

        return false;
    }

    /**
     * @param int $journalId
     *
     * @return string
     */
    private function getSourceType(int $journalId): string
    {
        /** @var TransactionJournal $journal */
        $journal = TransactionJournal::find($journalId);
        if (null === $journal) {
            Log::error(sprintf('Journal #%d does not exist. Cannot convert to transfer.', $journalId));
            return '';
        }
        return (string)$journal->transactions()->where('amount', '<', 0)->first()?->account?->accountType?->type;
    }

    /**
     * @param int $journalId
     *
     * @return string
     */
    private function getDestinationType(int $journalId): string
    {
        /** @var TransactionJournal $journal */
        $journal = TransactionJournal::find($journalId);
        if (null === $journal) {
            Log::error(sprintf('Journal #%d does not exist. Cannot convert to transfer.', $journalId));
            return '';
        }
        return (string)$journal->transactions()->where('amount', '>', 0)->first()?->account?->accountType?->type;
    }

    /**
     * A withdrawal is from Asset to Expense.
     * We replace the Expense with another asset.
     * So this replaces the destination
     *
     * @param TransactionJournal $journal
     * @param Account            $opposing
     *
     * @return bool
     * @throws FireflyException
     */
    private function convertWithdrawalArray(TransactionJournal $journal, Account $opposing): bool
    {
        $sourceAccount = $this->getSourceAccount($journal);
        if ((int)$sourceAccount->id === (int)$opposing->id) {
            Log::error(
                vsprintf(
                    'Journal #%d has already has "%s" as a source asset. ConvertToTransfer failed. (rule #%d).',
                    [$journal->id, $opposing->name, $this->action->rule_id]
                )
            );

            return false;
        }

        // update destination transaction:
        DB::table('transactions')
          ->where('transaction_journal_id', '=', $journal->id)
          ->where('amount', '>', 0)
          ->update(['account_id' => $opposing->id]);

        // change transaction type of journal:
        $newType = TransactionType::whereType(TransactionType::TRANSFER)->first();

        DB::table('transaction_journals')
          ->where('id', '=', $journal->id)
          ->update(['transaction_type_id' => $newType->id, 'bill_id' => null]);

        Log::debug('Converted withdrawal to transfer.');

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
     * A deposit is from Revenue to Asset.
     * We replace the Revenue with another asset.
     *
     * @param TransactionJournal $journal
     * @param Account            $opposing
     *
     * @return bool
     * @throws FireflyException
     */
    private function convertDepositArray(TransactionJournal $journal, Account $opposing): bool
    {
        $destAccount = $this->getDestinationAccount($journal);
        if ((int)$destAccount->id === (int)$opposing->id) {
            Log::error(
                vsprintf(
                    'Journal #%d has already has "%s" as a destination asset. ConvertToTransfer failed. (rule #%d).',
                    [$journal->id, $opposing->name, $this->action->rule_id]
                )
            );

            return false;
        }

        // update source transaction:
        DB::table('transactions')
          ->where('transaction_journal_id', '=', $journal->id)
          ->where('amount', '<', 0)
          ->update(['account_id' => $opposing->id]);

        // change transaction type of journal:
        $newType = TransactionType::whereType(TransactionType::TRANSFER)->first();

        DB::table('transaction_journals')
          ->where('id', '=', $journal->id)
          ->update(['transaction_type_id' => $newType->id, 'bill_id' => null]);

        Log::debug('Converted deposit to transfer.');

        return true;
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
}
