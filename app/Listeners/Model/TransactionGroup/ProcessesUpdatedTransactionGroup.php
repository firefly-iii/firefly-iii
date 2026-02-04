<?php
/*
 * ProcessesUpdatedTransactionGroup.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Listeners\Model\TransactionGroup;

use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Events\Model\TransactionGroup\UpdatedSingleTransactionGroup;
use FireflyIII\Events\Model\Webhook\WebhookMessagesRequestSending;
use FireflyIII\Generator\Webhook\MessageGeneratorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\Services\Internal\Support\CreditRecalculateService;
use FireflyIII\Support\Facades\FireflyConfig;
use FireflyIII\Support\Models\AccountBalanceCalculator;
use FireflyIII\TransactionRules\Engine\RuleEngineInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProcessesUpdatedTransactionGroup
{
    public function handle(UpdatedSingleTransactionGroup $event): void
    {
        Log::debug('Now in handle() for UpdatedSingleTransactionGroup');
        $this->unifyAccounts($event);
        $this->processRules($event);
        $this->recalculateCredit($event);
        $this->triggerWebhooks($event);
        ProcessesNewTransactionGroup::removePeriodStatistics($event->transactionGroup->transactionJournals);
        $this->updateRunningBalance($event);

        Log::debug('Done with handle() for UpdatedSingleTransactionGroup');
    }


    /**
     * This method will make sure all source / destination accounts are the same.
     */
    public function unifyAccounts(UpdatedSingleTransactionGroup $updatedGroupEvent): void
    {
        Log::debug('Now in unifyAccounts()');
        $group = $updatedGroupEvent->transactionGroup;
        if (1 === $group->transactionJournals->count()) {
            Log::debug('Nothing to do in unifyAccounts()');
            return;
        }

        // first journal:
        /** @var null|TransactionJournal $first */
        $first = $group
            ->transactionJournals()
            ->orderBy('transaction_journals.date', 'DESC')
            ->orderBy('transaction_journals.order', 'ASC')
            ->orderBy('transaction_journals.id', 'DESC')
            ->orderBy('transaction_journals.description', 'DESC')
            ->first();

        if (null === $first) {
            Log::warning(sprintf('Group #%d has no transaction journals.', $group->id));

            return;
        }

        $all = $group->transactionJournals()->get()->pluck('id')->toArray();

        /** @var Account $sourceAccount */
        $sourceAccount = $first->transactions()->where('amount', '<', '0')->first()->account;

        /** @var Account $destAccount */
        $destAccount = $first->transactions()->where('amount', '>', '0')->first()->account;

        $type = $first->transactionType->type;
        if (TransactionTypeEnum::TRANSFER->value === $type || TransactionTypeEnum::WITHDRAWAL->value === $type) {
            // set all source transactions to source account:
            Transaction::whereIn('transaction_journal_id', $all)->where('amount', '<', 0)->update(['account_id' => $sourceAccount->id]);
        }
        if (TransactionTypeEnum::TRANSFER->value === $type || TransactionTypeEnum::DEPOSIT->value === $type) {
            // set all destination transactions to destination account:
            Transaction::whereIn('transaction_journal_id', $all)->where('amount', '>', 0)->update(['account_id' => $destAccount->id]);
        }
        Log::debug('Done with unifyAccounts()');
    }


    /**
     * This method will check all the rules when a journal is updated.
     */
    private function processRules(UpdatedSingleTransactionGroup $updatedGroupEvent): void
    {
        Log::debug('Now in processRules()');
        if (false === $updatedGroupEvent->flags->applyRules) {
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
        Log::debug('Done with processRules()');
    }


    private function recalculateCredit(UpdatedSingleTransactionGroup $event): void
    {
        Log::debug('Now in recalculateCredit()');
        $group = $event->transactionGroup;

        /** @var CreditRecalculateService $object */
        $object = app(CreditRecalculateService::class);
        $object->setGroup($group);
        $object->recalculate();
        Log::debug('Done with recalculateCredit()');
    }


    private function triggerWebhooks(UpdatedSingleTransactionGroup $updatedGroupEvent): void
    {
        Log::debug('Now in triggerWebhooks()');
        $group = $updatedGroupEvent->transactionGroup;
        if (false === $updatedGroupEvent->fireWebhooks) {
            Log::info(sprintf('Will not fire webhooks for transaction group #%d', $group->id));

            return;
        }
        $user = $group->user;

        /** @var MessageGeneratorInterface $engine */
        $engine = app(MessageGeneratorInterface::class);
        $engine->setUser($user);
        $engine->setObjects(new Collection()->push($group));
        $engine->setTrigger(WebhookTrigger::UPDATE_TRANSACTION);
        $engine->generateMessages();

        Log::debug(sprintf('send event WebhookMessagesRequestSending from %s', __METHOD__));
        event(new WebhookMessagesRequestSending());
        Log::debug('End of triggerWebhooks()');
    }

    private function updateRunningBalance(UpdatedSingleTransactionGroup $event): void
    {
        Log::debug('Now in updateRunningBalance()');
        if (false === FireflyConfig::get('use_running_balance', config('firefly.feature_flags.running_balance_column'))->data) {
            return;
        }
        Log::debug(__METHOD__);
        $group = $event->transactionGroup;
        foreach ($group->transactionJournals as $journal) {
            AccountBalanceCalculator::recalculateForJournal($journal);
        }
        Log::debug('Done with updateRunningBalance()');
    }
}
