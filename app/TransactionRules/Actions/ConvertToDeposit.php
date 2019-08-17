<?php
/**
 * ConvertToDeposit.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
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
use Log;

/**
 *
 * Class ConvertToDeposit
 */
class ConvertToDeposit implements ActionInterface
{
    /** @var RuleAction The rule action */
    private $action;

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
     *
     * @return bool
     * @throws FireflyException
     */
    public function act(TransactionJournal $journal): bool
    {
        $type = $journal->transactionType->type;
        if (TransactionType::DEPOSIT === $type) {
            // @codeCoverageIgnoreStart
            Log::error(sprintf('Journal #%d is already a deposit (rule "%s").', $journal->id, $this->action->rule->title));

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
                    'Journal #%d has %d source transactions. ConvertToDeposit failed. (rule "%s").',
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
                    'Journal #%d has %d dest transactions. ConvertToDeposit failed. (rule "%s").',
                    [$journal->id, $destTransactions->count(), $this->action->rule->title]
                )
            );

            return false;
            // @codeCoverageIgnoreEnd
        }


        if (TransactionType::WITHDRAWAL === $type) {
            Log::debug('Going to transform a withdrawal to a deposit.');

            return $this->convertWithdrawal($journal);
        }
        if (TransactionType::TRANSFER === $type) {
            Log::debug('Going to transform a transfer to a deposit.');

            return $this->convertTransfer($journal);
        }

        return false; // @codeCoverageIgnore
    }

    /**
     * Input is a transfer from A to B.
     * Output is a deposit from C to B.
     *
     * @param TransactionJournal $journal
     *
     * @return bool
     * @throws FireflyException
     */
    private function convertTransfer(TransactionJournal $journal): bool
    {
        // find or create revenue account.
        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($journal->user);

        $sourceTransactions = $journal->transactions()->where('amount', '<', 0)->get();

        // get the action value, or use the original source name in case the action value is empty:
        // this becomes a new or existing revenue account.
        /** @var Account $source */
        $source      = $sourceTransactions->first()->account;
        $revenueName = '' === $this->action->action_value ? $source->name : $this->action->action_value;
        $revenue     = $factory->findOrCreate($revenueName, AccountType::REVENUE);

        Log::debug(sprintf('ConvertToDeposit. Action value is "%s", revenue name is "%s"', $this->action->action_value, $source->name));
        unset($source);

        // update source transaction(s) to be revenue account
        $journal->transactions()
                ->where('amount', '<', 0)
                ->update(['account_id' => $revenue->id]);

        // change transaction type of journal:
        $newType                      = TransactionType::whereType(TransactionType::DEPOSIT)->first();
        $journal->transaction_type_id = $newType->id;
        $journal->save();
        Log::debug('Converted transfer to deposit.');

        return true;
    }

    /**
     * Input is a withdrawal from A to B
     * Is converted to a deposit from C to A.
     *
     * @param TransactionJournal $journal
     *
     * @return bool
     * @throws FireflyException
     */
    private function convertWithdrawal(TransactionJournal $journal): bool
    {
        // find or create revenue account.
        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($journal->user);

        $destTransactions   = $journal->transactions()->where('amount', '>', 0)->get();
        $sourceTransactions = $journal->transactions()->where('amount', '<', 0)->get();

        // get the action value, or use the original destination name in case the action value is empty:
        // this becomes a new or existing revenue account.
        /** @var Account $destination */
        $destination = $destTransactions->first()->account;
        $revenueName = '' === $this->action->action_value ? $destination->name : $this->action->action_value;
        $revenue     = $factory->findOrCreate($revenueName, AccountType::REVENUE);

        Log::debug(sprintf('ConvertToDeposit. Action value is "%s", revenue name is "%s"', $this->action->action_value, $destination->name));


        // get source account from transaction(s).
        /** @var Account $source */
        $source = $sourceTransactions->first()->account;

        // update source transaction(s) to be revenue account
        $journal->transactions()
                ->where('amount', '<', 0)
                ->update(['account_id' => $revenue->id]);

        // update destination transaction(s) to be original source account(s).
        $journal->transactions()
                ->where('amount', '>', 0)
                ->update(['account_id' => $source->id]);

        // change transaction type of journal:
        $newType                      = TransactionType::whereType(TransactionType::DEPOSIT)->first();
        $journal->transaction_type_id = $newType->id;
        $journal->save();

        Log::debug('Converted withdrawal to deposit.');

        return true;
    }
}
