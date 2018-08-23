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


use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 *
 * Class RuleGroupControllerTest
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
        Log::debug(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleGroupController
     */
    public function testDelete(): void
    {
        /** @var RuleGroup $ruleGroup */
        $ruleGroup = $this->user()->ruleGroups()->first();

        // mock stuff:
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);

        // mock calls:
        $ruleGroupRepos->shouldReceive('setUser')->once();
        $ruleGroupRepos->shouldReceive('destroy')->once()->andReturn(true);

        $response = $this->delete('/api/v1/rule_groups/' . $ruleGroup->id);
        $response->assertStatus(204);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleGroupController
     */
    public function testIndex(): void
    {
        $ruleGroups = $this->user()->ruleGroups()->get();

        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $ruleGroupRepos->shouldReceive('setUser')->once();
        $ruleGroupRepos->shouldReceive('get')->once()->andReturn($ruleGroups);


        // call API
        $response = $this->get('/api/v1/rule_groups');
        $response->assertStatus(200);
        $response->assertSee($ruleGroups->first()->title);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleGroupController
     */
    public function testShow(): void
    {
        /** @var RuleGroup $ruleGroup */
        $ruleGroup = $this->user()->ruleGroups()->first();
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $ruleGroupRepos->shouldReceive('setUser')->once();


        // call API
        $response = $this->get('/api/v1/rule_groups/' . $ruleGroup->id);
        $response->assertStatus(200);
        $response->assertSee($ruleGroup->title);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleGroupController
     * @covers \FireflyIII\Api\V1\Requests\RuleGroupRequest
     */
    public function testStore(): void
    {
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $ruleGroupRepos->shouldReceive('setUser')->once();
        $ruleGroup = $this->user()->ruleGroups()->first();
        $data      = [
            'title'       => 'Store new rule ' . random_int(1, 100000),
            'active'      => 1,
            'description' => 'Hello',
        ];

        $ruleGroupRepos->shouldReceive('store')->once()->andReturn($ruleGroup);

        // test API
        $response = $this->post('/api/v1/rule_groups', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);

    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\RuleGroupController
     * @covers \FireflyIII\Api\V1\Requests\RuleGroupRequest
     */
    public function testUpdate(): void
    {
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $ruleGroupRepos->shouldReceive('setUser')->once();
        $ruleGroup = $this->user()->ruleGroups()->first();
        $data      = [
            'title'       => 'Store new rule ' . random_int(1, 100000),
            'active'      => 1,
            'description' => 'Hello',
        ];

        $ruleGroupRepos->shouldReceive('update')->once()->andReturn($ruleGroup);

        // test API
        $response = $this->put('/api/v1/rule_groups/' . $ruleGroup->id, $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);

    }

}