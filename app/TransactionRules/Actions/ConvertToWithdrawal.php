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


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Log;
use DB;

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
     * Execute the action.
     *
     * @param TransactionJournal $journal
     * @deprecated
     * @codeCoverageIgnore
     * @return bool
     * @throws FireflyException
     */
    public function act(TransactionJournal $journal): bool
    {
        $type = $journal->transactionType->type;
        if (TransactionType::WITHDRAWAL === $type) {
            // @codeCoverageIgnoreStart
            Log::error(sprintf('Journal #%d is already a withdrawal (rule "%s").', $journal->id, $this->action->rule->title));

            return false;
            // @codeCoverageIgnoreEnd
        }

        $destTransactions   = $journal->transactions()->where('amount', '>', 0)->get();
        $sourceTransactions = $journal->transactions()->where('amount', '<', 0)->get();

        // break if count is zero:
        if (1 !== $sourceTransactions->count()) {
            // @codeCoverageIgnoreStart
            Log::error(
                vsprintf(
                    'Journal #%d has %d source transactions. ConvertToWithdrawal failed. (rule "%s").',
                    [$journal->id, $sourceTransactions->count(), $this->action->rule->title]
                )
            );

            return false;
            // @codeCoverageIgnoreEnd
        }
        if (0 === $destTransactions->count()) {
            // @codeCoverageIgnoreStart
            Log::error(
                vsprintf(
                    'Journal #%d has %d dest transactions. ConvertToWithdrawal failed. (rule "%s").',
                    [$journal->id, $destTransactions->count(), $this->action->rule->title]
                )
            );

            return false;
            // @codeCoverageIgnoreEnd
        }


        if (TransactionType::DEPOSIT === $type) {
            Log::debug('Going to transform a deposit to a withdrawal.');

            return $this->convertDeposit($journal);
        }
        if (TransactionType::TRANSFER === $type) {
            Log::debug('Going to transform a transfer to a withdrawal.');

            return $this->convertTransfer($journal);
        }

        return false; // @codeCoverageIgnore
    }

    /**
     * Input is a deposit from A to B
     * Is converted to a withdrawal from B to C.
     *
     * @param TransactionJournal $journal
     * @deprecated
     * @codeCoverageIgnore
     * @return bool
     * @throws FireflyException
     */
    private function convertDeposit(TransactionJournal $journal): bool
    {
        // find or create expense account.
        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($journal->user);

        $destTransactions   = $journal->transactions()->where('amount', '>', 0)->get();
        $sourceTransactions = $journal->transactions()->where('amount', '<', 0)->get();

        // get the action value, or use the original source revenue name in case the action value is empty:
        // this becomes a new or existing expense account.
        /** @var Account $source */
        $source      = $sourceTransactions->first()->account;
        $expenseName = '' === $this->action->action_value ? $source->name : $this->action->action_value;
        $expense     = $factory->findOrCreate($expenseName, AccountType::EXPENSE);

        Log::debug(sprintf('ConvertToWithdrawal. Action value is "%s", expense name is "%s"', $this->action->action_value, $source->name));
        unset($source);

        // get destination asset account from transaction(s).
        /** @var Account $destination */
        $destination = $destTransactions->first()->account;

        // update source transaction(s) to be the original destination account
        $journal->transactions()
                ->where('amount', '<', 0)
                ->update(['account_id' => $destination->id]);

        // update destination transaction(s) to be new expense account.
        $journal->transactions()
                ->where('amount', '>', 0)
                ->update(['account_id' => $expense->id]);

        // change transaction type of journal:
        $newType                      = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        $journal->transaction_type_id = $newType->id;
        $journal->save();

        Log::debug('Converted deposit to withdrawal.');

        return true;
    }

    /**
     * Input is a transfer from A to B.
     * Output is a withdrawal from A to C.
     *
     * @param TransactionJournal $journal
     * @deprecated
     * @codeCoverageIgnore
     * @return bool
     * @throws FireflyException
     */
    private function convertTransfer(TransactionJournal $journal): bool
    {
        // find or create expense account.
        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($journal->user);

        $destTransactions = $journal->transactions()->where('amount', '>', 0)->get();

        // get the action value, or use the original destination name in case the action value is empty:
        // this becomes a new or existing expense account.
        /** @var Account $destination */
        $destination = $destTransactions->first()->account;
        $expenseName = '' === $this->action->action_value ? $destination->name : $this->action->action_value;
        $expense     = $factory->findOrCreate($expenseName, AccountType::EXPENSE);

        Log::debug(sprintf('ConvertToWithdrawal. Action value is "%s", revenue name is "%s"', $this->action->action_value, $destination->name));
        unset($source);

        // update destination transaction(s) to be the expense account
        $journal->transactions()
                ->where('amount', '>', 0)
                ->update(['account_id' => $expense->id]);

        // change transaction type of journal:
        $newType                      = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        $journal->transaction_type_id = $newType->id;
        $journal->save();
        Log::debug('Converted transfer to withdrawal.');

        return true;
    }


    /**
     * Input is a transfer from A to B.
     * Output is a withdrawal from A to C.
     *
     * @param array $journal
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
        $newType                      = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        DB::table('transaction_journals')
          ->where('id', '=', $journal['transaction_journal_id'])
          ->update(['transaction_type_id' => $newType->id]);

        Log::debug('Converted transfer to withdrawal.');

        return true;
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

        return false; // @codeCoverageIgnore
    }

    private function convertDepositArray(array $journal): bool
    {
        $user = User::find($journal['user_id']);
        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($user);

        $expenseName = '' === $this->action->action_value ? $journal['source_account_name'] : $this->action->action_value;
        $expense     = $factory->findOrCreate($expenseName, AccountType::EXPENSE);
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
        $newType                      = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        DB::table('transaction_journals')
          ->where('id', '=', $journal['transaction_journal_id'])
          ->update(['transaction_type_id' => $newType->id]);

        Log::debug('Converted deposit to withdrawal.');

        return true;

    }
}
