<?php
/**
 * UpdatedGroupEventHandler.php
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

namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\RequestedSendWebhookMessages;
use FireflyIII\Events\UpdatedTransactionGroup;
use FireflyIII\Generator\Webhook\MessageGeneratorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Models\Webhook;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\TransactionRules\Engine\RuleEngineInterface;
use Illuminate\Support\Collection;
use Log;

/**
 * Class UpdatedGroupEventHandler
 */
class UpdatedGroupEventHandler
{
    /**
     * This method will make sure all source / destination accounts are the same.
     *
     * @param UpdatedTransactionGroup $updatedGroupEvent
     */
    public function unifyAccounts(UpdatedTransactionGroup $updatedGroupEvent): void
    {
        $group = $updatedGroupEvent->transactionGroup;
        if (1 === $group->transactionJournals->count()) {
            return;
        }
        Log::debug(sprintf('Correct inconsistent accounts in group #%d', $group->id));
        // first journal:
        /** @var TransactionJournal $first */
        $first = $group->transactionJournals()
                       ->orderBy('transaction_journals.date', 'DESC')
                       ->orderBy('transaction_journals.order', 'ASC')
                       ->orderBy('transaction_journals.id', 'DESC')
                       ->orderBy('transaction_journals.description', 'DESC')
                       ->first();
        $all   = $group->transactionJournals()->get()->pluck('id')->toArray();
        /** @var Account $sourceAccount */
        $sourceAccount = $first->transactions()->where('amount', '<', '0')->first()->account;
        /** @var Account $destAccount */
        $destAccount = $first->transactions()->where('amount', '>', '0')->first()->account;

        $type = $first->transactionType->type;
        if (TransactionType::TRANSFER === $type || TransactionType::WITHDRAWAL === $type) {
            // set all source transactions to source account:
            Transaction::whereIn('transaction_journal_id', $all)
                       ->where('amount', '<', 0)->update(['account_id' => $sourceAccount->id]);
        }
        if (TransactionType::TRANSFER === $type || TransactionType::DEPOSIT === $type) {
            // set all destination transactions to destination account:
            Transaction::whereIn('transaction_journal_id', $all)
                       ->where('amount', '>', 0)->update(['account_id' => $destAccount->id]);
        }

    }

    /**
     * This method will check all the rules when a journal is updated.
     *
     * @param UpdatedTransactionGroup $updatedGroupEvent
     */
    public function processRules(UpdatedTransactionGroup $updatedGroupEvent): void
    {
        if (false === $updatedGroupEvent->applyRules) {
            Log::info(sprintf('Will not run rules on group #%d', $updatedGroupEvent->transactionGroup->id));

            return;
        }

        $journals = $updatedGroupEvent->transactionGroup->transactionJournals;
        $array    = [];
        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $array[] = $journal->id;
        }
        $journalIds = implode(',', $array);
        Log::debug(sprintf('Add local operator for journal(s): %s', $journalIds));

        // collect rules:
        $ruleGroupRepository = app(RuleGroupRepositoryInterface::class);
        $ruleGroupRepository->setUser($updatedGroupEvent->transactionGroup->user);

        $groups = $ruleGroupRepository->getRuleGroupsWithRules('update-journal');

        // file rule engine.
        $newRuleEngine = app(RuleEngineInterface::class);
        $newRuleEngine->setUser($updatedGroupEvent->transactionGroup->user);
        $newRuleEngine->addOperator(['type' => 'journal_id', 'value' => $journalIds]);
        $newRuleEngine->setRuleGroups($groups);
        $newRuleEngine->fire();
    }

    /**
     * @param UpdatedTransactionGroup $updatedGroupEvent
     */
    public function triggerWebhooks(UpdatedTransactionGroup $updatedGroupEvent): void
    {
        Log::debug('UpdatedGroupEventHandler:triggerWebhooks');
        $group    = $updatedGroupEvent->transactionGroup;
        $user     = $group->user;
        /** @var MessageGeneratorInterface $engine */
        $engine = app(MessageGeneratorInterface::class);
        $engine->setUser($user);
        $engine->setObjects(new Collection([$group]));
        $engine->setTrigger(Webhook::TRIGGER_UPDATE_TRANSACTION);
        $engine->generateMessages();

        event(new RequestedSendWebhookMessages);
    }
}
