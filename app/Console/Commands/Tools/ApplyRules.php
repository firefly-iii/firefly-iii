<?php

/*
 * ApplyRules.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Console\Commands\Tools;

use Carbon\Carbon;
use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Console\Commands\VerifiesAccessToken;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\TransactionRules\Engine\RuleEngineInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ApplyRules extends Command
{
    use ShowsFriendlyMessages;
    use VerifiesAccessToken;

    protected $description = 'This command will apply your rules and rule groups on a selection of your transactions.';

    protected $signature
                           = 'firefly-iii:apply-rules
                            {--user=1 : The user ID.}
                            {--token= : The user\'s access token.}
                            {--accounts= : A comma-separated list of asset accounts or liabilities to apply your rules to.}
                            {--rule_groups= : A comma-separated list of rule groups to apply. Take the ID\'s of these rule groups from the Firefly III interface.}
                            {--rules= : A comma-separated list of rules to apply. Take the ID\'s of these rules from the Firefly III interface. Using this option overrules the option that selects rule groups.}
                            {--all_rules : If set, will overrule both settings and simply apply ALL of your rules.}
                            {--start_date= : The date of the earliest transaction to be included (inclusive). If omitted, will be your very first transaction ever. Format: YYYY-MM-DD}
                            {--end_date= : The date of the latest transaction to be included (inclusive). If omitted, will be your latest transaction ever. Format: YYYY-MM-DD}';
    private array                        $acceptedAccounts;
    private Collection                   $accounts;
    private bool                         $allRules;
    private Carbon                       $endDate;
    private Collection                   $groups;
    private RuleGroupRepositoryInterface $ruleGroupRepository;
    private array                        $ruleGroupSelection;
    private RuleRepositoryInterface      $ruleRepository;
    private array                        $ruleSelection;
    private Carbon                       $startDate;

    /**
     * Execute the console command.
     *
     * @throws FireflyException
     */
    public function handle(): int
    {
        $start             = microtime(true);
        $this->stupidLaravel();
        if (!$this->verifyAccessToken()) {
            $this->friendlyError('Invalid access token.');

            return 1;
        }

        // set user:
        $this->ruleRepository->setUser($this->getUser());
        $this->ruleGroupRepository->setUser($this->getUser());

        $result            = $this->verifyInput();
        if (false === $result) {
            return 1;
        }

        $this->allRules    = $this->option('all_rules');

        // always get all the rules of the user.
        $this->grabAllRules();

        // loop all groups and rules and indicate if they're included:
        $rulesToApply      = $this->getRulesToApply();
        $count             = $rulesToApply->count();
        if (0 === $count) {
            $this->friendlyError('No rules or rule groups have been included.');
            $this->friendlyWarning('Make a selection using:');
            $this->friendlyWarning('    --rules=1,2,...');
            $this->friendlyWarning('    --rule_groups=1,2,...');
            $this->friendlyWarning('    --all_rules');

            return 1;
        }

        // create new rule engine:
        /** @var RuleEngineInterface $ruleEngine */
        $ruleEngine        = app(RuleEngineInterface::class);
        $ruleEngine->setRules($rulesToApply);
        $ruleEngine->setUser($this->getUser());

        // add the accounts as filter:
        $filterAccountList = [];
        foreach ($this->accounts as $account) {
            $filterAccountList[] = $account->id;
        }
        $list              = implode(',', $filterAccountList);
        $ruleEngine->addOperator(['type' => 'account_id', 'value' => $list]);

        // add the date as a filter:
        $ruleEngine->addOperator(['type' => 'date_after', 'value' => $this->startDate->format('Y-m-d')]);
        $ruleEngine->addOperator(['type' => 'date_before', 'value' => $this->endDate->format('Y-m-d')]);

        // start running rules.
        $this->friendlyLine(sprintf('Will apply %d rule(s) to your transaction(s).', $count));

        // fire the rule(s)
        $ruleEngine->fire();

        $this->friendlyLine('');
        $end               = round(microtime(true) - $start, 2);
        $this->friendlyPositive(sprintf('Done in %s seconds!', $end));

        return 0;
    }

    /**
     * Laravel will execute ALL __construct() methods for ALL commands whenever a SINGLE command is
     * executed. This leads to noticeable slow-downs and class calls. To prevent this, this method should
     * be called from the handle method instead of using the constructor to initialize the command.
     */
    private function stupidLaravel(): void
    {
        $this->allRules            = false;
        $this->accounts            = new Collection();
        $this->ruleSelection       = [];
        $this->ruleGroupSelection  = [];
        $this->ruleRepository      = app(RuleRepositoryInterface::class);
        $this->ruleGroupRepository = app(RuleGroupRepositoryInterface::class);
        $this->acceptedAccounts    = [AccountTypeEnum::DEFAULT->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::ASSET->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::MORTGAGE->value];
        $this->groups              = new Collection();
    }

    /**
     * @throws FireflyException
     */
    private function verifyInput(): bool
    {
        // verify account.
        $result = $this->verifyInputAccounts();
        if (false === $result) {
            return false;
        }

        // verify rule groups.
        $this->verifyInputRuleGroups();

        // verify rules.
        $this->verifyInputRules();

        $this->verifyInputDates();

        return true;
    }

    /**
     * @throws FireflyException
     */
    private function verifyInputAccounts(): bool
    {
        $accountString     = $this->option('accounts');
        if (null === $accountString || '' === $accountString) {
            $this->friendlyError('Please use the --accounts option to indicate the accounts to apply rules to.');

            return false;
        }
        $finalList         = new Collection();
        $accountList       = explode(',', $accountString);

        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $accountRepository->setUser($this->getUser());
        foreach ($accountList as $accountId) {
            $accountId = (int) $accountId;
            $account   = $accountRepository->find($accountId);
            if (null !== $account && in_array($account->accountType->type, $this->acceptedAccounts, true)) {
                $finalList->push($account);
            }
        }

        if (0 === $finalList->count()) {
            $this->friendlyError('Please make sure all accounts in --accounts are asset accounts or liabilities.');

            return false;
        }
        $this->accounts    = $finalList;

        return true;
    }

    private function verifyInputRuleGroups(): bool
    {
        $ruleGroupString = $this->option('rule_groups');
        if (null === $ruleGroupString || '' === $ruleGroupString) {
            // can be empty.
            return true;
        }
        $ruleGroupList   = explode(',', $ruleGroupString);

        foreach ($ruleGroupList as $ruleGroupId) {
            $ruleGroup = $this->ruleGroupRepository->find((int) $ruleGroupId);
            if (true === $ruleGroup->active) {
                $this->ruleGroupSelection[] = $ruleGroup->id;
            }
            if (false === $ruleGroup->active) {
                $this->friendlyWarning(sprintf('Will ignore inactive rule group #%d ("%s")', $ruleGroup->id, $ruleGroup->title));
            }
        }

        return true;
    }

    private function verifyInputRules(): bool
    {
        $ruleString = $this->option('rules');
        if (null === $ruleString || '' === $ruleString) {
            // can be empty.
            return true;
        }
        $ruleList   = explode(',', $ruleString);

        foreach ($ruleList as $ruleId) {
            $rule = $this->ruleRepository->find((int) $ruleId);
            if (null !== $rule && true === $rule->active) {
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
        $inputStart      = today(config('app.timezone'))->startOfMonth();
        $startString     = $this->option('start_date');
        if (null === $startString) {
            /** @var JournalRepositoryInterface $repository */
            $repository = app(JournalRepositoryInterface::class);
            $repository->setUser($this->getUser());
            $first      = $repository->firstNull();
            if (null !== $first) {
                $inputStart = $first->date;
            }
        }
        if (null !== $startString && '' !== $startString) {
            $inputStart = Carbon::createFromFormat('Y-m-d', $startString);
        }

        // parse end date
        $inputEnd        = today(config('app.timezone'));
        $endString       = $this->option('end_date');
        if (null !== $endString && '' !== $endString) {
            $inputEnd = Carbon::createFromFormat('Y-m-d', $endString);
        }
        if (null === $inputEnd || null === $inputStart) {
            Log::error('Could not parse start or end date in verifyInputDate().');

            return;
        }

        if ($inputStart > $inputEnd) {
            [$inputEnd, $inputStart] = [$inputStart, $inputEnd];
        }

        $this->startDate = $inputStart;
        $this->endDate   = $inputEnd;
    }

    private function grabAllRules(): void
    {
        $this->groups = $this->ruleGroupRepository->getActiveGroups();
    }

    private function getRulesToApply(): Collection
    {
        $rulesToApply = new Collection();

        /** @var RuleGroup $group */
        foreach ($this->groups as $group) {
            $rules = $this->ruleGroupRepository->getActiveStoreRules($group);

            /** @var Rule $rule */
            foreach ($rules as $rule) {
                // if in rule selection, or group in selection or all rules, it's included.
                $test = $this->includeRule($rule, $group);
                if (true === $test) {
                    app('log')->debug(sprintf('Will include rule #%d "%s"', $rule->id, $rule->title));
                    $rulesToApply->push($rule);
                }
            }
        }

        return $rulesToApply;
    }

    private function includeRule(Rule $rule, RuleGroup $group): bool
    {
        return in_array($group->id, $this->ruleGroupSelection, true)
               || in_array($rule->id, $this->ruleSelection, true)
               || $this->allRules;
    }
}
