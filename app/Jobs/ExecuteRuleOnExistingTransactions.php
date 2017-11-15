<?php
/**
 * ExecuteRuleOnExistingTransactions.php
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
use FireflyIII\Models\Rule;
use FireflyIII\TransactionRules\Processor;
use FireflyIII\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Log;

/**
 * Class ExecuteRuleOnExistingTransactions.
 */
class ExecuteRuleOnExistingTransactions extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /** @var Collection */
    private $accounts;
    /** @var Carbon */
    private $endDate;
    /** @var Rule */
    private $rule;
    /** @var Carbon */
    private $startDate;
    /** @var User */
    private $user;

    /**
     * Create a new job instance.
     *
     * @param Rule $rule
     */
    public function __construct(Rule $rule)
    {
        $this->rule = $rule;
    }

    /**
     * @return Collection
     */
    public function getAccounts(): Collection
    {
        return $this->accounts;
    }

    /**
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
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Lookup all journals that match the parameters specified
        $transactions = $this->collectJournals();
        $processor    = Processor::make($this->rule, true);
        $hits         = 0;
        $misses       = 0;
        $total        = 0;
        // Execute the rules for each transaction
        foreach ($transactions as $transaction) {
            ++$total;
            $result = $processor->handleTransaction($transaction);
            if ($result) {
                ++$hits;
            }
            if (!$result) {
                ++$misses;
            }
            Log::info(sprintf('Current progress: %d Transactions. Hits: %d, misses: %d', $total, $hits, $misses));
            // Stop processing this group if the rule specifies 'stop_processing'
            if ($processor->getRule()->stop_processing) {
                break;
            }
        }
        Log::info(sprintf('Total transactions: %d. Hits: %d, misses: %d', $total, $hits, $misses));
    }

    /**
     * Collect all journals that should be processed.
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
}
