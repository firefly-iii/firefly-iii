<?php
/**
 * StoredJournalEventHandler.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\StoredTransactionJournal;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Rules\Processor;
use FireflyIII\Support\Events\BillScanner;
use Log;

/**
 * Class StoredJournalEventHandler
 *
 * @package FireflyIII\Handlers\Events
 */
class StoredJournalEventHandler
{
    /**
     * This method connects a new transfer to a piggy bank.
     *
     * @param StoredTransactionJournal $event
     *
     * @return bool
     */
    public function connectToPiggyBank(StoredTransactionJournal $event): bool
    {
        /** @var TransactionJournal $journal */
        $journal     = $event->journal;
        $piggyBankId = $event->piggyBankId;

        Log::debug(sprintf('Trying to connect journal %d to piggy bank %d.', $journal->id, $piggyBankId));

        /** @var PiggyBank $piggyBank */
        $piggyBank = $journal->user->piggyBanks()->where('piggy_banks.id', $piggyBankId)->first(['piggy_banks.*']);

        if (is_null($piggyBank)) {
            Log::error('No such piggy bank!');

            return true;
        }
        Log::debug(sprintf('Found piggy bank #%d: "%s"', $piggyBank->id, $piggyBank->name));
        // update piggy bank rep for date of transaction journal.
        $repetition = $piggyBank->piggyBankRepetitions()->relevantOnDate($journal->date)->first();
        if (is_null($repetition)) {
            Log::error(sprintf('No piggy bank repetition on %s!', $journal->date->format('Y-m-d')));

            return true;
        }

        $amount = TransactionJournal::amountPositive($journal);
        Log::debug(sprintf('Will add/remove %f to piggy bank #%d ("%s")', $amount, $piggyBank->id, $piggyBank->name));
        // if piggy account matches source account, the amount is positive
        $sources = TransactionJournal::sourceAccountList($journal)->pluck('id')->toArray();
        if (in_array($piggyBank->account_id, $sources)) {
            $amount = bcmul($amount, '-1');
            Log::debug(sprintf('Account #%d is the source, so will remove amount from piggy bank.', $piggyBank->account_id));
        }

        // if the amount is positive:
        // make sure it fits in piggy bank:
        if (bccomp($amount, '0') === 1) {
            // amount is positive
            $room = bcsub(strval($piggyBank->targetamount), strval($repetition->currentamount));
            Log::debug(sprintf('Room in piggy bank for extra money is %f', $room));
            if (bccomp($room, $amount) === -1) {
                // $room is smaller than $amount
                Log::debug(sprintf('There is NO room to add %f to piggy bank #%d ("%s")', $amount, $piggyBank->id, $piggyBank->name));
                Log::debug(sprintf('New amount is %f', $room));
                $amount = $room;
            }
        }

        if (bccomp($amount, '0') === -1) {
            // amount is negative
            Log::debug(sprintf('Max amount to remove is %f', $repetition->currentamount));
            $compare = bcmul($repetition->currentamount, '-1');
            if (bccomp($compare, $amount) === 1) {
                // $currentamount is smaller than $amount
                Log::debug(sprintf('Cannot remove %f from piggy bank #%d ("%s")', $amount, $piggyBank->id, $piggyBank->name));
                Log::debug(sprintf('New amount is %f', $compare));
                $amount = $compare;
            }
        }


        $repetition->currentamount = bcadd($repetition->currentamount, $amount);
        $repetition->save();

        /** @var PiggyBankEvent $event */
        $event = PiggyBankEvent::create(
            ['piggy_bank_id' => $piggyBank->id, 'transaction_journal_id' => $journal->id, 'date' => $journal->date, 'amount' => $amount]
        );
        Log::debug(sprintf('Created piggy bank event #%d', $event->id));

        return true;
    }

    /**
     * This method grabs all the users rules and processes them.
     *
     * @param StoredTransactionJournal $event
     *
     * @return bool
     */
    public function processRules(StoredTransactionJournal $event): bool
    {
        // get all the user's rule groups, with the rules, order by 'order'.
        $journal = $event->journal;
        $groups  = $journal->user->ruleGroups()->where('rule_groups.active', 1)->orderBy('order', 'ASC')->get();
        //
        /** @var RuleGroup $group */
        foreach ($groups as $group) {
            $rules = $group->rules()
                           ->leftJoin('rule_triggers', 'rules.id', '=', 'rule_triggers.rule_id')
                           ->where('rule_triggers.trigger_type', 'user_action')
                           ->where('rule_triggers.trigger_value', 'store-journal')
                           ->where('rules.active', 1)
                           ->get(['rules.*']);
            /** @var Rule $rule */
            foreach ($rules as $rule) {

                $processor = Processor::make($rule);
                $processor->handleTransactionJournal($journal);

                if ($rule->stop_processing) {
                    return true;
                }

            }
        }

        return true;
    }

    /**
     * This method calls a special bill scanner that will check if the stored journal is part of a bill.
     *
     * @param StoredTransactionJournal $event
     *
     * @return bool
     */
    public function scanBills(StoredTransactionJournal $event): bool
    {
        $journal = $event->journal;
        BillScanner::scan($journal);

        return true;
    }
}
