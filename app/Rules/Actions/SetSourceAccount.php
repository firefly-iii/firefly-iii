<?php
/**
 * SetSourceAccount.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Rules\Actions;


use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Log;

/**
 * Class SetSourceAccount
 *
 * @package FireflyIII\Rules\Action
 */
class SetSourceAccount implements ActionInterface
{

    private $action;

    /** @var  TransactionJournal */
    private $journal;

    /** @var  Account */
    private $newSourceAccount;

    /** @var AccountRepositoryInterface */
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
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function act(TransactionJournal $journal): bool
    {
        $this->journal    = $journal;
        $this->repository = app(AccountRepositoryInterface::class, [$journal->user]);
        $count            = $journal->transactions()->count();
        if ($count > 2) {
            Log::error(sprintf('Cannot change source account of journal #%d because it is a split journal.', $journal->id));

            return true;
        }

        // journal type:
        $type = $journal->transactionType->type;
        // if this is a transfer or a withdrawal, the new source account must be an asset account or a default account, and it MUST exist:
        if (($type === TransactionType::WITHDRAWAL || $type === TransactionType::TRANSFER) && !$this->findAssetAccount()) {
            Log::error(
                sprintf(
                    'Cannot change source account of journal #%d because no asset account with name "%s" exists.',
                    $journal->id, $this->action->action_value
                )
            );

            return true;
        }

        // if this is a deposit, the new source account must be a revenue account and may be created:
        if ($type === TransactionType::DEPOSIT) {
            $this->findRevenueAccount();
        }

        Log::debug(sprintf('New source account is #%d ("%s").', $this->newSourceAccount->id, $this->newSourceAccount->name));

        // update source transaction with new source account:
        // get source transaction:
        $transaction             = $journal->transactions()->where('amount', '<', 0)->first();
        $transaction->account_id = $this->newSourceAccount->id;
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

        if (is_null($account->id)) {
            Log::debug(sprintf('There is NO asset account called "%s".', $this->action->action_value));

            return false;
        }
        Log::debug(sprintf('There exists an asset account called "%s". ID is #%d', $this->action->action_value, $account->id));
        $this->newSourceAccount = $account;

        return true;
    }

    /**
     *
     */
    private function findRevenueAccount()
    {
        $account = $this->repository->findByName($this->action->action_value, [AccountType::REVENUE]);
        if (is_null($account->id)) {
            // create new revenue account with this name:
            $data    = [
                'name'           => $this->action->action_value,
                'accountType'    => 'revenue',
                'virtualBalance' => 0,
                'active'         => true,
                'iban'           => null,
            ];
            $account = $this->repository->store($data);
        }
        Log::debug(sprintf('Found or created revenue account #%d ("%s")', $account->id, $account->name));
        $this->newSourceAccount = $account;
    }
}
