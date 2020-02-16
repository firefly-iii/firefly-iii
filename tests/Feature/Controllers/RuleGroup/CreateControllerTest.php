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

namespace Tests\Feature\Controllers\RuleGroup;


use FireflyIII\Models\RuleGroup;
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
     * @covers \FireflyIII\Http\Controllers\RuleGroup\CreateController
     */
    public function testCreate(): void
    {
        $this->mockDefaultSession();
        $this->mock(RuleGroupRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('rule-groups.create'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }


    /**
     * @covers       \FireflyIII\Http\Controllers\RuleGroup\CreateController
     * @covers       \FireflyIII\Http\Requests\RuleGroupFormRequest
     */
    public function testStore(): void
    {
        $this->mockDefaultSession();
        $repository = $this->mock(RuleGroupRepositoryInterface::class);

        Preferences::shouldReceive('mark')->atLeast()->once();

        $this->session(['rule-groups.create.uri' => 'http://localhost']);
        $repository->shouldReceive('store')->andReturn(new RuleGroup);
        $repository->shouldReceive('find')->andReturn(new RuleGroup);
        $data = [
            'title'       => 'A',
            'active'      => '1',
            'description' => 'No description',
        ];

        $this->be($this->user());
        $response = $this->post(route('rule-groups.store', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

}
