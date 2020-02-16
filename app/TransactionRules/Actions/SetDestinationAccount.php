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

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Log;

/**
 * Class SetDestinationAccount.
 */
class SetDestinationAccount implements ActionInterface
{
    /** @var RuleAction The rule action */
    private $action;

    /** @var TransactionJournal The journal */
    private $journal;

    /** @var Account The new account */
    private $newDestinationAccount;

    /** @var AccountRepositoryInterface Account repository */
    private $repository;

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
     *
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function act(TransactionJournal $journal): bool
    {
        $this->journal    = $journal;
        $this->repository = app(AccountRepositoryInterface::class);
        $this->repository->setUser($journal->user);
        // journal type:
        $type = $journal->transactionType->type;
        // source and destination:
        /** @var Transaction $source */
        $source = $journal->transactions()->where('amount', '<', 0)->first();
        /** @var Transaction $destination */
        $destination = $journal->transactions()->where('amount', '>', 0)->first();

        // sanity check:
        if (null === $source || null === $destination) {
            Log::error(sprintf('Cannot run rule on journal #%d because no source or dest.', $journal->id));

            return false;
        }

        // it depends on the type what kind of destination account is expected.
        $expectedDestinationTypes = config(sprintf('firefly.source_dests.%s.%s', $type, $source->account->accountType->type));

        if (null === $expectedDestinationTypes) {
            Log::error(
                sprintf(
                    'Configuration line "%s" is unexpectedly empty. Stopped.', sprintf('firefly.source_dests.%s.%s', $type, $source->account->accountType->type)
                )
            );

            return false;
        }

        // try to find an account with the destination name and these types:
        $newDestination = $this->findAccount($expectedDestinationTypes);
        if (true === $newDestination) {
            // update account.
            $destination->account_id = $this->newDestinationAccount->id;
            $destination->save();
            $journal->touch();
            Log::debug(sprintf('Updated transaction #%d and gave it new account ID.', $destination->id));

            return true;
        }

        Log::info(sprintf('Expected destination account "%s" not found.', $this->action->action_value));
        if (AccountType::EXPENSE === $expectedDestinationTypes[0]) {
            Log::debug('Expected type is expense, lets create it.');
            $this->findExpenseAccount();
            // update account.
            $destination->account_id = $this->newDestinationAccount->id;
            $destination->save();
            $journal->touch();
            Log::debug(sprintf('Updated transaction #%d and gave it new account ID.', $destination->id));
        }

        return true;
    }

    /**
     * @param array $types
     *
     * @return bool
     */
    private function findAccount(array $types): bool
    {
        $account = $this->repository->findByName($this->action->action_value, $types);

        if (null === $account) {
            Log::debug(sprintf('There is NO account called "%s" of type', $this->action->action_value), $types);

            return false;
        }
        Log::debug(
            sprintf(
                'There exists an account called "%s". ID is #%d. Type is "%s"',
                $this->action->action_value, $account->id, $account->accountType->type
            )
        );
        $this->newDestinationAccount = $account;

        return true;
    }

    /**
     *
     */
    private function findExpenseAccount(): void
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
        $this->newDestinationAccount = $account;
    }
}
