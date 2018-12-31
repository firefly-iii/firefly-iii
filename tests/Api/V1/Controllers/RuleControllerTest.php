<?php
/**
 * RuleControllerTest.php
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
use FireflyIII\Models\Rule;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\TransactionRules\TransactionMatcher;
use FireflyIII\Transformers\RuleTransformer;
use FireflyIII\Transformers\TransactionTransformer;
use Illuminate\Support\Collection;
use Laravel\Passport\Passport;
use Log;
use Queue;
use Tests\TestCase;

/**
 *
 * Class RuleControllerTest
 */
class RuleControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Passport::actingAs($this->user());
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleController
     */
    public function testDelete(): void
    {
        /** @var Rule $rule */
        $rule = $this->user()->rules()->first();

        // mock stuff:
        $ruleRepos    = $this->mock(RuleRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        // mock calls:
        $accountRepos->shouldReceive('setUser')->once();
        $ruleRepos->shouldReceive('setUser')->once();
        $ruleRepos->shouldReceive('destroy')->once()->andReturn(true);

        $response = $this->delete('/api/v1/rules/' . $rule->id);
        $response->assertStatus(204);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleController
     */
    public function testIndex(): void
    {
        $ruleRepos    = $this->mock(RuleRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $transformer  = $this->mock(RuleTransformer::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();

        $accountRepos->shouldReceive('setUser')->once();
        $ruleRepos->shouldReceive('setUser')->once();
        $ruleRepos->shouldReceive('getAll')->once()->andReturn(new Collection);


        // call API
        $response = $this->get('/api/v1/rules');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleController
     */
    public function testShow(): void
    {
        $rule         = $this->user()->rules()->first();
        $ruleRepos    = $this->mock(RuleRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $transformer  = $this->mock(RuleTransformer::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        $accountRepos->shouldReceive('setUser')->once();
        $ruleRepos->shouldReceive('setUser')->once();


        // call API
        $response = $this->get('/api/v1/rules/' . $rule->id);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleController
     * @covers \FireflyIII\Api\V1\Requests\RuleRequest
     */
    public function testStore(): void
    {
        $ruleRepos    = $this->mock(RuleRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $transformer  = $this->mock(RuleTransformer::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        $ruleRepos->shouldReceive('setUser')->once();
        $rule = $this->user()->rules()->first();
        $data = [
            'title'           => 'Store new rule',
            'rule_group_id'   => 1,
            'trigger'         => 'store-journal',
            'strict'          => 1,
            'stop_processing' => 1,
            'active'          => 1,
            'triggers'        => [
                [
                    'type'            => 'description_is',
                    'value'           => 'Hello',
                    'stop_processing' => 1,
                ],
            ],
            'actions'         => [
                [
                    'type'            => 'add_tag',
                    'value'           => 'A',
                    'stop_processing' => 1,
                ],
            ],
        ];

        $accountRepos->shouldReceive('setUser')->once();
        $ruleRepos->shouldReceive('store')->once()->andReturn($rule);

        // test API
        $response = $this->post('/api/v1/rules', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);

    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleController
     * @covers \FireflyIII\Api\V1\Requests\RuleRequest
     */
    public function testStoreNoActions(): void
    {
        $ruleRepos    = $this->mock(RuleRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $transformer  = $this->mock(RuleTransformer::class);

        $accountRepos->shouldReceive('setUser')->once();
        $ruleRepos->shouldReceive('setUser')->once();
        $rule = $this->user()->rules()->first();
        $data = [
            'title'           => 'Store new rule',
            'rule_group_id'   => 1,
            'trigger'         => 'store-journal',
            'strict'          => 1,
            'stop_processing' => 1,
            'active'          => 1,
            'triggers'        => [
                [
                    'name'            => 'description_is',
                    'value'           => 'Hello',
                    'stop_processing' => 1,
                ],
            ],
            'actions'         => [
            ],
        ];

        // test API
        $response = $this->post('/api/v1/rules', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleController
     * @covers \FireflyIII\Api\V1\Requests\RuleRequest
     */
    public function testStoreNoTriggers(): void
    {
        $ruleRepos    = $this->mock(RuleRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $transformer  = $this->mock(RuleTransformer::class);

        $accountRepos->shouldReceive('setUser')->once();
        $ruleRepos->shouldReceive('setUser')->once();
        $rule = $this->user()->rules()->first();
        $data = [
            'title'           => 'Store new rule',
            'rule_group_id'   => 1,
            'trigger'         => 'store-journal',
            'strict'          => 1,
            'stop_processing' => 1,
            'active'          => 1,
            'triggers'        => [
            ],
            'actions'         => [
                [
                    'name'            => 'add_tag',
                    'value'           => 'A',
                    'stop_processing' => 1,
                ],
            ],
        ];

        // test API
        $response = $this->post('/api/v1/rules', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertSee('');

    }

    /**
     *
     */
    public function testTestRule(): void
    {
        $rule         = $this->user()->rules()->first();
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $matcher      = $this->mock(TransactionMatcher::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $ruleRepos    = $this->mock(RuleRepositoryInterface::class);
        $transformer     = $this->mock(TransactionTransformer::class);

        $transformer->shouldReceive('setParameters')->atLeast()->once();

        $asset = $this->getRandomAsset();
        $repository->shouldReceive('setUser')->once();
        $ruleRepos->shouldReceive('setUser')->once();

        $repository->shouldReceive('findNull')->withArgs([1])->andReturn($asset);
        $repository->shouldReceive('findNull')->withArgs([2])->andReturn($asset);
        $repository->shouldReceive('findNull')->withArgs([3])->andReturn(null);
        $repository->shouldReceive('isAsset')->withArgs([1])->andReturn(true);
        $repository->shouldReceive('isAsset')->withArgs([2])->andReturn(false);

        $matcher->shouldReceive('setRule')->once();
        $matcher->shouldReceive('setEndDate')->once();
        $matcher->shouldReceive('setStartDate')->once();
        $matcher->shouldReceive('setSearchLimit')->once();
        $matcher->shouldReceive('setTriggeredLimit')->once();
        $matcher->shouldReceive('setAccounts')->once();
        $matcher->shouldReceive('findTransactionsByRule')->once()->andReturn(new Collection);


        $response = $this->get(route('api.v1.rules.test', [$rule->id]) . '?accounts=1,2,3');
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleController
     */
    public function testTriggerRule(): void
    {

        $rule         = $this->user()->rules()->first();
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $matcher      = $this->mock(TransactionMatcher::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $ruleRepos    = $this->mock(RuleRepositoryInterface::class);
        $transformer  = $this->mock(RuleTransformer::class);

        $asset = $this->getRandomAsset();
        $repository->shouldReceive('setUser')->once();
        $ruleRepos->shouldReceive('setUser')->once();
        $repository->shouldReceive('findNull')->withArgs([1])->andReturn($asset);
        $repository->shouldReceive('findNull')->withArgs([2])->andReturn($asset);
        $repository->shouldReceive('findNull')->withArgs([3])->andReturn(null);
        $repository->shouldReceive('isAsset')->andReturn(true, false);

        Queue::fake();


        $response = $this->post(route('api.v1.rules.trigger', [$rule->id]) . '?accounts=1,2,3');
        $response->assertStatus(204);

        Queue::assertPushed(
            ExecuteRuleOnExistingTransactions::class, function (Job $job) use ($rule) {
            return $job->getRule()->id === $rule->id;
        }
        );
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleController
     * @covers \FireflyIII\Api\V1\Requests\RuleRequest
     */
    public function testUpdate(): void
    {
        $ruleRepos    = $this->mock(RuleRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $transformer  = $this->mock(RuleTransformer::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        $accountRepos->shouldReceive('setUser')->once();
        $ruleRepos->shouldReceive('setUser')->once();
        /** @var Rule $rule */
        $rule = $this->user()->rules()->first();
        $data = [
            'title'           => 'Store new rule',
            'rule_group_id'   => 1,
            'trigger'         => 'store-journal',
            'strict'          => 1,
            'stop_processing' => 1,
            'active'          => 1,
            'triggers'        => [
                [
                    'type'            => 'description_is',
                    'value'           => 'Hello',
                    'stop_processing' => 1,
                ],
            ],
            'actions'         => [
                [
                    'type'            => 'add_tag',
                    'value'           => 'A',
                    'stop_processing' => 1,
                ],
            ],
        ];

        $ruleRepos->shouldReceive('update')->once()->andReturn($rule);

        // test API
        $response = $this->put('/api/v1/rules/' . $rule->id, $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);

    }

}
