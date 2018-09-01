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


use FireflyIII\Models\Rule;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use Laravel\Passport\Passport;
use Log;
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
        Log::debug(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleController
     */
    public function testDelete(): void
    {
        /** @var Rule $rule */
        $rule = $this->user()->rules()->first();

        // mock stuff:
        $ruleRepos = $this->mock(RuleRepositoryInterface::class);

        // mock calls:
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
        $rules = $this->user()->rules()->get();

        $ruleRepos = $this->mock(RuleRepositoryInterface::class);
        $ruleRepos->shouldReceive('setUser')->once();
        $ruleRepos->shouldReceive('getAll')->once()->andReturn($rules);


        // call API
        $response = $this->get('/api/v1/rules');
        $response->assertStatus(200);
        $response->assertSee($rules->first()->title);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleController
     */
    public function testShow(): void
    {
        $rule = $this->user()->rules()->first();

        $ruleRepos = $this->mock(RuleRepositoryInterface::class);
        $ruleRepos->shouldReceive('setUser')->once();


        // call API
        $response = $this->get('/api/v1/rules/' . $rule->id);
        $response->assertStatus(200);
        $response->assertSee($rule->title);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleController
     * @covers \FireflyIII\Api\V1\Requests\RuleRequest
     */
    public function testStore(): void
    {
        $ruleRepos = $this->mock(RuleRepositoryInterface::class);
        $ruleRepos->shouldReceive('setUser')->once();
        $rule = $this->user()->rules()->first();
        $data = [
            'title'           => 'Store new rule',
            'rule_group_id'   => 1,
            'trigger'         => 'store-journal',
            'strict'          => 1,
            'stop_processing' => 1,
            'active'          => 1,
            'rule_triggers'   => [
                [
                    'name'            => 'description_is',
                    'value'           => 'Hello',
                    'stop_processing' => 1,
                ],
            ],
            'rule_actions'    => [
                [
                    'name'            => 'add_tag',
                    'value'           => 'A',
                    'stop_processing' => 1,
                ],
            ],
        ];

        $ruleRepos->shouldReceive('store')->once()->andReturn($rule);

        // test API
        $response = $this->post('/api/v1/rules', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);

    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleController
     * @covers \FireflyIII\Api\V1\Requests\RuleRequest
     */
    public function testUpdate(): void
    {
        $ruleRepos = $this->mock(RuleRepositoryInterface::class);
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
            'rule_triggers'   => [
                [
                    'name'            => 'description_is',
                    'value'           => 'Hello',
                    'stop_processing' => 1,
                ],
            ],
            'rule_actions'    => [
                [
                    'name'            => 'add_tag',
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

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleController
     * @covers \FireflyIII\Api\V1\Requests\RuleRequest
     */
    public function testStoreNoTriggers(): void
    {
        $ruleRepos = $this->mock(RuleRepositoryInterface::class);
        $ruleRepos->shouldReceive('setUser')->once();
        $rule = $this->user()->rules()->first();
        $data = [
            'title'           => 'Store new rule',
            'rule_group_id'   => 1,
            'trigger'         => 'store-journal',
            'strict'          => 1,
            'stop_processing' => 1,
            'active'          => 1,
            'rule_triggers'   => [
            ],
            'rule_actions'    => [
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
     * @covers \FireflyIII\Api\V1\Controllers\RuleController
     * @covers \FireflyIII\Api\V1\Requests\RuleRequest
     */
    public function testStoreNoActions(): void
    {
        $ruleRepos = $this->mock(RuleRepositoryInterface::class);
        $ruleRepos->shouldReceive('setUser')->once();
        $rule = $this->user()->rules()->first();
        $data = [
            'title'           => 'Store new rule',
            'rule_group_id'   => 1,
            'trigger'         => 'store-journal',
            'strict'          => 1,
            'stop_processing' => 1,
            'active'          => 1,
            'rule_triggers'   => [
                [
                    'name'            => 'description_is',
                    'value'           => 'Hello',
                    'stop_processing' => 1,
                ],
            ],
            'rule_actions'    => [
            ],
        ];

        // test API
        $response = $this->post('/api/v1/rules', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
    }

}