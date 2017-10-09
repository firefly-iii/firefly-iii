<?php
/**
 * ExecuteRuleOnExistingTransactions.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
 * Class ExecuteRuleOnExistingTransactions
 *
 * @package FireflyIII\Jobs
 */
class ExecuteRuleOnExistingTransactions extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /** @var Collection */
    private $accounts;
    /** @var  Carbon */
    private $endDate;
    /** @var Rule */
    private $rule;
    /** @var  Carbon */
    private $startDate;
    /** @var  User */
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
        $processor    = Processor::make($this->rule, true);
        $hits         = 0;
        $misses       = 0;
        $total        = 0;
        // Execute the rules for each transaction
        foreach ($transactions as $transaction) {
            $total++;
            $result = $processor->handleTransaction($transaction);
            if ($result) {
                $hits++;
            }
            if (!$result) {
                $misses++;
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

}
