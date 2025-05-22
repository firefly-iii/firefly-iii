<?php

/**
 * SetDestinationAccount.php
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
use FireflyIII\Events\TriggeredAuditLog;
use FireflyIII\Models\Account;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Facades\DB;

/**
 * Class SetDestinationAccount.
 */
class SetDestinationAccount implements ActionInterface
{
    private AccountRepositoryInterface $repository;

    /**
     * TriggerInterface constructor.
     */
    public function __construct(private readonly RuleAction $action) {}

    public function actOnArray(array $journal): bool
    {
        $accountName = $this->action->getValue($journal);

        /** @var User $user */
        $user = User::find($journal['user_id']);

        /** @var null|TransactionJournal $object */
        $object           = $user->transactionJournals()->find((int) $journal['transaction_journal_id']);
        $this->repository = app(AccountRepositoryInterface::class);

        if (null === $object) {
            app('log')->error('Could not find journal.');
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.no_such_journal')));

            return false;
        }
        $type = $object->transactionType->type;
        $this->repository->setUser($user);

        // if this is a transfer or a deposit, the new destination account must be an asset account or a default account, and it MUST exist:
        $newAccount = $this->findAssetAccount($type, $accountName);
        if ((TransactionTypeEnum::DEPOSIT->value === $type || TransactionTypeEnum::TRANSFER->value === $type) && null === $newAccount) {
            app('log')->error(
                sprintf(
                    'Cant change destination account of journal #%d because no asset account with name "%s" exists.',
                    $object->id,
                    $accountName
                )
            );
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.cannot_find_asset', ['name' => $accountName])));

            return false;
        }

        // new destination account must be different from the current source account:
        /** @var null|Transaction $source */
        $source = $object->transactions()->where('amount', '<', 0)->first();
        if (null === $source) {
            app('log')->error('Could not find source transaction.');
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.cannot_find_source_transaction')));

            return false;
        }
        // account must not be deleted (in the meantime):
        if (null === $source->account) {
            app('log')->error('Could not find source transaction account.');
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.cannot_find_source_transaction_account')));

            return false;
        }
        if (null !== $newAccount && $newAccount->id === $source->account_id) {
            app('log')->error(
                sprintf(
                    'New destination account ID #%d and current source account ID #%d are the same. Do nothing.',
                    $newAccount->id,
                    $source->account_id
                )
            );

            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.already_has_destination', ['name' => $newAccount->name])));

            return false;
        }

        // if this is a withdrawal, the new destination account must be a expense account and may be created:
        // or it is a liability, in which case it must be returned.
        if (TransactionTypeEnum::WITHDRAWAL->value === $type) {
            $newAccount = $this->findWithdrawalDestinationAccount($accountName);
        }
        if (null === $newAccount) {
            app('log')->error(
                sprintf(
                    'No destination account found for name "%s".',
                    $accountName
                )
            );

            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.no_destination', ['name' => $accountName])));

            return false;
        }

        app('log')->debug(sprintf('New destination account is #%d ("%s").', $newAccount->id, $newAccount->name));

        event(new TriggeredAuditLog($this->action->rule, $object, 'set_destination', null, $newAccount->name));

        // update destination transaction with new destination account:
        DB::table('transactions')
          ->where('transaction_journal_id', '=', $object->id)
          ->where('amount', '>', 0)
          ->update(['account_id' => $newAccount->id]);

        app('log')->debug(sprintf('Updated journal #%d (group #%d) and gave it new destination account ID.', $object->id, $object->transaction_group_id));

        return true;
    }

    private function findAssetAccount(string $type, string $accountName): ?Account
    {
        // switch on type:
        $allowed = config(sprintf('firefly.expected_source_types.destination.%s', $type));
        $allowed = is_array($allowed) ? $allowed : [];
        app('log')->debug(sprintf('Check config for expected_source_types.destination.%s, result is', $type), $allowed);

        return $this->repository->findByName($accountName, $allowed);
    }

    private function findWithdrawalDestinationAccount(string $accountName): Account
    {
        $allowed = config('firefly.expected_source_types.destination.Withdrawal');
        $account = $this->repository->findByName($accountName, $allowed);
        if (null === $account) {
            $data    = [
                'name'              => $accountName,
                'account_type_name' => 'expense',
                'account_type_id'   => null,
                'virtual_balance'   => 0,
                'active'            => true,
                'iban'              => null,
            ];
            $account = $this->repository->store($data);
        }
        app('log')->debug(sprintf('Found or created expense account #%d ("%s")', $account->id, $account->name));

        return $account;
    }
}
