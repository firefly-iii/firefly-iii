<?php
/**
 * SetDestinationAccount.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\TransactionRules\Actions;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
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
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function act(TransactionJournal $journal): bool
    {
        $this->journal    = $journal;
        $this->repository = app(AccountRepositoryInterface::class);
        $this->repository->setUser($journal->user);
        $count = $journal->transactions()->count();
        if ($count > 2) {
            Log::error(sprintf('Cannot change destination account of journal #%d because it is a split journal.', $journal->id));

            return false;
        }

        // journal type:
        $type = $journal->transactionType->type;

        // if this is a deposit or a transfer, the destination account must be an asset account or a default account, and it MUST exist:
        if ((TransactionType::DEPOSIT === $type || TransactionType::TRANSFER === $type) && !$this->findAssetAccount()) {
            Log::error(
                sprintf(
                    'Cannot change destination account of journal #%d because no asset account with name "%s" exists.',
                    $journal->id,
                    $this->action->action_value
                )
            );

            return false;
        }

        // if this is a withdrawal, the new destination account must be a expense account and may be created:
        if (TransactionType::WITHDRAWAL === $type) {
            $this->findExpenseAccount();
        }

        Log::debug(sprintf('New destination account is #%d ("%s").', $this->newDestinationAccount->id, $this->newDestinationAccount->name));

        // update destination transaction with new destination account:
        // get destination transaction:
        $transaction             = $journal->transactions()->where('amount', '>', 0)->first();
        $transaction->account_id = $this->newDestinationAccount->id;
        $transaction->save();
        Log::debug(sprintf('Updated transaction #%d and gave it new account ID.', $transaction->id));

        return true;
    }

    /**
     * @return bool
     */
    private function findAssetAccount(): bool
    {
        $account = $this->repository->findByName($this->action->action_value, [AccountType::DEFAULT, AccountType::ASSET]);

        if (null === $account->id) {
            Log::debug(sprintf('There is NO asset account called "%s".', $this->action->action_value));

            return false;
        }
        Log::debug(sprintf('There exists an asset account called "%s". ID is #%d', $this->action->action_value, $account->id));
        $this->newDestinationAccount = $account;

        return true;
    }

    /**
     *
     */
    private function findExpenseAccount()
    {
        $account = $this->repository->findByName($this->action->action_value, [AccountType::EXPENSE]);
        if (null === $account->id) {
            // create new revenue account with this name:
            $data    = [
                'name'           => $this->action->action_value,
                'accountType'    => 'expense',
                'virtualBalance' => 0,
                'active'         => true,
                'iban'           => null,
            ];
            $account = $this->repository->store($data);
        }
        Log::debug(sprintf('Found or created expense account #%d ("%s")', $account->id, $account->name));
        $this->newDestinationAccount = $account;
    }
}
