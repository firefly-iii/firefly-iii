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
use FireflyIII\Models\TransactionJournal;
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
     * Set destination account to X
     * @param TransactionJournal $journal
     *
     * @return bool
     * @deprecated
     * @codeCoverageIgnore
     */
    public function act(TransactionJournal $journal): bool
    {
        return false;
    }

    /**
     * @return Account|null
     */
    private function findExpenseAccount(): ?Account
    {
        $account = $this->repository->findByName($this->action->action_value, [AccountType::EXPENSE]);
        if (null === $account) {
            $data    = [
                'name'            => $this->action->action_value,
                'account_type'    => 'expense',
                'account_type_id' => null,
                'virtual_balance' => 0,
                'active'          => true,
                'iban'            => null,
            ];
            $account = $this->repository->store($data);
        }
        Log::debug(sprintf('Found or created expense account #%d ("%s")', $account->id, $account->name));
        return $account;
    }

    /**
     * @inheritDoc
     */
    public function actOnArray(array $journal): bool
    {
        $user             = User::find($journal['user_id']);
        $type             = $journal['transaction_type_type'];
        $this->repository = app(AccountRepositoryInterface::class);
        $this->repository->setUser($user);

        // it depends on the type what kind of destination account is expected.
        $expectedTypes = config(sprintf('firefly.source_dests.%s.%s', $type, $journal['source_account_type']));
        if (null === $expectedTypes) {
            Log::error(sprintf('Configuration line "%s" is unexpectedly empty. Stopped.', sprintf('firefly.source_dests.%s.%s', $type, $journal['source_account_type'])));

            return false;
        }
        // try to find an account with the destination name and these types:
        $destination = $this->findAccount($expectedTypes);
        if (null !== $destination) {
            // update account of destination transaction.
            DB::table('transactions')
              ->where('transaction_journal_id', '=', $journal['transaction_journal_id'])
              ->where('amount', '>', 0)
              ->update(['account_id' => $destination->id]);
            Log::debug(sprintf('Updated journal #%d and gave it new account ID.', $journal['transaction_journal_id']));

            return true;
        }
        Log::info(sprintf('Expected destination account "%s" not found.', $this->action->action_value));

        if (in_array(AccountType::EXPENSE, $expectedTypes)) {
            // does not exist, but can be created.
            Log::debug('Expected type is expense, lets create it.');
            $expense = $this->findExpenseAccount();
            if (null === $expense) {
                Log::error('Could not create expense account.');
                return false;
            }
            DB::table('transactions')
              ->where('transaction_journal_id', '=', $journal['transaction_journal_id'])
              ->where('amount', '>', 0)
              ->update(['account_id' => $expense->id]);
            Log::debug(sprintf('Updated journal #%d and gave it new account ID.', $journal['transaction_journal_id']));

            return true;
        }

        return false;
    }

    /**
     * @param array $types
     * @return Account|null
     */
    private function findAccount(array $types): ?Account
    {
        return $this->repository->findByName($this->action->action_value, $types);
    }
}
