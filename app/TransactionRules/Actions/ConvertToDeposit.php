<?php

/**
 * ConvertToDeposit.php
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

use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Events\Model\Rule\RuleActionFailedOnArray;
use FireflyIII\Events\TriggeredAuditLog;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Class ConvertToDeposit
 */
class ConvertToDeposit implements ActionInterface
{
    /**
     * TriggerInterface constructor.
     */
    public function __construct(private readonly RuleAction $action) {}

    public function actOnArray(array $journal): bool
    {
        $actionValue = $this->action->getValue($journal);

        // make object from array (so the data is fresh).
        /** @var null|TransactionJournal $object */
        $object      = TransactionJournal::where('user_id', $journal['user_id'])->find($journal['transaction_journal_id']);
        if (null === $object) {
            app('log')->error(sprintf('Cannot find journal #%d, cannot convert to deposit.', $journal['transaction_journal_id']));
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.journal_not_found')));

            return false;
        }
        $groupCount  = TransactionJournal::where('transaction_group_id', $journal['transaction_group_id'])->count();
        if ($groupCount > 1) {
            app('log')->error(sprintf('Group #%d has more than one transaction in it, cannot convert to deposit.', $journal['transaction_group_id']));
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.split_group')));

            return false;
        }

        app('log')->debug(sprintf('Convert journal #%d to deposit.', $journal['transaction_journal_id']));
        $type        = $object->transactionType->type;
        if (TransactionTypeEnum::DEPOSIT->value === $type) {
            app('log')->error(sprintf('Journal #%d is already a deposit (rule #%d).', $journal['transaction_journal_id'], $this->action->rule_id));
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.is_already_deposit')));

            return false;
        }

        if (TransactionTypeEnum::WITHDRAWAL->value === $type) {
            app('log')->debug('Going to transform a withdrawal to a deposit.');

            try {
                $res = $this->convertWithdrawalArray($object, $actionValue);
            } catch (FireflyException $e) {
                app('log')->debug('Could not convert withdrawal to deposit.');
                app('log')->error($e->getMessage());
                event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.complex_error')));

                return false;
            }

            event(new TriggeredAuditLog($this->action->rule, $object, 'update_transaction_type', TransactionTypeEnum::WITHDRAWAL->value, TransactionTypeEnum::DEPOSIT->value));

            return $res;
        }
        if (TransactionTypeEnum::TRANSFER->value === $type) {
            app('log')->debug('Going to transform a transfer to a deposit.');

            try {
                $res = $this->convertTransferArray($object, $actionValue);
            } catch (FireflyException $e) {
                app('log')->debug('Could not convert transfer to deposit.');
                app('log')->error($e->getMessage());
                event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.complex_error')));

                return false;
            }
            event(new TriggeredAuditLog($this->action->rule, $object, 'update_transaction_type', TransactionTypeEnum::TRANSFER->value, TransactionTypeEnum::DEPOSIT->value));

            return $res;
        }
        event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.unsupported_transaction_type_deposit', ['type' => $type])));

        return false;
    }

    /**
     * Input is a withdrawal from A to B
     * Is converted to a deposit from C to A.
     *
     * @throws FireflyException
     */
    private function convertWithdrawalArray(TransactionJournal $journal, string $actionValue = ''): bool
    {
        $user            = $journal->user;

        // find or create revenue account.
        /** @var AccountFactory $factory */
        $factory         = app(AccountFactory::class);
        $factory->setUser($user);

        $repository      = app(AccountRepositoryInterface::class);
        $repository->setUser($user);

        $destAccount     = $this->getDestinationAccount($journal);
        $sourceAccount   = $this->getSourceAccount($journal);

        // get the action value, or use the original destination name in case the action value is empty:
        // this becomes a new or existing (revenue) account, which is the source of the new deposit.
        $opposingName    = '' === $actionValue ? $destAccount->name : $actionValue;
        // we check all possible source account types if one exists:
        $validTypes      = config('firefly.expected_source_types.source.Deposit');
        $opposingAccount = $repository->findByName($opposingName, $validTypes);
        if (null === $opposingAccount) {
            $opposingAccount = $factory->findOrCreate($opposingName, AccountTypeEnum::REVENUE->value);
        }

        app('log')->debug(sprintf('ConvertToDeposit. Action value is "%s", new opposing name is "%s"', $actionValue, $opposingAccount->name));

        // update the source transaction and put in the new revenue ID.
        DB::table('transactions')
            ->where('transaction_journal_id', '=', $journal->id)
            ->where('amount', '<', 0)
            ->update(['account_id' => $opposingAccount->id])
        ;

        // update the destination transaction and put in the original source account ID.
        DB::table('transactions')
            ->where('transaction_journal_id', '=', $journal->id)
            ->where('amount', '>', 0)
            ->update(['account_id' => $sourceAccount->id])
        ;

        // change transaction type of journal:
        $newType         = TransactionType::whereType(TransactionTypeEnum::DEPOSIT->value)->first();

        DB::table('transaction_journals')
            ->where('id', '=', $journal->id)
            ->update(['transaction_type_id' => $newType->id, 'bill_id' => null])
        ;

        app('log')->debug('Converted withdrawal to deposit.');

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
     * Input is a transfer from A to B.
     * Output is a deposit from C to B.
     * The source account is replaced.
     *
     * @throws FireflyException
     */
    private function convertTransferArray(TransactionJournal $journal, string $actionValue = ''): bool
    {
        $user            = $journal->user;

        // find or create revenue account.
        /** @var AccountFactory $factory */
        $factory         = app(AccountFactory::class);
        $factory->setUser($user);

        $repository      = app(AccountRepositoryInterface::class);
        $repository->setUser($user);

        $sourceAccount   = $this->getSourceAccount($journal);

        // get the action value, or use the original source name in case the action value is empty:
        // this becomes a new or existing (revenue) account, which is the source of the new deposit.
        $opposingName    = '' === $actionValue ? $sourceAccount->name : $actionValue;
        // we check all possible source account types if one exists:
        $validTypes      = config('firefly.expected_source_types.source.Deposit');
        $opposingAccount = $repository->findByName($opposingName, $validTypes);
        if (null === $opposingAccount) {
            $opposingAccount = $factory->findOrCreate($opposingName, AccountTypeEnum::REVENUE->value);
        }

        app('log')->debug(sprintf('ConvertToDeposit. Action value is "%s", revenue name is "%s"', $actionValue, $opposingAccount->name));

        // update source transaction(s) to be revenue account
        DB::table('transactions')
            ->where('transaction_journal_id', '=', $journal->id)
            ->where('amount', '<', 0)
            ->update(['account_id' => $opposingAccount->id])
        ;

        // change transaction type of journal:
        $newType         = TransactionType::whereType(TransactionTypeEnum::DEPOSIT->value)->first();

        DB::table('transaction_journals')
            ->where('id', '=', $journal->id)
            ->update(['transaction_type_id' => $newType->id, 'bill_id' => null])
        ;

        app('log')->debug('Converted transfer to deposit.');

        return true;
    }
}
