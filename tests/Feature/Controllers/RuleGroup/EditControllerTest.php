<?php
/**
 * EditControllerTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
 * Class EditControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EditControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\RuleGroup\EditController
     */
    public function testDown(): void
    {
        $this->mockDefaultSession();
        $repository = $this->mock(RuleGroupRepositoryInterface::class);

        $repository->shouldReceive('moveDown');

        $this->be($this->user());
        $response = $this->get(route('rule-groups.down', [1]));
        $response->assertStatus(302);
        $response->assertRedirect(route('rules.index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\RuleGroup\EditController
     */
    public function testEdit(): void
    {
        $this->mockDefaultSession();
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        /** @var RuleGroup $ruleGroup */
        $ruleGroup              = $this->user()->ruleGroups()->first();
        $ruleGroup->description = 'Some description ' . $this->randomInt();
        $ruleGroup->save();

        $this->be($this->user());
        $response = $this->get(route('rule-groups.edit', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSee($ruleGroup->description);
    }


    /**
     * @covers \FireflyIII\Http\Controllers\RuleGroup\EditController
     */
    public function testUp(): void
    {
        $this->mockDefaultSession();
        $repository = $this->mock(RuleGroupRepositoryInterface::class);

        $repository->shouldReceive('moveUp');

        $this->be($this->user());
        $response = $this->get(route('rule-groups.up', [1]));
        $response->assertStatus(302);
        $response->assertRedirect(route('rules.index'));
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\RuleGroup\EditController
     * @covers       \FireflyIII\Http\Requests\RuleGroupFormRequest
     */
    public function testUpdate(): void
    {
        $this->mockDefaultSession();
        $repository = $this->mock(RuleGroupRepositoryInterface::class);

        $data = [
            'id'          => 1,
            'title'       => 'C',
            'description' => 'XX',
        ];
        $this->session(['rule-groups.edit.uri' => 'http://localhost']);

        $repository->shouldReceive('update');
        $repository->shouldReceive('find')->andReturn(RuleGroup::first());
        Preferences::shouldReceive('mark')->atLeast()->once();

        $this->be($this->user());
        $response = $this->post(route('rule-groups.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}
