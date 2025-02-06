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
use FireflyIII\Events\RequestedSendWebhookMessages;
use FireflyIII\Events\StoredTransactionGroup;
use FireflyIII\Generator\Webhook\MessageGeneratorInterface;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\Services\Internal\Support\CreditRecalculateService;
use FireflyIII\TransactionRules\Engine\RuleEngineInterface;
use Illuminate\Support\Collection;

/**
 * Class StoredGroupEventHandler
 */
class StoredGroupEventHandler
{
    public function runAllHandlers(StoredTransactionGroup $event): void
    {
        $this->processRules($event);
        $this->recalculateCredit($event);
        $this->triggerWebhooks($event);
    }


    /**
     * This method grabs all the users rules and processes them.
     */
    private function processRules(StoredTransactionGroup $storedGroupEvent): void
    {
        if (false === $storedGroupEvent->applyRules) {
            app('log')->info(sprintf('Will not run rules on group #%d', $storedGroupEvent->transactionGroup->id));

            return;
        }
        app('log')->debug('Now in StoredGroupEventHandler::processRules()');

        $journals = $storedGroupEvent->transactionGroup->transactionJournals;
        $array    = [];

        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $array[] = $journal->id;
        }
        $journalIds = implode(',', $array);
        app('log')->debug(sprintf('Add local operator for journal(s): %s', $journalIds));

        // collect rules:
        $ruleGroupRepository = app(RuleGroupRepositoryInterface::class);
        $ruleGroupRepository->setUser($storedGroupEvent->transactionGroup->user);

        // add the groups to the rule engine.
        // it should run the rules in the group and cancel the group if necessary.
        $groups = $ruleGroupRepository->getRuleGroupsWithRules('store-journal');

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

    /**
     * This method processes all webhooks that respond to the "stored transaction group" trigger (100)
     */
    private function triggerWebhooks(StoredTransactionGroup $storedGroupEvent): void
    {
        app('log')->debug(__METHOD__);
        $group = $storedGroupEvent->transactionGroup;
        if (false === $storedGroupEvent->fireWebhooks) {
            app('log')->info(sprintf('Will not fire webhooks for transaction group #%d', $group->id));

            return;
        }

        $user = $group->user;

        /** @var MessageGeneratorInterface $engine */
        $engine = app(MessageGeneratorInterface::class);
        $engine->setUser($user);

        // tell the generator which trigger it should look for
        $engine->setTrigger(WebhookTrigger::STORE_TRANSACTION->value);
        // tell the generator which objects to process
        $engine->setObjects(new Collection([$group]));
        // tell the generator to generate the messages
        $engine->generateMessages();

        // trigger event to send them:
        event(new RequestedSendWebhookMessages());
    }
}
