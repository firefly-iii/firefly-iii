<?php
/*
 * ProcessesNewTransactionGroup.php
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

use Carbon\Carbon;
use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Events\Model\TransactionGroup\CreatedSingleTransactionGroup;
use FireflyIII\Events\Model\Webhook\WebhookMessagesRequestSending;
use FireflyIII\Generator\Webhook\MessageGeneratorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\PeriodStatistic\PeriodStatisticRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\Services\Internal\Support\CreditRecalculateService;
use FireflyIII\Support\Facades\FireflyConfig;
use FireflyIII\Support\Models\AccountBalanceCalculator;
use FireflyIII\TransactionRules\Engine\RuleEngineInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProcessesNewTransactionGroup implements ShouldQueue
{
    public function handle(CreatedSingleTransactionGroup $event): void
    {
        Log::debug(sprintf('In ProcessesNewTransactionGroup::handle(#%d)', $event->transactionGroup->id));
        if (true === $event->flags->batchSubmission) {
            Log::debug(sprintf('Will do nothing for group #%d because it is part of a batch.', $event->transactionGroup->id));
            return;
        }
        Log::debug(sprintf('Will join group #%d with all other open transaction groups and process them.', $event->transactionGroup->id));
        $collection = $event->transactionGroup->transactionJournals;
        $repository = app(JournalRepositoryInterface::class);
        $set        = $collection->merge($repository->getUncompletedJournals());
        if (0 === $set->count()) {
            Log::debug('Set is empty, never mind.');
            return;
        }
        if (!$event->flags->applyRules) {
            Log::debug(sprintf('Will NOT process rules for %d journal(s)', $set->count()));
        }
        if (!$event->flags->recalculateCredit) {
            Log::debug(sprintf('Will NOT recalculate credit for %d journal(s)', $set->count()));
        }
        if (!$event->flags->fireWebhooks) {
            Log::debug(sprintf('Will NOT fire webhooks for %d journal(s)', $set->count()));
        }
        if ($event->flags->applyRules) {
            $this->processRules($set);
        }
        if ($event->flags->recalculateCredit) {
            $this->recalculateCredit($set);
        }
        if ($event->flags->fireWebhooks) {
            $this->fireWebhooks($set);
        }
        // always remove old statistics.
        $this->removePeriodStatistics($set);

        // recalculate running balance if necessary.

        Log::debug('Observe "created" of a transaction.');
        if (true === FireflyConfig::get('use_running_balance', config('firefly.feature_flags.running_balance_column'))->data) {
            $this->recalculateRunningBalance($set);
        }

        $repository->markAsCompleted($set);

    }

    private function recalculateRunningBalance(Collection $set): void
    {
        Log::debug('Now in recalculateRunningBalance');
        // find the earliest date in the set, based on date and _internal_previous_date
        $earliest = $set->pluck('date')->sort()->first();
        $entries  = TransactionJournalMeta
            ::whereIn('transaction_journal_id', $set->pluck('id')->toArray())
            ->where('name', '_internal_previous_date')
            ->get(['journal_meta.*']);
        $array    = $entries->toArray();
        if (count($array) > 0) {
            usort($array, function (array $a, array $b) {
                return Carbon::parse($a['data'])->gt(Carbon::parse($b['data']));
            });
            /** @var Carbon $date */
            $date     = Carbon::parse($array[0]['data']);
            $earliest = $date->lt($earliest) ? $date : $earliest;
        }

        // get accounts
        $accounts = Account
            ::leftJoin('transactions', 'transactions.account_id', 'accounts.id')
            ->leftJoin('transaction_journals', 'transaction_journals.id', 'transactions.transaction_journal_id')
            ->leftJoin('account_types', 'account_types.id', 'accounts.account_type_id')
            ->whereIn('transaction_journals.id', $set->pluck('id')->toArray())
            ->get(['accounts.*']);

        AccountBalanceCalculator::optimizedCalculation($accounts, $earliest);
    }

    private function removePeriodStatistics(Collection $set): void
    {
        Log::debug('Always remove period statistics');
        /** @var PeriodStatisticRepositoryInterface $repository */
        $repository = app(PeriodStatisticRepositoryInterface::class);
        $repository->deleteStatisticsForCollection($set);
    }

    private function fireWebhooks(Collection $set): void
    {
        // collect transaction groups by set ids.
        $groups = TransactionGroup::whereIn('id', array_unique($set->pluck('transaction_group_id')->toArray()))->get();

        Log::debug(__METHOD__);
        $user = $set->first()->user;

        /** @var MessageGeneratorInterface $engine */
        $engine = app(MessageGeneratorInterface::class);
        $engine->setUser($user);

        // tell the generator which trigger it should look for
        $engine->setTrigger(WebhookTrigger::STORE_TRANSACTION);
        // tell the generator which objects to process
        $engine->setObjects($groups);
        // tell the generator to generate the messages
        $engine->generateMessages();

        // trigger event to send them:
        Log::debug(sprintf('send event WebhookMessagesRequestSending from %s', __METHOD__));
        event(new WebhookMessagesRequestSending());
    }

    private function recalculateCredit(Collection $set): void
    {
        Log::debug(sprintf('Will now recalculateCredit for %d journal(s)', $set->count()));

        /** @var CreditRecalculateService $object */
        $object = app(CreditRecalculateService::class);
        $object->setJournals($set);
        $object->recalculate();
    }

    private function processRules(Collection $set): void
    {
        Log::debug(sprintf('Will now processRules for %d journal(s)', $set->count()));
        $array      = $set->pluck('id')->toArray();
        $journalIds = implode(',', $array);
        $user       = $set->first()->user;
        Log::debug(sprintf('Add local operator for journal(s): %s', $journalIds));

        // collect rules:
        $ruleGroupRepository = app(RuleGroupRepositoryInterface::class);
        $ruleGroupRepository->setUser($user);

        // add the groups to the rule engine.
        // it should run the rules in the group and cancel the group if necessary.
        Log::debug('Fire processRules with ALL store-journal rule groups.');
        $groups = $ruleGroupRepository->getRuleGroupsWithRules('store-journal');

        // create and fire rule engine.
        $newRuleEngine = app(RuleEngineInterface::class);
        $newRuleEngine->setUser($user);
        $newRuleEngine->addOperator(['type' => 'journal_id', 'value' => $journalIds]);
        $newRuleEngine->setRuleGroups($groups);
        $newRuleEngine->fire();
    }

}
