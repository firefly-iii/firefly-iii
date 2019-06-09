<?php
/**
 * RuleGroupControllerTest.php
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

namespace Tests\Api\V1\Controllers;


use FireflyIII\Jobs\ExecuteRuleOnExistingTransactions;
use FireflyIII\Jobs\Job;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\TransactionRules\TransactionMatcher;
use FireflyIII\Transformers\RuleGroupTransformer;
use FireflyIII\Transformers\TransactionGroupTransformer;
use Illuminate\Support\Collection;
use Laravel\Passport\Passport;
use Log;
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
            'title'       => 'Store new rule group ' . random_int(1, 100000),
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

        // call API
        $group    = $this->user()->ruleGroups()->first();
        $response = $this->get(route('api.v1.rule_groups.test', [$group->id]), ['Accept' => 'application/json']);
        $response->assertStatus(500);
        $response->assertSee('{"message":"No rules in this rule group.","exception":"FireflyIII\\\\Exceptions\\\\FireflyException"');
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
        $ruleGroupRepos->shouldReceive('setUser')->once();
        $repository->shouldReceive('setUser')->once();
        $ruleGroupRepos->shouldReceive('getActiveRules')->once()->andReturn(new Collection([$rule]));

        $repository->shouldReceive('findNull')->withArgs([1])->andReturn($asset);
        $repository->shouldReceive('findNull')->withArgs([2])->andReturn($expense);
        $repository->shouldReceive('findNull')->withArgs([3])->andReturn(null);
        $repository->shouldReceive('isAsset')->withArgs([1])->andReturn(true);
        $repository->shouldReceive('isAsset')->withArgs([2])->andReturn(false);

        Queue::fake();
        $response = $this->post(route('api.v1.rule_groups.trigger', [$group->id]) . '?accounts=1,2,3&start_date=2019-01-01&end_date=2019-01-02');
        $response->assertStatus(204);


        Queue::assertPushed(
            ExecuteRuleOnExistingTransactions::class, function (Job $job) use ($rule) {
            return $job->getRule()->id === $rule->id;
        }
        );
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
            'title'       => 'Store new rule ' . random_int(1, 100000),
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
