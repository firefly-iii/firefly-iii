<?php

/**
 * SetSourceAccount.php
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
use FireflyIII\Events\Model\Rule\RuleActionFailedOnArray;
use FireflyIII\Events\TriggeredAuditLog;
use FireflyIII\Models\Account;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\TransactionRules\Expressions\ActionExpressionEvaluator;
use FireflyIII\User;
use Illuminate\Support\Facades\Log;

/**
 * Class SetSourceAccount.
 */
class SetSourceAccount implements ActionInterface
{
    private RuleAction                 $action;
    private ActionExpressionEvaluator  $evaluator;
    private AccountRepositoryInterface $repository;

    /**
     * TriggerInterface constructor.
     *
     * @param RuleAction $action
     */
    public function __construct(RuleAction $action, ActionExpressionEvaluator $evaluator)
    {
        $this->action = $action;
        $this->evaluator = $evaluator;
    }

    /**
     * @inheritDoc
     */
    public function actOnArray(array $journal): bool
    {
        $user = User::find($journal['user_id']);
        $type = $journal['transaction_type_type'];
        $name = $this->evaluator->evaluate($journal);
        /** @var TransactionJournal|null $object */
        $object           = $user->transactionJournals()->find((int)$journal['transaction_journal_id']);
        $this->repository = app(AccountRepositoryInterface::class);
        if (null === $object) {
            Log::error('Could not find journal.');
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.no_such_journal')));
            return false;
        }
        $type = $object->transactionType->type;
        $this->repository->setUser($user);

        // if this is a transfer or a withdrawal, the new source account must be an asset account or a default account, and it MUST exist:
        $newAccount = $this->findAssetAccount($type, $name);
        if ((TransactionType::WITHDRAWAL === $type || TransactionType::TRANSFER === $type) && null === $newAccount) {
            Log::error(
                sprintf('Cant change source account of journal #%d because no asset account with name "%s" exists.', $object->id, $name)
            );
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.cannot_find_asset', ['name' => $name])));
            return false;
        }

        // new source account must be different from the current destination account:
        /** @var Transaction|null $destination */
        $destination = $object->transactions()->where('amount', '>', 0)->first();
        if (null === $destination) {
            Log::error('Could not find destination transaction.');
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.cannot_find_destination_transaction')));
            return false;
        }
        // account must not be deleted (in the meantime):
        if (null === $destination->account) {
            Log::error('Could not find destination transaction account.');
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.cannot_find_destination_transaction_account')));
            return false;
        }
        if (null !== $newAccount && (int)$newAccount->id === (int)$destination->account_id) {
            Log::error(
                sprintf(
                    'New source account ID #%d and current destination account ID #%d are the same. Do nothing.',
                    $newAccount->id,
                    $destination->account_id
                )
            );
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.already_has_source', ['name' => $newAccount->name])));
            return false;
        }

        // if this is a deposit, the new source account must be a revenue account and may be created:
        // or it's a liability
        if (TransactionType::DEPOSIT === $type) {
            $newAccount = $this->findDepositSourceAccount($name);
        }

        Log::debug(sprintf('New source account is #%d ("%s").', $newAccount->id, $newAccount->name));

        // update source transaction with new source account:
        DB::table('transactions')
            ->where('transaction_journal_id', '=', $object->id)
            ->where('amount', '<', 0)
            ->update(['account_id' => $newAccount->id]);

        event(new TriggeredAuditLog($this->action->rule, $object, 'set_source', null, $newAccount->name));

        Log::debug(sprintf('Updated journal #%d (group #%d) and gave it new source account ID.', $object->id, $object->transaction_group_id));

        return true;
    }

    /**
     * @param string $type
     *
     * @return Account|null
     */
    private function findAssetAccount(string $type, string $name): ?Account
    {
        // switch on type:
        $allowed = config(sprintf('firefly.expected_source_types.source.%s', $type));
        $allowed = is_array($allowed) ? $allowed : [];
        Log::debug(sprintf('Check config for expected_source_types.source.%s, result is', $type), $allowed);

        return $this->repository->findByName($name, $allowed);
    }

    /**
     * @return Account
     */
    private function findDepositSourceAccount(string $name): Account
    {
        $allowed = config('firefly.expected_source_types.source.Deposit');
        $account = $this->repository->findByName($name, $allowed);
        if (null === $account) {
            // create new revenue account with this name:
            $data    = [
                'name'              => $name,
                'account_type_name' => 'revenue',
                'account_type_id'   => null,
                'virtual_balance'   => 0,
                'active'            => true,
                'iban'              => null,
            ];
            $account = $this->repository->store($data);
        }
        Log::debug(sprintf('Found or created revenue account #%d ("%s")', $account->id, $account->name));

        return $account;
    }
}
