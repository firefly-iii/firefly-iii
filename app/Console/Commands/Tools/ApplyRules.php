<?php


/**
 * ApplyRules.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Console\Commands\Tools;


use Carbon\Carbon;
use FireflyIII\Console\Commands\VerifiesAccessToken;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\TransactionRules\Engine\RuleEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Log;

/**
 * Class ApplyRules
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
        = 'firefly-iii:apply-rules
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
    /** @var array */
    private $acceptedAccounts;
    /** @var Carbon */
    private $endDate;
    /** @var array */
    private $ruleGroupSelection;
    /** @var array */
    private $ruleSelection;
    /** @var Carbon */
    private $startDate;
    /** @var Collection */
    private $groups;
    /** @var bool */
    private $allRules;

    /** @var RuleRepositoryInterface */
    private $ruleRepository;

    /** @var RuleGroupRepositoryInterface */
    private $ruleGroupRepository;

    /**
     * Execute the console command.
     *
     * @return int
     * @throws FireflyException
     */
    public function handle(): int
    {
        $this->stupidLaravel();
        // @codeCoverageIgnoreStart
        if (!$this->verifyAccessToken()) {
            $this->error('Invalid access token.');

            return 1;
        }
        // @codeCoverageIgnoreEnd

        // set user:
        $this->ruleRepository->setUser($this->getUser());
        $this->ruleGroupRepository->setUser($this->getUser());

        $result = $this->verifyInput();
        if (false === $result) {
            return 1;
        }

        $this->allRules = $this->option('all_rules');

        // always get all the rules of the user.
        $this->grabAllRules();

        // loop all groups and rules and indicate if they're included:
        $rulesToApply = $this->getRulesToApply();
        $count        = count($rulesToApply);
        if (0 === $count) {
            $this->error('No rules or rule groups have been included.');
            $this->warn('Make a selection using:');
            $this->warn('    --rules=1,2,...');
            $this->warn('    --rule_groups=1,2,...');
            $this->warn('    --all_rules');
        }

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->getUser());
        $collector->setAccounts($this->accounts);
        $collector->setRange($this->startDate, $this->endDate);
        $journals = $collector->getExtractedJournals();

        // start running rules.
        $this->line(sprintf('Will apply %d rule(s) to %d transaction(s).', $count, count($journals)));

        // start looping.
        /** @var RuleEngine $ruleEngine */
        $ruleEngine = app(RuleEngine::class);
        $ruleEngine->setUser($this->getUser());
        $ruleEngine->setRulesToApply($rulesToApply);

        // for this call, the rule engine only includes "store" rules:
        $ruleEngine->setTriggerMode(RuleEngine::TRIGGER_STORE);

        $bar = $this->output->createProgressBar(count($journals));
        Log::debug(sprintf('Now looping %d transactions.', count($journals)));
        /** @var array $journal */
        foreach ($journals as $journal) {
            Log::debug('Start of new journal.');
            $ruleEngine->processJournalArray($journal);
            Log::debug('Done with all rules for this group + done with journal.');
            /** @noinspection DisconnectedForeachInstructionInspection */
            $bar->advance();
        }
        $this->line('');
        $this->line('Done!');

        return 0;
    }

    /**
     * Laravel will execute ALL __construct() methods for ALL commands whenever a SINGLE command is
     * executed. This leads to noticeable slow-downs and class calls. To prevent this, this method should
     * be called from the handle method instead of using the constructor to initialize the command.
     *
     * @codeCoverageIgnore
     */
    private function stupidLaravel(): void
    {
        $this->allRules            = false;
        $this->accounts            = new Collection;
        $this->ruleSelection       = [];
        $this->ruleGroupSelection  = [];
        $this->ruleRepository      = app(RuleRepositoryInterface::class);
        $this->ruleGroupRepository = app(RuleGroupRepositoryInterface::class);
        $this->acceptedAccounts    = [AccountType::DEFAULT, AccountType::DEBT, AccountType::ASSET, AccountType::LOAN, AccountType::MORTGAGE];
        $this->groups              = new Collection;
    }

    /**
     * @return bool
     * @throws FireflyException
     */
    private function verifyInput(): bool
    {
        // verify account.
        $result = $this->verifyInputAccounts();
        if (false === $result) {
            return $result;
        }

        // verify rule groups.
        $this->verifyInputRuleGroups();

        // verify rules.
        $this->verifyInputRules();

        $this->verifyInputDates();

        return true;
    }

    /**
     * @return bool
     * @throws FireflyException
     */
    private function verifyInputAccounts(): bool
    {
        $accountString = $this->option('accounts');
        if (null === $accountString || '' === $accountString) {
            $this->error('Please use the --accounts option to indicate the accounts to apply rules to.');

            return false;
        }
        $finalList   = new Collection;
        $accountList = explode(',', $accountString);

        // @codeCoverageIgnoreStart
        if (0 === count($accountList)) {
            $this->error('Please use the --accounts option to indicate the accounts to apply rules to.');

            return false;
        }
        // @codeCoverageIgnoreEnd

        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $accountRepository->setUser($this->getUser());


        foreach ($accountList as $accountId) {
            $accountId = (int)$accountId;
            $account   = $accountRepository->findNull($accountId);
            if (null !== $account && in_array($account->accountType->type, $this->acceptedAccounts, true)) {
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
     */
    private function verifyInputRuleGroups(): bool
    {
        $ruleGroupString = $this->option('rule_groups');
        if (null === $ruleGroupString || '' === $ruleGroupString) {
            // can be empty.
            return true;
        }
        $ruleGroupList = explode(',', $ruleGroupString);
        // @codeCoverageIgnoreStart
        if (0 === count($ruleGroupList)) {
            // can be empty.
            return true;
        }
        // @codeCoverageIgnoreEnd
        foreach ($ruleGroupList as $ruleGroupId) {
            $ruleGroup = $this->ruleGroupRepository->find((int)$ruleGroupId);
            if ($ruleGroup->active) {
                $this->ruleGroupSelection[] = $ruleGroup->id;
            }
            if (false === $ruleGroup->active) {
                $this->warn(sprintf('Will ignore inactive rule group #%d ("%s")', $ruleGroup->id, $ruleGroup->title));
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    private function verifyInputRules(): bool
    {
        $ruleString = $this->option('rules');
        if (null === $ruleString || '' === $ruleString) {
            // can be empty.
            return true;
        }
        $ruleList = explode(',', $ruleString);

        // @codeCoverageIgnoreStart
        if (0 === count($ruleList)) {
            // can be empty.

            return true;
        }
        // @codeCoverageIgnoreEnd

        foreach ($ruleList as $ruleId) {
            $rule = $this->ruleRepository->find((int)$ruleId);
            if (null !== $rule && $rule->active) {
                $this->ruleSelection[] = $rule->id;
            }
        }

        return true;
    }

    /**
     * @throws FireflyException
     */
    private function verifyInputDates(): void
    {
        // parse start date.
        $startDate   = Carbon::now()->startOfMonth();
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
     */
    private function grabAllRules(): void
    {
        $this->groups = $this->ruleGroupRepository->getActiveGroups();
    }

    /**
     * @param Rule $rule
     * @param RuleGroup $group
     * @return bool
     */
    private function includeRule(Rule $rule, RuleGroup $group): bool
    {
        return in_array($group->id, $this->ruleGroupSelection, true) ||
               in_array($rule->id, $this->ruleSelection, true) ||
               $this->allRules;
    }

    /**
     * @return array
     */
    private function getRulesToApply(): array
    {
        $rulesToApply = [];
        /** @var RuleGroup $group */
        foreach ($this->groups as $group) {
            $rules = $this->ruleGroupRepository->getActiveStoreRules($group);
            /** @var Rule $rule */
            foreach ($rules as $rule) {
                // if in rule selection, or group in selection or all rules, it's included.
                $test = $this->includeRule($rule, $group);
                if (true === $test) {
                    Log::debug(sprintf('Will include rule #%d "%s"', $rule->id, $rule->title));
                    $rulesToApply[] = $rule->id;
                }
            }
        }

        return $rulesToApply;
    }
}
