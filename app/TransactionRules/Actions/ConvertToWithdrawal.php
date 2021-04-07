<?php
/**
 * ConvertToWithdrawal.php
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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Log;

/**
 *
 * Class ConvertToWithdrawal
 */
class ConvertToWithdrawal implements ActionInterface
{
    private RuleAction $action;

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
        $type = $journal['transaction_type_type'];
        if (TransactionType::WITHDRAWAL === $type) {
            Log::error(sprintf('Journal #%d is already a withdrawal (rule #%d).', $journal['transaction_journal_id'], $this->action->rule_id));

            return false;
        }

        if (TransactionType::DEPOSIT === $type) {
            Log::debug('Going to transform a deposit to a withdrawal.');

            return $this->convertDepositArray($journal);
        }
        if (TransactionType::TRANSFER === $type) {
            Log::debug('Going to transform a transfer to a withdrawal.');

            return $this->convertTransferArray($journal);
        }

        return false; 
    }

    private function convertDepositArray(array $journal): bool
    {
        $user = User::find($journal['user_id']);
        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($user);

        $expenseName   = '' === $this->action->action_value ? $journal['source_account_name'] : $this->action->action_value;
        $expense       = $factory->findOrCreate($expenseName, AccountType::EXPENSE);
        $destinationId = $journal['destination_account_id'];
        Log::debug(sprintf('ConvertToWithdrawal. Action value is "%s", expense name is "%s"', $this->action->action_value, $expenseName));

        // update source transaction(s) to be the original destination account
        DB::table('transactions')
          ->where('transaction_journal_id', '=', $journal['transaction_journal_id'])
          ->where('amount', '<', 0)
          ->update(['account_id' => $destinationId]);

        // update destination transaction(s) to be new expense account.
        DB::table('transactions')
          ->where('transaction_journal_id', '=', $journal['transaction_journal_id'])
          ->where('amount', '>', 0)
          ->update(['account_id' => $expense->id]);

        // change transaction type of journal:
        $newType = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        DB::table('transaction_journals')
          ->where('id', '=', $journal['transaction_journal_id'])
          ->update(['transaction_type_id' => $newType->id]);

        Log::debug('Converted deposit to withdrawal.');

        return true;

    }

    /**
     * Input is a transfer from A to B.
     * Output is a withdrawal from A to C.
     *
     * @param array $journal
     *
     * @return bool
     * @throws FireflyException
     */
    private function convertTransferArray(array $journal): bool
    {
        // find or create expense account.
        $user = User::find($journal['user_id']);
        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($user);
        $expenseName = '' === $this->action->action_value ? $journal['destination_account_name'] : $this->action->action_value;
        $expense     = $factory->findOrCreate($expenseName, AccountType::EXPENSE);

        Log::debug(sprintf('ConvertToWithdrawal. Action value is "%s", expense name is "%s"', $this->action->action_value, $expenseName));

        // update destination transaction(s) to be new expense account.
        DB::table('transactions')
          ->where('transaction_journal_id', '=', $journal['transaction_journal_id'])
          ->where('amount', '>', 0)
          ->update(['account_id' => $expense->id]);

        // change transaction type of journal:
        $newType = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        DB::table('transaction_journals')
          ->where('id', '=', $journal['transaction_journal_id'])
          ->update(['transaction_type_id' => $newType->id]);

        Log::debug('Converted transfer to withdrawal.');

        return true;
    }
}
