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

declare(strict_types=1);

namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\StoredTransactionJournal;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
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
    /** @var  JournalRepositoryInterface */
    public $journalRepository;
    /** @var  PiggyBankRepositoryInterface */
    public $repository;

    public $ruleGroupRepos;

    /**
     * StoredJournalEventHandler constructor.
     */
    public function __construct(
        PiggyBankRepositoryInterface $repository, JournalRepositoryInterface $journalRepository, RuleGroupRepositoryInterface $ruleGroupRepository
    ) {
        $this->repository        = $repository;
        $this->journalRepository = $journalRepository;
        $this->ruleGroupRepos    = $ruleGroupRepository;

    }

    /**
     * This method connects a new transfer to a piggy bank.
     *
     * @codeCoverageIgnore
     *
     * @param StoredTransactionJournal $event
     *
     * @return bool
     */
    public function connectToPiggyBank(StoredTransactionJournal $event): bool
    {
        $journal     = $event->journal;
        $piggyBankId = $event->piggyBankId;
        $piggyBank   = $this->repository->find($piggyBankId);

        // is a transfer?
        if (!$this->journalRepository->isTransfer($journal)) {
            Log::info(sprintf('Will not connect %s #%d to a piggy bank.', $journal->transactionType->type, $journal->id));

            return true;
        }

        // piggy exists?
        if (is_null($piggyBank->id)) {
            Log::error(sprintf('There is no piggy bank with ID #%d', $piggyBankId));

            return true;
        }

        // repetition exists?
        $repetition = $this->repository->getRepetition($piggyBank, $journal->date);
        if (is_null($repetition->id)) {
            Log::error(sprintf('No piggy bank repetition on %s!', $journal->date->format('Y-m-d')));

            return true;
        }

        // get the amount
        $amount = $this->repository->getExactAmount($piggyBank, $repetition, $journal);
        if (bccomp($amount, '0') === 0) {
            Log::debug('Amount is zero, will not create event.');

            return true;
        }

        // update amount
        $this->repository->addAmountToRepetition($repetition, $amount);
        $event = $this->repository->createEventWithJournal($piggyBank, $amount, $journal);

        Log::debug(sprintf('Created piggy bank event #%d', $event->id));

        return true;
    }

    /**
     * This method grabs all the users rules and processes them.
     *
     * @param StoredTransactionJournal $storedJournalEvent
     *
     * @return bool
     */
    public function processRules(StoredTransactionJournal $storedJournalEvent): bool
    {
        // get all the user's rule groups, with the rules, order by 'order'.
        $journal = $storedJournalEvent->journal;
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
     * @param StoredTransactionJournal $storedJournalEvent
     *
     * @return bool
     */
    public function scanBills(StoredTransactionJournal $storedJournalEvent): bool
    {
        $journal = $storedJournalEvent->journal;
        BillScanner::scan($journal);

        return true;
    }
}
