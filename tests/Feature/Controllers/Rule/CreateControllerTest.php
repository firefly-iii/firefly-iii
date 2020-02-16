<?php
/**
 * CreateControllerTest.php
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

namespace tests\Feature\Controllers\Rule;


use FireflyIII\Models\Rule;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class CreateControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CreateControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Rule\CreateController
     */
    public function testCreate(): void
    {
        // mock stuff
        $billRepos      = $this->mock(BillRepositoryInterface::class);
        $ruleRepos      = $this->mock(RuleRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);

        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_rules_create');

        $ruleGroupRepos->shouldReceive('count')->atLeast()->once()->andReturn(1);
        $ruleRepos->shouldReceive('count')->atLeast()->once()->andReturn(1);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('rules.create', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Rule\CreateController
     */
    public function testCreateFromBill(): void
    {
        // mock stuff
        $billRepos      = $this->mock(BillRepositoryInterface::class);
        $ruleRepos      = $this->mock(RuleRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);

        $this->mockDefaultSession();

        $ruleGroupRepos->shouldReceive('count')->atLeast()->once()->andReturn(1);
        $ruleRepos->shouldReceive('count')->atLeast()->once()->andReturn(1);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);


        $this->be($this->user());
        $response = $this->get(route('rules.create-from-bill', [1, 1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Rule\CreateController
     */
    public function testCreatePreviousInput(): void
    {
        $old = [
            'rule-trigger'       => ['description_is'],
            'rule-trigger-stop'  => ['1'],
            'rule-trigger-value' => ['X'],
            'rule-action'        => ['set_category'],
            'rule-action-stop'   => ['1'],
            'rule-action-value'  => ['x'],
        ];
        $this->session(['_old_input' => $old]);

        // mock stuff
        $ruleRepos      = $this->mock(RuleRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);

        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_rules_create');

        $ruleGroupRepos->shouldReceive('count')->atLeast()->once()->andReturn(1);
        $ruleRepos->shouldReceive('count')->atLeast()->once()->andReturn(1);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('rules.create', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Rule\CreateController
     * @covers       \FireflyIII\Http\Requests\RuleFormRequest
     */
    public function testStore(): void
    {
        // mock stuff
        $repository     = $this->mock(RuleRepositoryInterface::class);
        $this->mock(RuleGroupRepositoryInterface::class);
        $this->mock(UserRepositoryInterface::class);

        $this->mockDefaultSession();
        Preferences::shouldReceive('mark')->atLeast()->once();


        $repository->shouldReceive('store')->andReturn(new Rule);

        $this->session(['rules.create.uri' => 'http://localhost']);
        $data = [
            'rule_group_id' => 1,
            'active'        => 1,
            'title'         => 'A',
            'trigger'       => 'store-journal',
            'description'   => 'D',
            'triggers' => [
                [
                    'type'            => 'description_is',
                    'value'           => 'A',
                    'stop_processing' => '0',

                ],
            ],
            'actions'  => [
                [
                    'type'            => 'set_category',
                    'value'           => 'C',
                    'stop_processing' => '0',

                ],
            ],
        ];
        $this->be($this->user());
        $response = $this->post(route('rules.store', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}
