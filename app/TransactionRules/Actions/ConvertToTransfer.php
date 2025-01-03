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

use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Events\Model\Rule\RuleActionFailedOnArray;
use FireflyIII\Events\Model\Rule\RuleActionFailedOnObject;
use FireflyIII\Events\TriggeredAuditLog;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;

/**
 * Class ConvertToTransfer
 */
class ConvertToTransfer implements ActionInterface
{
    private RuleAction $action;

    /**
     * TriggerInterface constructor.
     */
    public function __construct(RuleAction $action)
    {
        $this->action = $action;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function actOnArray(array $journal): bool
    {
        $accountName  = $this->action->getValue($journal);

        // make object from array (so the data is fresh).
        /** @var null|TransactionJournal $object */
        $object       = TransactionJournal::where('user_id', $journal['user_id'])->find($journal['transaction_journal_id']);
        if (null === $object) {
            app('log')->error(sprintf('Cannot find journal #%d, cannot convert to transfer.', $journal['transaction_journal_id']));
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.journal_not_found')));

            return false;
        }
        $groupCount   = TransactionJournal::where('transaction_group_id', $journal['transaction_group_id'])->count();
        if ($groupCount > 1) {
            app('log')->error(sprintf('Group #%d has more than one transaction in it, cannot convert to transfer.', $journal['transaction_group_id']));
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.split_group')));

            return false;
        }

        $type         = $object->transactionType->type;
        $user         = $object->user;
        $journalId    = $object->id;
        if (TransactionType::TRANSFER === $type) {
            app('log')->error(
                sprintf('Journal #%d is already a transfer so cannot be converted (rule #%d).', $object->id, $this->action->rule_id)
            );
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.is_already_transfer')));

            return false;
        }
        if (TransactionType::DEPOSIT !== $type && TransactionTypeEnum::WITHDRAWAL->value !== $type) {
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.unsupported_transaction_type_transfer', ['type' => $type])));

            return false;
        }

        // find the asset account in the action value.
        /** @var AccountRepositoryInterface $repository */
        $repository   = app(AccountRepositoryInterface::class);
        $repository->setUser($user);
        $expectedType = null;
        if (TransactionTypeEnum::WITHDRAWAL->value === $type) {
            $expectedType = $this->getSourceType($journalId);
            // Withdrawal? Replace destination with account with same type as source.
        }
        if (TransactionType::DEPOSIT === $type) {
            $expectedType = $this->getDestinationType($journalId);
            // Deposit? Replace source with account with same type as destination.
        }
        $opposing     = $repository->findByName($accountName, [$expectedType]);

        if (null === $opposing) {
            app('log')->error(
                sprintf(
                    'Journal #%d cannot be converted because no valid %s account with name "%s" exists (rule #%d).',
                    $expectedType,
                    $journalId,
                    $accountName,
                    $this->action->rule_id
                )
            );
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.no_valid_opposing', ['name' => $accountName])));

            return false;
        }

        if (TransactionTypeEnum::WITHDRAWAL->value === $type) {
            app('log')->debug('Going to transform a withdrawal to a transfer.');

            try {
                $res = $this->convertWithdrawalArray($object, $opposing);
            } catch (FireflyException $e) {
                app('log')->debug('Could not convert withdrawal to transfer.');
                app('log')->error($e->getMessage());
                event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.complex_error')));

                return false;
            }
            if (false !== $res) {
                event(new TriggeredAuditLog($this->action->rule, $object, 'update_transaction_type', TransactionTypeEnum::WITHDRAWAL->value, TransactionType::TRANSFER));
            }

            return $res;
        }
        // can only be a deposit at this point.
        app('log')->debug('Going to transform a deposit to a transfer.');

        try {
            $res = $this->convertDepositArray($object, $opposing);
        } catch (FireflyException $e) {
            app('log')->debug('Could not convert deposit to transfer.');
            app('log')->error($e->getMessage());
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.complex_error')));

            return false;
        }
        if (false !== $res) {
            event(new TriggeredAuditLog($this->action->rule, $object, 'update_transaction_type', TransactionType::DEPOSIT, TransactionType::TRANSFER));
        }

        return $res;
    }

    private function getSourceType(int $journalId): string
    {
        /** @var null|TransactionJournal $journal */
        $journal = TransactionJournal::find($journalId);
        if (null === $journal) {
            app('log')->error(sprintf('Journal #%d does not exist. Cannot convert to transfer.', $journalId));

            return '';
        }

        return (string) $journal->transactions()->where('amount', '<', 0)->first()?->account?->accountType?->type;
    }

    private function getDestinationType(int $journalId): string
    {
        /** @var null|TransactionJournal $journal */
        $journal = TransactionJournal::find($journalId);
        if (null === $journal) {
            app('log')->error(sprintf('Journal #%d does not exist. Cannot convert to transfer.', $journalId));

            return '';
        }

        return (string) $journal->transactions()->where('amount', '>', 0)->first()?->account?->accountType?->type;
    }

    /**
     * A withdrawal is from Asset to Expense.
     * We replace the Expense with another asset.
     * So this replaces the destination
     *
     * @throws FireflyException
     */
    private function convertWithdrawalArray(TransactionJournal $journal, Account $opposing): bool
    {
        $sourceAccount = $this->getSourceAccount($journal);
        if ($sourceAccount->id === $opposing->id) {
            app('log')->error(
                vsprintf(
                    'Journal #%d has already has "%s" as a source asset. ConvertToTransfer failed. (rule #%d).',
                    [$journal->id, $opposing->name, $this->action->rule_id]
                )
            );
            event(new RuleActionFailedOnObject($this->action, $journal, trans('rules.already_has_source_asset', ['name' => $opposing->name])));

            return false;
        }

        // update destination transaction:
        \DB::table('transactions')
            ->where('transaction_journal_id', '=', $journal->id)
            ->where('amount', '>', 0)
            ->update(['account_id' => $opposing->id])
        ;

        // change transaction type of journal:
        $newType       = TransactionType::whereType(TransactionType::TRANSFER)->first();

        \DB::table('transaction_journals')
            ->where('id', '=', $journal->id)
            ->update(['transaction_type_id' => $newType->id, 'bill_id' => null])
        ;

        app('log')->debug('Converted withdrawal to transfer.');

        return true;
    }

    /**
     * @throws FireflyException
     */
    private function getSourceAccount(TransactionJournal $journal): Account
    {
        /** @var null|Transaction $sourceTransaction */
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
     * @throws FireflyException
     */
    private function convertDepositArray(TransactionJournal $journal, Account $opposing): bool
    {
        $destAccount = $this->getDestinationAccount($journal);
        if ($destAccount->id === $opposing->id) {
            app('log')->error(
                vsprintf(
                    'Journal #%d has already has "%s" as a destination asset. ConvertToTransfer failed. (rule #%d).',
                    [$journal->id, $opposing->name, $this->action->rule_id]
                )
            );
            event(new RuleActionFailedOnObject($this->action, $journal, trans('rules.already_has_destination_asset', ['name' => $opposing->name])));

            return false;
        }

        // update source transaction:
        \DB::table('transactions')
            ->where('transaction_journal_id', '=', $journal->id)
            ->where('amount', '<', 0)
            ->update(['account_id' => $opposing->id])
        ;

        // change transaction type of journal:
        $newType     = TransactionType::whereType(TransactionType::TRANSFER)->first();

        \DB::table('transaction_journals')
            ->where('id', '=', $journal->id)
            ->update(['transaction_type_id' => $newType->id, 'bill_id' => null])
        ;

        app('log')->debug('Converted deposit to transfer.');

        return true;
    }

    /**
     * @throws FireflyException
     */
    private function getDestinationAccount(TransactionJournal $journal): Account
    {
        /** @var null|Transaction $destAccount */
        $destAccount = $journal->transactions()->where('amount', '>', 0)->first();
        if (null === $destAccount) {
            throw new FireflyException(sprintf('Cannot find destination transaction for journal #%d', $journal->id));
        }

        return $destAccount->account;
    }
}
