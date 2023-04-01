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
use FireflyIII\Models\Account;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
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
     * @param  RuleAction  $action
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
        $groupCount = TransactionJournal::where('transaction_group_id', $journal['transaction_group_id'])->count();
        if ($groupCount > 1) {
            Log::error(sprintf('Group #%d has more than one transaction in it, cannot convert to transfer.', $journal['transaction_group_id']));
            return false;
        }


        $type = $journal['transaction_type_type'];
        $user = User::find($journal['user_id']);
        if (TransactionType::TRANSFER === $type) {
            Log::error(
                sprintf('Journal #%d is already a transfer so cannot be converted (rule #%d).', $journal['transaction_journal_id'], $this->action->rule_id)
            );

            return false;
        }

        // find the asset account in the action value.
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $repository->setUser($user);
        $opposing = null;
        $expectedType = null;
        if (TransactionType::WITHDRAWAL === $type) {
            $expectedType = $this->getSourceType($journal['transaction_journal_id']);
            // Withdrawal? Replace destination with account with same type as source.
        }
        if (TransactionType::DEPOSIT === $type) {
            $expectedType = $this->getDestinationType($journal['transaction_journal_id']);
            // Deposit? Replace source with account with same type as destination.
        }
        $opposing = $repository->findByName($this->action->action_value, [$expectedType]);

        if (null === $opposing) {
            Log::error(
                sprintf(
                    'Journal #%d cannot be converted because no valid %s account with name "%s" exists (rule #%d).',
                    $expectedType,
                    $journal['transaction_journal_id'],
                    $this->action->action_value,
                    $this->action->rule_id
                )
            );

            return false;
        }
        if (TransactionType::WITHDRAWAL === $type) {
            Log::debug('Going to transform a withdrawal to a transfer.');
            $object = TransactionJournal::where('user_id', $journal['user_id'])->find($journal['transaction_journal_id']);
            event(new TriggeredAuditLog($this->action->rule, $object, 'update_transaction_type', TransactionType::WITHDRAWAL, TransactionType::TRANSFER));

            return $this->convertWithdrawalArray($journal, $opposing);
        }
        if (TransactionType::DEPOSIT === $type) {
            Log::debug('Going to transform a deposit to a transfer.');

            $object = TransactionJournal::where('user_id', $journal['user_id'])->find($journal['transaction_journal_id']);
            event(new TriggeredAuditLog($this->action->rule, $object, 'update_transaction_type', TransactionType::DEPOSIT, TransactionType::TRANSFER));

            return $this->convertDepositArray($journal, $opposing);
        }

        return false;
    }

    /**
     * A withdrawal is from Asset to Expense.
     * We replace the Expense with another asset.
     * So this replaces the destination
     *
     * @param  array  $journal
     * @param  Account  $opposing
     *
     * @return bool
     */
    private function convertWithdrawalArray(array $journal, Account $opposing): bool
    {
        if ($journal['source_account_id'] === $opposing->id) {
            Log::error(
                vsprintf(
                    'Journal #%d has already has "%s" as a source asset. ConvertToTransfer failed. (rule #%d).',
                    [$journal['transaction_journal_id'], $opposing->name, $this->action->rule_id]
                )
            );

            return false;
        }

        // update destination transaction:
        DB::table('transactions')
          ->where('transaction_journal_id', '=', $journal['transaction_journal_id'])
          ->where('amount', '>', 0)
          ->update(['account_id' => $opposing->id]);

        // change transaction type of journal:
        $newType = TransactionType::whereType(TransactionType::TRANSFER)->first();

        DB::table('transaction_journals')
          ->where('id', '=', $journal['transaction_journal_id'])
          ->update(['transaction_type_id' => $newType->id, 'bill_id' => null]);

        Log::debug('Converted withdrawal to transfer.');

        return true;
    }

    /**
     * A deposit is from Revenue to Asset.
     * We replace the Revenue with another asset.
     *
     * @param  array  $journal
     * @param  Account  $opposing
     *
     * @return bool
     */
    private function convertDepositArray(array $journal, Account $opposing): bool
    {
        if ($journal['destination_account_id'] === $opposing->id) {
            Log::error(
                vsprintf(
                    'Journal #%d has already has "%s" as a destination asset. ConvertToTransfer failed. (rule #%d).',
                    [$journal['transaction_journal_id'], $opposing->name, $this->action->rule_id]
                )
            );

            return false;
        }

        // update source transaction:
        DB::table('transactions')
          ->where('transaction_journal_id', '=', $journal['transaction_journal_id'])
          ->where('amount', '<', 0)
          ->update(['account_id' => $opposing->id]);

        // change transaction type of journal:
        $newType = TransactionType::whereType(TransactionType::TRANSFER)->first();

        DB::table('transaction_journals')
          ->where('id', '=', $journal['transaction_journal_id'])
          ->update(['transaction_type_id' => $newType->id, 'bill_id' => null]);

        Log::debug('Converted deposit to transfer.');

        return true;
    }

    /**
     * @param  int  $journalId
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
     * @param  int  $journalId
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
}
