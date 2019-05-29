<?php


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
use FireflyIII\TransactionRules\Processor;
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
    /** @var Collection */
    private $results;
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
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->allRules            = false;
        $this->accounts            = new Collection;
        $this->ruleSelection       = [];
        $this->ruleGroupSelection  = [];
        $this->results             = new Collection;
        $this->ruleRepository      = app(RuleRepositoryInterface::class);
        $this->ruleGroupRepository = app(RuleGroupRepositoryInterface::class);
        $this->acceptedAccounts    = [AccountType::DEFAULT, AccountType::DEBT, AccountType::ASSET, AccountType::LOAN, AccountType::MORTGAGE];
        $this->groups              = new Collection;
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws FireflyException
     */
    public function handle(): int
    {
        if (!$this->verifyAccessToken()) {
            $this->error('Invalid access token.');

            return 1;
        }
        // set user:
        $this->ruleRepository->setUser($this->getUser());
        $this->ruleGroupRepository->setUser($this->getUser());

        $result = $this->verifyInput();
        if (false === $result) {
            return 1;
        }

        $this->allRules = $this->option('all_rules');

        $this->grabAllRules();

        // loop all groups and rules and indicate if they're included:
        $count = 0;
        /** @var RuleGroup $group */
        foreach ($this->groups as $group) {
            /** @var Rule $rule */
            foreach ($group->rules as $rule) {
                // if in rule selection, or group in selection or all rules, it's included.
                if ($this->includeRule($rule, $group)) {
                    $count++;
                }
            }
        }
        if (0 === $count) {
            $this->error('No rules or rule groups have been included.');
            $this->warn('Make a selection using:');
            $this->warn('    --rules=1,2,...');
            $this->warn('    --rule_groups=1,2,...');
            $this->warn('    --all_rules');
        }


        // get transactions from asset accounts.
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->getUser());
        $collector->setAccounts($this->accounts);
        $collector->setRange($this->startDate, $this->endDate);
        $journals = $collector->getExtractedJournals();

        // start running rules.
        $this->line(sprintf('Will apply %d rules to %d transactions.', $count, count($journals)));

        // start looping.
        $bar = $this->output->createProgressBar(count($journals) * $count);
        Log::debug(sprintf('Now looping %d transactions.', count($journals)));
        /** @var array $journal */
        foreach ($journals as $journal) {
            Log::debug('Start of new journal.');
            foreach ($this->groups as $group) {
                $groupTriggered = false;
                /** @var Rule $rule */
                foreach ($group->rules as $rule) {
                    $ruleTriggered = false;
                    // if in rule selection, or group in selection or all rules, it's included.
                    if ($this->includeRule($rule, $group)) {
                        /** @var Processor $processor */
                        $processor = app(Processor::class);
                        $processor->make($rule, true);
                        $ruleTriggered = $processor->handleJournalArray($journal);
                        $bar->advance();
                        if ($ruleTriggered) {
                            $groupTriggered = true;
                        }
                    }

                    // if the rule is triggered and stop processing is true, cancel the entire group.
                    if ($ruleTriggered && $rule->stop_processing) {
                        Log::info('Break out group because rule was triggered.');
                        break;
                    }
                }
                // if group is triggered and stop processing is true, cancel the whole thing.
                if ($groupTriggered && $group->stop_processing) {
                    Log::info('Break out ALL because group was triggered.');
                    break;
                }
            }
            Log::debug('Done with all rules for this group + done with journal.');
        }
        $this->line('');
        $this->line('Done!');

        return 0;
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
        $result = $this->verifyInputRuleGroups();
        if (false === $result) {
            return $result;
        }

        // verify rules.
        $result = $this->verifyInputRules();
        if (false === $result) {
            return $result;
        }

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

        if (0 === count($accountList)) {
            $this->error('Please use the --accounts option to indicate the accounts to apply rules to.');

            return false;
        }

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

        if (0 === count($ruleGroupList)) {
            // can be empty.
            return true;
        }
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

        if (0 === count($ruleList)) {
            // can be empty.

            return true;
        }
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
}
