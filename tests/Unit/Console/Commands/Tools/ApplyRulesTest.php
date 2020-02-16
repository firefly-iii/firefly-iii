<?php
/**
 * ApplyRulesTest.php
 * Copyright (c) 2019 james@firefly-iii.org
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

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\Tools;


use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\TransactionRules\Engine\RuleEngine;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class ApplyRulesTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ApplyRulesTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * Basic call with everything perfect (and ALL rules).
     *
     * @covers \FireflyIII\Console\Commands\Tools\ApplyRules
     */
    public function testHandle(): void
    {
        $ruleRepos      = $this->mock(RuleRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $collector      = $this->mock(GroupCollectorInterface::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $ruleEngine     = $this->mock(RuleEngine::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);

        // data
        $asset   = $this->getRandomAsset();
        $journal = $this->getRandomWithdrawal();
        $group   = $this->user()->ruleGroups()->first();
        $rule    = $this->user()->rules()->first();
        $groups  = new Collection([$group]);
        $rules   = new Collection([$rule]);

        // expected calls:
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($this->user());
        $ruleRepos->shouldReceive('setUser')->atLeast()->once();
        $ruleGroupRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('findNull')->atLeast()->once()->withArgs([1])->andReturn($asset);
        $journalRepos->shouldReceive('firstNull')->atLeast()->once()->andReturn($journal);

        $ruleGroupRepos->shouldReceive('getActiveGroups')->atLeast()->once()->andReturn($groups);
        $ruleGroupRepos->shouldReceive('getActiveStoreRules')->atLeast()->once()->andReturn($rules);


        $collector->shouldReceive('setUser')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setAccounts')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setRange')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn([[], [], []]);

        $ruleEngine->shouldReceive('setUser')->atLeast()->once();
        $ruleEngine->shouldReceive('setRulesToApply')->atLeast()->once();
        $ruleEngine->shouldReceive('processJournalArray')->times(3);
        $ruleEngine->shouldReceive('setTriggerMode')->atLeast()->once()->withArgs([RuleEngine::TRIGGER_STORE]);

        $parameters = [
            '--user=1',
            '--token=token',
            '--accounts=1',
            '--all_rules',
        ];

        // mock Preferences Facade:
        $pref = new Preference;
        $pref->data = 'token';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'access_token',null])->atLeast()->once()->andReturn($pref);

        $this->artisan('firefly-iii:apply-rules ' . implode(' ', $parameters))
             ->expectsOutput('Will apply 1 rule(s) to 3 transaction(s).')
             ->expectsOutput('Done!')
             ->assertExitCode(0);

        // this method changes no objects so there is nothing to verify.
    }

    /**
     * Basic call with everything perfect (and ALL rules), but no rules will be selected.
     *
     * @covers \FireflyIII\Console\Commands\Tools\ApplyRules
     */
    public function testHandEmpty(): void
    {
        $ruleRepos      = $this->mock(RuleRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $collector      = $this->mock(GroupCollectorInterface::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $ruleEngine     = $this->mock(RuleEngine::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);

        // data
        $asset   = $this->getRandomAsset();
        $journal = $this->getRandomWithdrawal();
        $group   = $this->user()->ruleGroups()->first();
        $groups  = new Collection([$group]);

        // expected calls:
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($this->user());
        $ruleRepos->shouldReceive('setUser')->atLeast()->once();
        $ruleGroupRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('findNull')->atLeast()->once()->withArgs([1])->andReturn($asset);
        $journalRepos->shouldReceive('firstNull')->atLeast()->once()->andReturn($journal);

        $ruleGroupRepos->shouldReceive('getActiveGroups')->atLeast()->once()->andReturn($groups);
        $ruleGroupRepos->shouldReceive('getActiveStoreRules')->atLeast()->once()->andReturn(new Collection);


        $collector->shouldReceive('setUser')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setAccounts')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setRange')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn([[], [], []]);

        $ruleEngine->shouldReceive('setUser')->atLeast()->once();
        $ruleEngine->shouldReceive('setRulesToApply')->atLeast()->once();
        $ruleEngine->shouldReceive('processJournalArray')->times(3);
        $ruleEngine->shouldReceive('setTriggerMode')->atLeast()->once()->withArgs([RuleEngine::TRIGGER_STORE]);

        $parameters = [
            '--user=1',
            '--token=token',
            '--accounts=1',
            '--all_rules',
        ];

        // mock Preferences Facade:
        $pref = new Preference;
        $pref->data = 'token';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'access_token',null])->atLeast()->once()->andReturn($pref);

        $this->artisan('firefly-iii:apply-rules ' . implode(' ', $parameters))
             ->expectsOutput('No rules or rule groups have been included.')
             ->expectsOutput('Done!')
             ->assertExitCode(0);
    }


    /**
     * Basic call with everything perfect (and ALL rules) and dates.
     *
     * @covers \FireflyIII\Console\Commands\Tools\ApplyRules
     */
    public function testHandleDate(): void
    {
        $ruleRepos      = $this->mock(RuleRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $this->mock(JournalRepositoryInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $ruleEngine   = $this->mock(RuleEngine::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);

        // data
        $asset  = $this->getRandomAsset();
        $group  = $this->user()->ruleGroups()->first();
        $rule   = $this->user()->rules()->first();
        $groups = new Collection([$group]);
        $rules  = new Collection([$rule]);

        // expected calls:
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($this->user());
        $ruleRepos->shouldReceive('setUser')->atLeast()->once();
        $ruleGroupRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('findNull')->atLeast()->once()->withArgs([1])->andReturn($asset);

        $ruleGroupRepos->shouldReceive('getActiveGroups')->atLeast()->once()->andReturn($groups);
        $ruleGroupRepos->shouldReceive('getActiveStoreRules')->atLeast()->once()->andReturn($rules);


        $collector->shouldReceive('setUser')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setAccounts')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setRange')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn([[], [], []]);

        $ruleEngine->shouldReceive('setUser')->atLeast()->once();
        $ruleEngine->shouldReceive('setRulesToApply')->atLeast()->once();
        $ruleEngine->shouldReceive('processJournalArray')->times(3);
        $ruleEngine->shouldReceive('setTriggerMode')->atLeast()->once()->withArgs([RuleEngine::TRIGGER_STORE]);

        $parameters = [
            '--user=1',
            '--token=token',
            '--accounts=1',
            '--all_rules',
            '--start_date=2019-01-31',
            '--end_date=2019-01-01',
        ];

        // mock Preferences Facade:
        $pref = new Preference;
        $pref->data = 'token';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'access_token',null])->atLeast()->once()->andReturn($pref);

        $this->artisan('firefly-iii:apply-rules ' . implode(' ', $parameters))
             ->expectsOutput('Will apply 1 rule(s) to 3 transaction(s).')
             ->expectsOutput('Done!')
             ->assertExitCode(0);
    }

    /**
     * Will submit some rules to apply.
     *
     * @covers \FireflyIII\Console\Commands\Tools\ApplyRules
     */
    public function testHandleRules(): void
    {
        $ruleRepos      = $this->mock(RuleRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $collector      = $this->mock(GroupCollectorInterface::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $ruleEngine     = $this->mock(RuleEngine::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);

        // data
        $asset        = $this->getRandomAsset();
        $journal      = $this->getRandomWithdrawal();
        $group        = $this->user()->ruleGroups()->first();
        $groups       = new Collection([$group]);
        $activeRule   = $this->user()->rules()->where('active', 1)->inRandomOrder()->first();
        $inactiveRule = $this->user()->rules()->where('active', 0)->inRandomOrder()->first();
        $rules        = new Collection([$activeRule]);

        // expected calls:
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($this->user());
        $ruleRepos->shouldReceive('setUser')->atLeast()->once();
        $ruleGroupRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('findNull')->atLeast()->once()->withArgs([1])->andReturn($asset);
        $journalRepos->shouldReceive('firstNull')->atLeast()->once()->andReturn($journal);

        $ruleRepos->shouldReceive('find')->atLeast()->once()->withArgs([$activeRule->id])->andReturn($activeRule);
        $ruleRepos->shouldReceive('find')->atLeast()->once()->withArgs([$inactiveRule->id])->andReturn($inactiveRule);

        $ruleGroupRepos->shouldReceive('getActiveGroups')->atLeast()->once()->andReturn($groups);
        $ruleGroupRepos->shouldReceive('getActiveStoreRules')->atLeast()->once()->andReturn($rules);


        $collector->shouldReceive('setUser')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setAccounts')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setRange')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn([[], [], []]);

        $ruleEngine->shouldReceive('setUser')->atLeast()->once();
        $ruleEngine->shouldReceive('setRulesToApply')->atLeast()->once();
        $ruleEngine->shouldReceive('processJournalArray')->times(3);
        $ruleEngine->shouldReceive('setTriggerMode')->atLeast()->once()->withArgs([RuleEngine::TRIGGER_STORE]);

        $parameters = [
            '--user=1',
            '--token=token',
            '--accounts=1',
            sprintf('--rules=%d,%d', $activeRule->id, $inactiveRule->id),
        ];

        // mock Preferences Facade:
        $pref = new Preference;
        $pref->data = 'token';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'access_token',null])->atLeast()->once()->andReturn($pref);

        $this->artisan('firefly-iii:apply-rules ' . implode(' ', $parameters))
             ->expectsOutput('Will apply 1 rule(s) to 3 transaction(s).')
             ->expectsOutput('Done!')
             ->assertExitCode(0);
    }

    /**
     * Basic call with two rule groups. One active, one inactive.
     *
     * @covers \FireflyIII\Console\Commands\Tools\ApplyRules
     */
    public function testHandleRuleGroups(): void
    {
        $ruleRepos      = $this->mock(RuleRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $collector      = $this->mock(GroupCollectorInterface::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $ruleEngine     = $this->mock(RuleEngine::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);

        $activeGroup   = $this->user()->ruleGroups()->where('active', 1)->inRandomOrder()->first();
        $inactiveGroup = $this->user()->ruleGroups()->where('active', 0)->inRandomOrder()->first();

        // data
        $asset   = $this->getRandomAsset();
        $journal = $this->getRandomWithdrawal();
        $rule    = $this->user()->rules()->first();
        $groups  = new Collection([$activeGroup]);
        $rules   = new Collection([$rule]);

        // expected calls:
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($this->user());
        $ruleRepos->shouldReceive('setUser')->atLeast()->once();
        $ruleGroupRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('findNull')->atLeast()->once()->withArgs([1])->andReturn($asset);
        $journalRepos->shouldReceive('firstNull')->atLeast()->once()->andReturn($journal);

        $ruleGroupRepos->shouldReceive('getActiveGroups')->atLeast()->once()->andReturn($groups);
        $ruleGroupRepos->shouldReceive('getActiveStoreRules')->atLeast()->once()->andReturn($rules);
        $ruleGroupRepos->shouldReceive('find')->atLeast()->once()->withArgs([$activeGroup->id])->andReturn($activeGroup);
        $ruleGroupRepos->shouldReceive('find')->atLeast()->once()->withArgs([$inactiveGroup->id])->andReturn($inactiveGroup);

        $collector->shouldReceive('setUser')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setAccounts')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setRange')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn([[], [], []]);

        $ruleEngine->shouldReceive('setUser')->atLeast()->once();
        $ruleEngine->shouldReceive('setRulesToApply')->atLeast()->once();
        $ruleEngine->shouldReceive('processJournalArray')->times(3);
        $ruleEngine->shouldReceive('setTriggerMode')->atLeast()->once()->withArgs([RuleEngine::TRIGGER_STORE]);

        $parameters = [
            '--user=1',
            '--token=token',
            '--accounts=1',
            sprintf('--rule_groups=%d,%d', $activeGroup->id, $inactiveGroup->id),
        ];

        // mock Preferences Facade:
        $pref = new Preference;
        $pref->data = 'token';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'access_token',null])->atLeast()->once()->andReturn($pref);

        $this->artisan('firefly-iii:apply-rules ' . implode(' ', $parameters))
             ->expectsOutput(sprintf('Will ignore inactive rule group #%d ("%s")', $inactiveGroup->id, $inactiveGroup->title))
            // one rule out of 2 groups:
             ->expectsOutput('Will apply 1 rule(s) to 3 transaction(s).')
             ->expectsOutput('Done!')
             ->assertExitCode(0);
    }

    /**
     * Basic call but no accounts submitted.
     *
     * @covers \FireflyIII\Console\Commands\Tools\ApplyRules
     */
    public function testHandleNoAccounts(): void
    {
        $ruleRepos      = $this->mock(RuleRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $this->mock(JournalRepositoryInterface::class);
        $this->mock(GroupCollectorInterface::class);
        $this->mock(AccountRepositoryInterface::class);
        $this->mock(RuleEngine::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);

        // expected calls:
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($this->user());
        $ruleRepos->shouldReceive('setUser')->atLeast()->once();
        $ruleGroupRepos->shouldReceive('setUser')->atLeast()->once();


        $parameters = [
            '--user=1',
            '--token=token',
            '--accounts=',
            '--all_rules',
        ];

        // mock Preferences Facade:
        $pref = new Preference;
        $pref->data = 'token';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'access_token',null])->atLeast()->once()->andReturn($pref);

        $this->artisan('firefly-iii:apply-rules ' . implode(' ', $parameters))
             ->expectsOutput('Please use the --accounts option to indicate the accounts to apply rules to.')
             ->assertExitCode(1);
    }

    /**
     * Basic call but only one expense account submitted
     *
     * @covers \FireflyIII\Console\Commands\Tools\ApplyRules
     */
    public function testHandleExpenseAccounts(): void
    {
        $ruleRepos      = $this->mock(RuleRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $this->mock(RuleEngine::class);
        $this->mock(JournalRepositoryInterface::class);
        $this->mock(GroupCollectorInterface::class);

        // data
        $expense = $this->getRandomExpense();

        // expected calls:
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($this->user());
        $ruleRepos->shouldReceive('setUser')->atLeast()->once();
        $ruleGroupRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('setUser')->atLeast()->once();
        $accountRepos->shouldReceive('findNull')->atLeast()->once()->withArgs([$expense->id])->andReturn($expense);


        $parameters = [
            '--user=1',
            '--token=token',
            '--accounts=' . $expense->id,
            '--all_rules',
        ];

        // mock Preferences Facade:
        $pref = new Preference;
        $pref->data = 'token';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'access_token',null])->atLeast()->once()->andReturn($pref);

        $this->artisan('firefly-iii:apply-rules ' . implode(' ', $parameters))
             ->expectsOutput('Please make sure all accounts in --accounts are asset accounts or liabilities.')
             ->assertExitCode(1);
    }

}
