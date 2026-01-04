<?php

/**
 * StoredGroupEventHandler.php
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

use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Events\Model\TransactionGroup\TriggeredStoredTransactionGroup;
use FireflyIII\Events\RequestedSendWebhookMessages;
use FireflyIII\Events\StoredTransactionGroup;
use FireflyIII\Generator\Webhook\MessageGeneratorInterface;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\PeriodStatistic\PeriodStatisticRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\Services\Internal\Support\CreditRecalculateService;
use FireflyIII\TransactionRules\Engine\RuleEngineInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class StoredGroupEventHandler
 *
 * TODO migrate to observer?
 */
class StoredGroupEventHandler
{
    public function runAllHandlers(StoredTransactionGroup $event): void
    {
        $this->processRules($event, null);
        $this->recalculateCredit($event);
        $this->triggerWebhooks($event);
        $this->removePeriodStatistics($event);
    }

    public function triggerRulesManually(TriggeredStoredTransactionGroup $event): void
    {
        $newEvent = new StoredTransactionGroup($event->transactionGroup, true, false);
        $this->processRules($newEvent, $event->ruleGroup);
    }

    /**
     * This method grabs all the users rules and processes them.
     */
    private function processRules(StoredTransactionGroup $storedGroupEvent, ?RuleGroup $ruleGroup): void
    {
        if (false === $storedGroupEvent->applyRules) {
            Log::info(sprintf('Will not run rules on group #%d', $storedGroupEvent->transactionGroup->id));

            return;
        }
        Log::debug('Now in StoredGroupEventHandler::processRules()');

        $journals = $storedGroupEvent->transactionGroup->transactionJournals;
        $array    = [];

        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $array[] = $journal->id;
        }
        $journalIds = implode(',', $array);
        Log::debug(sprintf('Add local operator for journal(s): %s', $journalIds));

        // collect rules:
        $ruleGroupRepository = app(RuleGroupRepositoryInterface::class);
        $ruleGroupRepository->setUser($storedGroupEvent->transactionGroup->user);

        // add the groups to the rule engine.
        // it should run the rules in the group and cancel the group if necessary.
        if (null === $ruleGroup) {
            Log::debug('Fire processRules with ALL store-journal rule groups.');
            $groups = $ruleGroupRepository->getRuleGroupsWithRules('store-journal');
        }
        if (null !== $ruleGroup) {
            Log::debug(sprintf('Fire processRules with rule group #%d.', $ruleGroup->id));
            $groups = new Collection([$ruleGroup]);
        }

        // create and fire rule engine.
        $newRuleEngine = app(RuleEngineInterface::class);
        $newRuleEngine->setUser($storedGroupEvent->transactionGroup->user);
        $newRuleEngine->addOperator(['type' => 'journal_id', 'value' => $journalIds]);
        $newRuleEngine->setRuleGroups($groups);
        $newRuleEngine->fire();
    }

    private function recalculateCredit(StoredTransactionGroup $event): void
    {
        $group = $event->transactionGroup;

        /** @var CreditRecalculateService $object */
        $object = app(CreditRecalculateService::class);
        $object->setGroup($group);
        $object->recalculate();
    }

    private function removePeriodStatistics(StoredTransactionGroup $event): void
    {
        /** @var PeriodStatisticRepositoryInterface $repository */
        $repository = app(PeriodStatisticRepositoryInterface::class);

        /** @var TransactionJournal $journal */
        foreach ($event->transactionGroup->transactionJournals as $journal) {
            /** @var null|Transaction $source */
            $source = $journal->transactions()->where('amount', '<', '0')->first();

            /** @var null|Transaction $dest */
            $dest = $journal->transactions()->where('amount', '>', '0')->first();

            if (null !== $source) {
                $repository->deleteStatisticsForModel($source->account, $journal->date);
            }
            if (null !== $dest) {
                $repository->deleteStatisticsForModel($dest->account, $journal->date);
            }
            $categories = $journal->categories;
            $tags       = $journal->tags;
            $budgets    = $journal->budgets;
            foreach ($categories as $category) {
                $repository->deleteStatisticsForModel($category, $journal->date);
            }
            foreach ($tags as $tag) {
                $repository->deleteStatisticsForModel($tag, $journal->date);
            }
            foreach ($budgets as $budget) {
                $repository->deleteStatisticsForModel($budget, $journal->date);
            }
            if (0 === $categories->count()) {
                $repository->deleteStatisticsForPrefix($journal->userGroup, 'no_category', $journal->date);
            }
            if (0 === $budgets->count()) {
                $repository->deleteStatisticsForPrefix($journal->userGroup, 'no_budget', $journal->date);
            }
        }
    }

    /**
     * This method processes all webhooks that respond to the "stored transaction group" trigger (100)
     */
    private function triggerWebhooks(StoredTransactionGroup $storedGroupEvent): void
    {
        Log::debug(__METHOD__);
        $group = $storedGroupEvent->transactionGroup;
        if (false === $storedGroupEvent->fireWebhooks) {
            Log::info(sprintf('Will not fire webhooks for transaction group #%d', $group->id));

            return;
        }

        $user = $group->user;

        /** @var MessageGeneratorInterface $engine */
        $engine = app(MessageGeneratorInterface::class);
        $engine->setUser($user);

        // tell the generator which trigger it should look for
        $engine->setTrigger(WebhookTrigger::STORE_TRANSACTION);
        // tell the generator which objects to process
        $engine->setObjects(new Collection()->push($group));
        // tell the generator to generate the messages
        $engine->generateMessages();

        // trigger event to send them:
        Log::debug(sprintf('send event RequestedSendWebhookMessages from %s', __METHOD__));
        event(new RequestedSendWebhookMessages());
    }
}
