<?php
/**
 * RuleGroupControllerTest.php
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

namespace Tests\Api\V1\Controllers;


use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Jobs\ExecuteRuleOnExistingTransactions;
use FireflyIII\Jobs\Job;
use FireflyIII\Models\Preference;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\TransactionRules\Engine\RuleEngine;
use FireflyIII\TransactionRules\TransactionMatcher;
use FireflyIII\Transformers\RuleGroupTransformer;
use FireflyIII\Transformers\TransactionGroupTransformer;
use Illuminate\Support\Collection;
use Laravel\Passport\Passport;
use Log;
use Mockery;
use Preferences;
use Queue;
use Tests\TestCase;

/**
 *
 * Class RuleGroupControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RuleGroupControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Passport::actingAs($this->user());
        Log::info(sprintf('Now in %s.', get_class($this)));
    }


    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleGroupController
     * @covers \FireflyIII\Api\V1\Requests\RuleGroupRequest
     */
    public function testStore(): void
    {
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $transformer    = $this->mock(RuleGroupTransformer::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        $accountRepos->shouldReceive('setUser')->once();
        $ruleGroupRepos->shouldReceive('setUser')->once();
        $ruleGroup = $this->user()->ruleGroups()->first();
        $data      = [
            'title'       => 'Store new rule group ' . $this->randomInt(),
            'active'      => 1,
            'description' => 'Hello',
        ];

        $ruleGroupRepos->shouldReceive('store')->once()->andReturn($ruleGroup);



        // test API
        $response = $this->post(route('api.v1.rule_groups.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);

    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleGroupController
     * @covers \FireflyIII\Api\V1\Requests\RuleGroupTestRequest
     */
    public function testTestGroupBasic(): void
    {
        $group = $this->user()->ruleGroups()->first();
        $rule  = $this->user()->rules()->first();

        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $repository     = $this->mock(AccountRepositoryInterface::class);
        $matcher        = $this->mock(TransactionMatcher::class);
        $transformer    = $this->mock(TransactionGroupTransformer::class);


        // mock calls
        $asset   = $this->getRandomAsset();
        $expense = $this->getRandomExpense();


        $ruleGroupRepos->shouldReceive('setUser')->once();
        $repository->shouldReceive('setUser')->once();

        $ruleGroupRepos->shouldReceive('getActiveRules')->once()->andReturn(new Collection([$rule]));

        $repository->shouldReceive('findNull')->withArgs([1])->andReturn($asset);
        $repository->shouldReceive('findNull')->withArgs([2])->andReturn($expense);
        $repository->shouldReceive('findNull')->withArgs([3])->andReturn(null);

        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $matcher->shouldReceive('setRule')->once();
        $matcher->shouldReceive('setEndDate')->once();
        $matcher->shouldReceive('setStartDate')->once();
        $matcher->shouldReceive('setSearchLimit')->once();
        $matcher->shouldReceive('setTriggeredLimit')->once();
        $matcher->shouldReceive('setAccounts')->once();
        $matcher->shouldReceive('findTransactionsByRule')->once()->andReturn([]);

        // mock Preferences Facade:
        $pref = new Preference;
        $pref->data = 50;

        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'listPageSize', 50])->atLeast()->once()->andReturn($pref);


        // call API
        $response = $this->get(route('api.v1.rule_groups.test', [$group->id]) . '?accounts=1,2,3');
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleGroupController
     * @covers \FireflyIII\Api\V1\Requests\RuleGroupTestRequest
     */
    public function testTestGroupEmpty(): void
    {
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);

        $accountRepos->shouldReceive('setUser')->once();
        $ruleGroupRepos->shouldReceive('setUser')->once();
        $ruleGroupRepos->shouldReceive('getActiveRules')->once()->andReturn(new Collection);


        // mock Preferences Facade:
        $pref = new Preference;
        $pref->data = 50;

        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'listPageSize', 50])->atLeast()->once()->andReturn($pref);

        // call API
        $group    = $this->user()->ruleGroups()->first();
        Log::warning('The following error is part of a test.');
        $response = $this->get(route('api.v1.rule_groups.test', [$group->id]), ['Accept' => 'application/json']);
        $response->assertStatus(500);
        $response->assertSee('200023');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleGroupController
     * @covers \FireflyIII\Api\V1\Requests\RuleGroupTriggerRequest
     */
    public function testTrigger(): void
    {
        $group   = $this->user()->ruleGroups()->first();
        $rule    = $this->user()->rules()->first();
        $asset   = $this->getRandomAsset();
        $expense = $this->getRandomExpense();

        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $repository     = $this->mock(AccountRepositoryInterface::class);
        $matcher        = $this->mock(TransactionMatcher::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $collector  = $this->mock(GroupCollectorInterface::class);
        $ruleEngine = $this->mock(RuleEngine::class);


        // new mocks for ruleEngine
        $ruleEngine->shouldReceive('setUser')->atLeast()->once();
        $ruleEngine->shouldReceive('setRulesToApply')->atLeast()->once();
        $ruleEngine->shouldReceive('setTriggerMode')->atLeast()->once();
        $ruleEngine->shouldReceive('processJournalArray')->atLeast()->once();

        $collector->shouldReceive('setAccounts')->atLeast()->once();
        $collector->shouldReceive('setRange')->atLeast()->once();
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn([['x']]);


        $ruleGroupRepos->shouldReceive('setUser')->once();
        $repository->shouldReceive('setUser')->once();
        $ruleGroupRepos->shouldReceive('getActiveRules')->once()->andReturn(new Collection([$rule]));

        $repository->shouldReceive('findNull')->withArgs([1])->andReturn($asset);
        $repository->shouldReceive('findNull')->withArgs([2])->andReturn($expense);
        $repository->shouldReceive('findNull')->withArgs([3])->andReturn(null);
        $repository->shouldReceive('isAsset')->withArgs([1])->andReturn(true);
        $repository->shouldReceive('isAsset')->withArgs([2])->andReturn(false);



        $response = $this->post(route('api.v1.rule_groups.trigger', [$group->id]) . '?accounts=1,2,3&start_date=2019-01-01&end_date=2019-01-02');
        $response->assertStatus(204);

    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleGroupController
     * @covers \FireflyIII\Api\V1\Requests\RuleGroupRequest
     */
    public function testUpdate(): void
    {
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $transformer    = $this->mock(RuleGroupTransformer::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        $accountRepos->shouldReceive('setUser')->once();
        $ruleGroupRepos->shouldReceive('setUser')->once();
        $ruleGroup = $this->user()->ruleGroups()->first();
        $data      = [
            'title'       => 'Store new rule ' . $this->randomInt(),
            'active'      => 1,
            'description' => 'Hello',
        ];

        $ruleGroupRepos->shouldReceive('update')->once()->andReturn($ruleGroup);

        // test API
        $response = $this->put(route('api.v1.rule_groups.update', [$ruleGroup->id]), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);

    }


    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleGroupController
     */
    public function testMoveRuleGroupDown(): void
    {
        /** @var RuleGroup $group */
        $group = $this->user()->ruleGroups()->first();

        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $transformer    = $this->mock(RuleGroupTransformer::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        $accountRepos->shouldReceive('setUser')->once();
        $ruleGroupRepos->shouldReceive('setUser')->once();
        $ruleGroupRepos->shouldReceive('find')->once()->andReturn($group);
        $ruleGroupRepos->shouldReceive('moveDown')->once();

        // test API
        $response = $this->post(route('api.v1.rule_groups.down', [$group->id]), ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleGroupController
     */
    public function testMoveRuleGroupUp(): void
    {
        /** @var RuleGroup $group */
        $group = $this->user()->ruleGroups()->first();

        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $transformer    = $this->mock(RuleGroupTransformer::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        $accountRepos->shouldReceive('setUser')->once();
        $ruleGroupRepos->shouldReceive('setUser')->once();
        $ruleGroupRepos->shouldReceive('find')->once()->andReturn($group);
        $ruleGroupRepos->shouldReceive('moveUp')->once();

        // test API
        $response = $this->post(route('api.v1.rule_groups.up', [$group->id]), ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

}
