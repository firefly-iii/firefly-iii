<?php

declare(strict_types=1);

namespace FireflyIII\Listeners\Model\TransactionGroup;

use Carbon\Carbon;
use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Events\Model\TransactionGroup\TransactionGroupEventObjects;
use FireflyIII\Generator\Webhook\MessageGeneratorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Repositories\PeriodStatistic\PeriodStatisticRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\Services\Internal\Support\CreditRecalculateService;
use FireflyIII\Support\Facades\FireflyConfig;
use FireflyIII\Support\Models\AccountBalanceCalculator;
use FireflyIII\TransactionRules\Engine\RuleEngineInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

trait SupportsGroupProcessingTrait
{
    private function recalculateCredit(Collection $accounts): void
    {
        Log::debug(sprintf('Will now recalculateCredit for %d account(s)', $accounts->count()));

        /** @var CreditRecalculateService $object */
        $object = app(CreditRecalculateService::class);
        $object->setAccounts($accounts);
        $object->recalculate();
    }

    private function fireWebhooks(Collection $journals, WebhookTrigger $trigger): void
    {
        // collect transaction groups by set ids.
        $groups = TransactionGroup::whereIn('id', array_unique($journals->pluck('transaction_group_id')->toArray()))->get();

        Log::debug(__METHOD__);

        /** @var TransactionJournal $first */
        $first  = $journals->first();
        $user   = $first->user;

        /** @var MessageGeneratorInterface $engine */
        $engine = app(MessageGeneratorInterface::class);
        $engine->setUser($user);

        // tell the generator which trigger it should look for
        $engine->setTrigger($trigger);
        // tell the generator which objects to process
        $engine->setObjects($groups);
        // tell the generator to generate the messages
        $engine->generateMessages();
    }

    protected function removePeriodStatistics(TransactionGroupEventObjects $objects): void
    {
        if (auth()->check()) {
            // since you get a bunch of journals AND a bunch of
            // objects, this needs to be a collection
            /** @var PeriodStatisticRepositoryInterface $repository */
            $repository = app(PeriodStatisticRepositoryInterface::class);
            $dates      = $this->collectDatesFromJournals($objects->transactionJournals);
            $repository->deleteStatisticsForType(Account::class, $objects->accounts, $dates);
            $repository->deleteStatisticsForType(Budget::class, $objects->budgets, $dates);
            $repository->deleteStatisticsForType(Category::class, $objects->categories, $dates);
            $repository->deleteStatisticsForType(Tag::class, $objects->tags, $dates);

            // remove if no stuff present:
            // remove for no tag, no cat, etc.
            if (0 === $objects->budgets->count()) {
                Log::debug('No budgets, delete "no_category" stats.');
                $repository->deleteStatisticsForPrefix('no_budget', $dates);
            }
            if (0 === $objects->categories->count()) {
                Log::debug('No categories, delete "no_category" stats.');
                $repository->deleteStatisticsForPrefix('no_category', $dates);
            }
            if (0 === $objects->tags->count()) {
                Log::debug('No tags, delete "no_category" stats.');
                $repository->deleteStatisticsForPrefix('no_tag', $dates);
            }
        }
    }

    private function collectDatesFromJournals(Collection $journals): Collection
    {
        $collection = $journals->pluck('date');
        if (0 === count($collection)) {
            $collection->push(now(config('app.timezone')));
        }

        return $collection;
    }

    protected function processRules(Collection $set, string $type): void
    {
        Log::debug(sprintf('Will now processRules("%s") for %d journal(s)', $type, $set->count()));
        $array               = $set->pluck('id')->toArray();

        /** @var TransactionJournal $first */
        $first               = $set->first();
        $journalIds          = implode(',', $array);
        $user                = $first->user;
        Log::debug(sprintf('Add local operator for journal(s): %s', $journalIds));

        // collect rules:
        $ruleGroupRepository = app(RuleGroupRepositoryInterface::class);
        $ruleGroupRepository->setUser($user);

        // add the groups to the rule engine.
        // it should run the rules in the group and cancel the group if necessary.
        Log::debug(sprintf('Fire processRules with ALL %s rule groups.', $type));
        $groups              = $ruleGroupRepository->getRuleGroupsWithRules($type);

        // create and fire rule engine.
        $newRuleEngine       = app(RuleEngineInterface::class);
        $newRuleEngine->setUser($user);
        $newRuleEngine->addOperator(['type'  => 'journal_id', 'value' => $journalIds]);
        $newRuleEngine->setRuleGroups($groups);
        $newRuleEngine->fire();
    }

    protected function recalculateRunningBalance(TransactionGroupEventObjects $objects): void
    {
        Log::debug('Now in recalculateRunningBalance');
        if (true === FireflyConfig::get('use_running_balance', config('firefly.feature_flags.running_balance_column'))->data) {
            Log::debug('Running balance is disabled.');

            return;
        }
        // find the earliest date in the set, based on date and _internal_previous_date
        $earliest         = $objects->transactionJournals->pluck('date')->sort()->first();
        $fromInternalDate = $this->getFromInternalDate($objects->transactionJournals->pluck('id')->toArray());
        $earliest         = $fromInternalDate->lt($earliest) ? $fromInternalDate : $earliest;
        Log::debug(sprintf('Found earliest date: %s', $earliest->toW3cString()));

        Log::debug('Found accounts to process', $objects->accounts->pluck('id')->toArray());

        AccountBalanceCalculator::optimizedCalculation($objects->accounts, $earliest);
    }

    private function getFromInternalDate(array $ids): Carbon
    {
        $entries = TransactionJournalMeta::whereIn('transaction_journal_id', $ids)->where('name', '_internal_previous_date')->get(['journal_meta.*']);
        $array   = $entries->toArray();
        $return  = today()->subDay();
        if (count($array) > 0) {
            usort($array, function (array $a, array $b) {
                return Carbon::parse($a['data'])->gt(Carbon::parse($b['data']));
            });

            $date   = Carbon::parse($array[0]['data']);
            $return = $date->lt($return) ? $date : $return;
        }

        return $return;
    }
}
