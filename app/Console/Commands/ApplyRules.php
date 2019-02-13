<?php

/**
 * ApplyRules.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Console\Commands;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\Transaction;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\TransactionRules\Processor;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 *
 * Class ApplyRules
 *
 * @codeCoverageIgnore
 */
class ApplyRules extends Command
{
    use VerifiesAccessToken;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will apply your rules and rule groups on a selection of your transactions.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature
        = 'firefly:apply-rules
                            {--user=1 : The user ID that the import should import for.}
                            {--token= : The user\'s access token.}
                            {--accounts= : A comma-separated list of asset accounts or liabilities to apply your rules to.}
                            {--rule_groups= : A comma-separated list of rule groups to apply. Take the ID\'s of these rule groups from the Firefly III interface.}
                            {--rules= : A comma-separated list of rules to apply. Take the ID\'s of these rules from the Firefly III interface. Using this option overrules the option that selects rule groups.}
                            {--all_rules : If set, will overrule both settings and simply apply ALL of your rules.}
                            {--start_date= : The date of the earliest transaction to be included (inclusive). If omitted, will be your very first transaction ever. Format: YYYY-MM-DD}
                            {--end_date= : The date of the latest transaction to be included (inclusive). If omitted, will be your latest transaction ever. Format: YYYY-MM-DD}';
    /** @var Collection */
    private $accounts;
    /** @var Carbon */
    private $endDate;
    /** @var Collection */
    private $results;
    /** @var Collection */
    private $ruleGroups;
    /** @var Collection */
    private $rules;
    /** @var Carbon */
    private $startDate;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->accounts   = new Collection;
        $this->rules      = new Collection;
        $this->ruleGroups = new Collection;
        $this->results    = new Collection;
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function handle(): int
    {
        if (!$this->verifyAccessToken()) {
            $this->error('Invalid access token.');

            return 1;
        }

        $result = $this->verifyInput();
        if (false === $result) {
            return 1;
        }


        // get transactions from asset accounts.
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser($this->getUser());
        $collector->setAccounts($this->accounts);
        $collector->setRange($this->startDate, $this->endDate);
        $transactions = $collector->getTransactions();
        $count        = $transactions->count();

        // first run all rule groups:
        /** @var RuleGroupRepositoryInterface $ruleGroupRepos */
        $ruleGroupRepos = app(RuleGroupRepositoryInterface::class);
        $ruleGroupRepos->setUser($this->getUser());

        /** @var RuleGroup $ruleGroup */
        foreach ($this->ruleGroups as $ruleGroup) {
            $this->line(sprintf('Going to apply rule group "%s" to %d transaction(s).', $ruleGroup->title, $count));
            $rules = $ruleGroupRepos->getActiveStoreRules($ruleGroup);
            $this->applyRuleSelection($rules, $transactions, true);
        }

        // then run all rules (rule groups should be empty).
        if ($this->rules->count() > 0) {

            $this->line(sprintf('Will apply %d rule(s) to %d transaction(s)', $this->rules->count(), $transactions->count()));
            $this->applyRuleSelection($this->rules, $transactions, false);
        }

        // filter results:
        $this->results = $this->results->unique(
            function (Transaction $transaction) {
                return (int)$transaction->journal_id;
            }
        );

        $this->line('');
        if (0 === $this->results->count()) {
            $this->line('The rules were fired but did not influence any transactions.');
        }
        if ($this->results->count() > 0) {
            $this->line(sprintf('The rule(s) was/were fired, and influenced %d transaction(s).', $this->results->count()));
            foreach ($this->results as $result) {
                $this->line(
                    vsprintf(
                        'Transaction #%d: "%s" (%s %s)',
                        [
                            $result->journal_id,
                            $result->description,
                            $result->transaction_currency_code,
                            round($result->transaction_amount, $result->transaction_currency_dp),
                        ]
                    )
                );
            }
        }

        return 0;
    }

    /**
     * @param Collection $rules
     * @param Collection $transactions
     * @param bool       $breakProcessing
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    private function applyRuleSelection(Collection $rules, Collection $transactions, bool $breakProcessing): void
    {
        $bar = $this->output->createProgressBar($rules->count() * $transactions->count());

        /** @var Rule $rule */
        foreach ($rules as $rule) {
            /** @var Processor $processor */
            $processor = app(Processor::class);
            $processor->make($rule, true);

            /** @var Transaction $transaction */
            foreach ($transactions as $transaction) {
                /** @noinspection DisconnectedForeachInstructionInspection */
                $bar->advance();
                $result = $processor->handleTransaction($transaction);
                if (true === $result) {
                    $this->results->push($transaction);
                }
            }
            if (true === $rule->stop_processing && true === $breakProcessing) {
                $this->line('');
                $this->line(sprintf('Rule #%d ("%s") says to stop processing.', $rule->id, $rule->title));

                return;
            }
        }
        $this->line('');
    }

    /**
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    private function grabAllRules(): void
    {
        if (true === $this->option('all_rules')) {
            /** @var RuleRepositoryInterface $ruleRepos */
            $ruleRepos = app(RuleRepositoryInterface::class);
            $ruleRepos->setUser($this->getUser());
            $this->rules = $ruleRepos->getAll();

            // reset rule groups.
            $this->ruleGroups = new Collection;
        }
    }

    /**
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    private function parseDates(): void
    {
        // parse start date.
        $startDate   = Carbon::create()->startOfMonth();
        $startString = $this->option('start_date');
        if (null === $startString) {
            /** @var JournalRepositoryInterface $repository */
            $repository = app(JournalRepositoryInterface::class);
            $repository->setUser($this->getUser());
            $first = $repository->firstNull();
            if (null !== $first) {
                $startDate = $first->date;
            }
        }
        if (null !== $startString && '' !== $startString) {
            $startDate = Carbon::createFromFormat('Y-m-d', $startString);
        }

        // parse end date
        $endDate   = Carbon::now();
        $endString = $this->option('end_date');
        if (null !== $endString && '' !== $endString) {
            $endDate = Carbon::createFromFormat('Y-m-d', $endString);
        }

        if ($startDate > $endDate) {
            [$endDate, $startDate] = [$startDate, $endDate];
        }

        $this->startDate = $startDate;
        $this->endDate   = $endDate;
    }

    /**
     * @return bool
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    private function verifyInput(): bool
    {
        // verify account.
        $result = $this->verifyInputAccounts();
        if (false === $result) {
            return $result;
        }

        // verify rule groups.
        $result = $this->verifyRuleGroups();
        if (false === $result) {
            return $result;
        }

        // verify rules.
        $result = $this->verifyRules();
        if (false === $result) {
            return $result;
        }

        $this->grabAllRules();
        $this->parseDates();

        //$this->line('Number of rules found: ' . $this->rules->count());
        $this->line('Start date is ' . $this->startDate->format('Y-m-d'));
        $this->line('End date is ' . $this->endDate->format('Y-m-d'));

        return true;
    }

    /**
     * @return bool
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    private function verifyInputAccounts(): bool
    {
        $accountString = $this->option('accounts');
        if (null === $accountString || '' === $accountString) {
            $this->error('Please use the --accounts to indicate the accounts to apply rules to.');

            return false;
        }
        $finalList   = new Collection;
        $accountList = explode(',', $accountString);

        if (0 === \count($accountList)) {
            $this->error('Please use the --accounts to indicate the accounts to apply rules to.');

            return false;
        }

        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $accountRepository->setUser($this->getUser());

        foreach ($accountList as $accountId) {
            $accountId = (int)$accountId;
            $account   = $accountRepository->findNull($accountId);
            if (null !== $account
                && \in_array(
                    $account->accountType->type, [AccountType::DEFAULT, AccountType::DEBT, AccountType::ASSET, AccountType::LOAN, AccountType::MORTGAGE], true
                )) {
                $finalList->push($account);
            }
        }

        if (0 === $finalList->count()) {
            $this->error('Please make sure all accounts in --accounts are asset accounts or liabilities.');

            return false;
        }
        $this->accounts = $finalList;

        return true;

    }

    /**
     * @return bool
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    private function verifyRuleGroups(): bool
    {
        $ruleGroupString = $this->option('rule_groups');
        if (null === $ruleGroupString || '' === $ruleGroupString) {
            // can be empty.
            return true;
        }
        $ruleGroupList = explode(',', $ruleGroupString);

        if (0 === \count($ruleGroupList)) {
            // can be empty.

            return true;
        }
        /** @var RuleGroupRepositoryInterface $ruleGroupRepos */
        $ruleGroupRepos = app(RuleGroupRepositoryInterface::class);
        $ruleGroupRepos->setUser($this->getUser());

        foreach ($ruleGroupList as $ruleGroupId) {
            $ruleGroupId = (int)$ruleGroupId;
            $ruleGroup   = $ruleGroupRepos->find($ruleGroupId);
            $this->ruleGroups->push($ruleGroup);
        }

        return true;
    }

    /**
     * @return bool
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    private function verifyRules(): bool
    {
        $ruleString = $this->option('rules');
        if (null === $ruleString || '' === $ruleString) {
            // can be empty.
            return true;
        }
        $finalList = new Collection;
        $ruleList  = explode(',', $ruleString);

        if (0 === \count($ruleList)) {
            // can be empty.

            return true;
        }
        /** @var RuleRepositoryInterface $ruleRepos */
        $ruleRepos = app(RuleRepositoryInterface::class);
        $ruleRepos->setUser($this->getUser());

        foreach ($ruleList as $ruleId) {
            $ruleId = (int)$ruleId;
            $rule   = $ruleRepos->find($ruleId);
            if (null !== $rule) {
                $finalList->push($rule);
            }
        }
        if ($finalList->count() > 0) {
            // reset rule groups.
            $this->ruleGroups = new Collection;
            $this->rules      = $finalList;
        }

        return true;
    }


}
