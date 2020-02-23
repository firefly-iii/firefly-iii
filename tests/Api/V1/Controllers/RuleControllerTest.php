<?php
/**
 * RuleControllerTest.php
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
use FireflyIII\Models\Preference;
use FireflyIII\Models\Rule;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\TransactionRules\Engine\RuleEngine;
use FireflyIII\TransactionRules\TransactionMatcher;
use FireflyIII\Transformers\RuleTransformer;
use FireflyIII\Transformers\TransactionGroupTransformer;
use Laravel\Passport\Passport;
use Log;
use Preferences;
use Tests\TestCase;
use Mockery;

/**
 *
 * Class RuleControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
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
        Log::info(sprintf('Now in %s.', get_class($this)));
    }


    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleController
     * @covers \FireflyIII\Api\V1\Requests\RuleStoreRequest
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
        $response = $this->post(route('api.v1.rules.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);

    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleController
     * @covers \FireflyIII\Api\V1\Requests\RuleStoreRequest
     */
    public function testStoreNoActions(): void
    {
        $ruleRepos    = $this->mock(RuleRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $this->mock(RuleTransformer::class);
        Preferences::shouldReceive('mark');

        $accountRepos->shouldReceive('setUser')->once();
        $ruleRepos->shouldReceive('setUser')->once();
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
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.rules.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'errors'  => [
                    'title' => [
                        'Rule must have at least one action.',
                    ],
                ],
                'message' => 'The given data was invalid.',
            ]);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleController
     * @covers \FireflyIII\Api\V1\Requests\RuleStoreRequest
     */
    public function testStoreNoTriggers(): void
    {
        $ruleRepos    = $this->mock(RuleRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $this->mock(RuleTransformer::class);

        $accountRepos->shouldReceive('setUser')->once();
        $ruleRepos->shouldReceive('setUser')->once();
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
                    'type'            => 'add_tag',
                    'value'           => 'A',
                    'stop_processing' => 1,
                ],
            ],
        ];

        // test API
        $response = $this->post(route('api.v1.rules.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'errors'  => [
                    'title' => [
                        'Rule must have at least one trigger.',
                    ],
                ],
                'message' => 'The given data was invalid.',
            ]);

    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleController
     * @covers \FireflyIII\Api\V1\Requests\RuleTestRequest
     */
    public function testTestRule(): void
    {
        $rule = $this->user()->rules()->first();

        // mock used classes.
        $repository  = $this->mock(AccountRepositoryInterface::class);
        $matcher     = $this->mock(TransactionMatcher::class);
        $ruleRepos   = $this->mock(RuleRepositoryInterface::class);
        $transformer = $this->mock(TransactionGroupTransformer::class);

        $asset   = $this->getRandomAsset();
        $expense = $this->getRandomExpense();

        $repository->shouldReceive('setUser')->once();
        $ruleRepos->shouldReceive('setUser')->once();

        $repository->shouldReceive('findNull')->withArgs([1])->andReturn($asset);
        $repository->shouldReceive('findNull')->withArgs([2])->andReturn($expense);
        $repository->shouldReceive('findNull')->withArgs([3])->andReturn(null);

        $matcher->shouldReceive('setRule')->once();
        $matcher->shouldReceive('setEndDate')->once();
        $matcher->shouldReceive('setStartDate')->once();
        $matcher->shouldReceive('setSearchLimit')->once();
        $matcher->shouldReceive('setTriggeredLimit')->once();
        $matcher->shouldReceive('setAccounts')->once();
        $matcher->shouldReceive('findTransactionsByRule')->once()->andReturn([[1]]);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock Preferences Facade:
        $pref = new Preference;
        $pref->data = 50;

        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'listPageSize', 50])->atLeast()->once()->andReturn($pref);


        $response = $this->get(route('api.v1.rules.test', [$rule->id]) . '?accounts=1,2,3');
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleController
     * @covers \FireflyIII\Api\V1\Requests\RuleTriggerRequest
     */
    public function testTriggerRule(): void
    {
        $rule       = $this->user()->rules()->first();
        $repository = $this->mock(AccountRepositoryInterface::class);
        $ruleRepos  = $this->mock(RuleRepositoryInterface::class);
        $collector  = $this->mock(GroupCollectorInterface::class);
        $ruleEngine = $this->mock(RuleEngine::class);
        Preferences::shouldReceive('mark');

        // new mocks for ruleEngine
        $ruleEngine->shouldReceive('setUser')->atLeast()->once();
        $ruleEngine->shouldReceive('setRulesToApply')->atLeast()->once();
        $ruleEngine->shouldReceive('setTriggerMode')->atLeast()->once();
        $ruleEngine->shouldReceive('processJournalArray')->atLeast()->once();

        $collector->shouldReceive('setAccounts')->atLeast()->once();
        $collector->shouldReceive('setRange')->atLeast()->once();
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn([['x']]);


        $asset   = $this->getRandomAsset();
        $expense = $this->getRandomExpense();

        $repository->shouldReceive('setUser')->once();
        $ruleRepos->shouldReceive('setUser')->once();
        $repository->shouldReceive('findNull')->withArgs([1])->andReturn($asset);
        $repository->shouldReceive('findNull')->withArgs([2])->andReturn($expense);
        $repository->shouldReceive('findNull')->withArgs([3])->andReturn(null);

        $response = $this->post(route('api.v1.rules.trigger', [$rule->id]) . '?accounts=1,2,3&start_date=2019-01-01&end_date=2019-01-02');
        $response->assertStatus(204);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleController
     */
    public function testMoveRuleDown(): void
    {
        /** @var Rule $rule */
        $rule = $this->user()->rules()->first();

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
        $ruleRepos->shouldReceive('find')->once()->andReturn($rule);
        $ruleRepos->shouldReceive('moveDown')->once();

        // test API
        $response = $this->post(route('api.v1.rules.down', [$rule->id]), ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleController
     */
    public function testMoveRuleUp(): void
    {
        /** @var Rule $rule */
        $rule = $this->user()->rules()->first();

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
        $ruleRepos->shouldReceive('find')->once()->andReturn($rule);
        $ruleRepos->shouldReceive('moveUp')->once();

        // test API
        $response = $this->post(route('api.v1.rules.up', [$rule->id]), ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleController
     * @covers \FireflyIII\Api\V1\Requests\RuleUpdateRequest
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
        $response = $this->put(route('api.v1.rules.update', [$rule->id]), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);

    }

}
