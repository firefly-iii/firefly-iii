<?php
/**
 * ExecuteRuleGroupOnExistingTransactions.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Jobs;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\RuleGroup;
use FireflyIII\TransactionRules\Processor;
use FireflyIII\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Class ExecuteRuleGroupOnExistingTransactions
 *
 * @package FireflyIII\Jobs
 */
class ExecuteRuleGroupOnExistingTransactions extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /** @var Collection */
    private $accounts;
    /** @var  Carbon */
    private $endDate;
    /** @var RuleGroup */
    private $ruleGroup;
    /** @var  Carbon */
    private $startDate;
    /** @var  User */
    private $user;

    /**
     * Create a new job instance.
     *
     * @param RuleGroup $ruleGroup
     */
    public function __construct(RuleGroup $ruleGroup)
    {
        $this->ruleGroup = $ruleGroup;
    }

    /**
     * @return Collection
     */
    public function getAccounts(): Collection
    {
        return $this->accounts;
    }

    /**
     *
     * @param Collection $accounts
     */
    public function setAccounts(Collection $accounts)
    {
        $this->accounts = $accounts;
    }

    /**
     * @return \Carbon\Carbon
     */
    public function getEndDate(): Carbon
    {
        return $this->endDate;
    }

    /**
     *
     * @param Carbon $date
     */
    public function setEndDate(Carbon $date)
    {
        $this->endDate = $date;
    }

    /**
     * @return \Carbon\Carbon
     */
    public function getStartDate(): Carbon
    {
        return $this->startDate;
    }

    /**
     *
     * @param Carbon $date
     */
    public function setStartDate(Carbon $date)
    {
        $this->startDate = $date;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     *
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Lookup all journals that match the parameters specified
        $transactions = $this->collectJournals();

        // Find processors for each rule within the current rule group
        $processors = $this->collectProcessors();

        // Execute the rules for each transaction
        foreach ($transactions as $transaction) {
            /** @var Processor $processor */
            foreach ($processors as $processor) {
                $processor->handleTransaction($transaction);

                // Stop processing this group if the rule specifies 'stop_processing'
                if ($processor->getRule()->stop_processing) {
                    break;
                }
            }
        }
    }

    /**
     * Collect all journals that should be processed
     *
     * @return Collection
     */
    protected function collectJournals()
    {
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setAccounts($this->accounts)->setRange($this->startDate, $this->endDate);

        return $collector->getJournals();
    }

    /**
     * Collects a list of rule processors, one for each rule within the rule group
     *
     * @return array
     */
    protected function collectProcessors()
    {
        // Find all rules belonging to this rulegroup
        $rules = $this->ruleGroup->rules()
                                 ->leftJoin('rule_triggers', 'rules.id', '=', 'rule_triggers.rule_id')
                                 ->where('rule_triggers.trigger_type', 'user_action')
                                 ->where('rule_triggers.trigger_value', 'store-journal')
                                 ->where('rules.active', 1)
                                 ->get(['rules.*']);

        // Create a list of processors for these rules
        return array_map(
            function ($rule) {
                return Processor::make($rule);
            },
            $rules->all()
        );
    }
}
