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

use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
use Log;

/**
 * Class SetDestinationAccount.
 */
class SetDestinationAccount implements ActionInterface
{
    private RuleAction                 $action;
    private AccountRepositoryInterface $repository;

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
        $user             = User::find($journal['user_id']);
        $type             = $journal['transaction_type_type'];
        /** @var TransactionJournal|null $object */
        $object           = $user->transactionJournals()->find((int)$journal['transaction_journal_id']);
        $this->repository = app(AccountRepositoryInterface::class);

        if (null === $object) {
            Log::error('Could not find journal.');

            return false;
        }

        $this->repository->setUser($user);

        // if this is a transfer or a deposit, the new destination account must be an asset account or a default account, and it MUST exist:
        $newAccount = $this->findAssetAccount($type);
        if ((TransactionType::DEPOSIT === $type || TransactionType::TRANSFER === $type) && null === $newAccount) {
            Log::error(
                sprintf(
                    'Cant change destination account of journal #%d because no asset account with name "%s" exists.', $object->id, $this->action->action_value
                )
            );

            return false;
        }

        // new destination account must be different from the current source account:
        /** @var Transaction|null $source */
        $source = $object->transactions()->where('amount', '<', 0)->first();
        if (null === $source) {
            Log::error('Could not find source transaction.');

            return false;
        }
        // account must not be deleted (in the mean time):
        if (null === $source->account) {
            Log::error('Could not find source transaction account.');

            return false;
        }
        if (null !== $newAccount && (int)$newAccount->id === (int)$source->account_id) {
            Log::error(
                sprintf(
                    'New destination account ID #%d and current source account ID #%d are the same. Do nothing.', $newAccount->id,
                    $source->account_id
                )
            );

            return false;
        }

        // if this is a withdrawal, the new destination account must be a expense account and may be created:
        if (TransactionType::WITHDRAWAL === $type) {
            $newAccount = $this->findExpenseAccount();
        }

        Log::debug(sprintf('New destination account is #%d ("%s").', $newAccount->id, $newAccount->name));

        // update destination transaction with new destination account:
        DB::table('transactions')
          ->where('transaction_journal_id', '=', $object->id)
          ->where('amount', '>', 0)
          ->update(['account_id' => $newAccount->id]);

        Log::debug(sprintf('Updated journal #%d (group #%d) and gave it new destination account ID.', $object->id, $object->transaction_group_id));

        return true;


    }

    /**
     * @param string $type
     *
     * @return Account|null
     */
    private function findAssetAccount(string $type): ?Account
    {
        // switch on type:
        $allowed = config(sprintf('firefly.expected_source_types.destination.%s', $type));
        $allowed = is_array($allowed) ? $allowed : [];
        Log::debug(sprintf('Check config for expected_source_types.destination.%s, result is', $type), $allowed);

        return $this->repository->findByName($this->action->action_value, $allowed);
    }

    /**
     * @return Account
     */
    private function findExpenseAccount(): Account
    {
        $account = $this->repository->findByName($this->action->action_value, [AccountType::EXPENSE]);
        if (null === $account) {
            $data    = [
                'name'              => $this->action->action_value,
                'account_type_name' => 'expense',
                'account_type_id'   => null,
                'virtual_balance'   => 0,
                'active'            => true,
                'iban'              => null,
            ];
            $account = $this->repository->store($data);
        }
        Log::debug(sprintf('Found or created expense account #%d ("%s")', $account->id, $account->name));

        return $account;
    }


}
