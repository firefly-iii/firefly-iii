<?php
/**
 * EditControllerTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Feature\Controllers\Category;


use FireflyIII\Models\Category;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class EditControllerTest
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
     * @covers \FireflyIII\Http\Controllers\Category\EditController
     */
    public function testEdit(): void
    {
        Log::debug('Test edit()');
        // mock stuff
        $this->mock(CategoryRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $this->mockDefaultSession();

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('categories.edit', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Category\EditController
     * @covers \FireflyIII\Http\Requests\CategoryFormRequest
     */
    public function testUpdate(): void
    {
        Log::debug('Test update()');
        $category   = Category::first();
        $repository = $this->mock(CategoryRepositoryInterface::class);
        $this->mock(AccountRepositoryInterface::class);
        $this->mock(UserRepositoryInterface::class);
        $this->mockDefaultSession();
        Preferences::shouldReceive('mark')->atLeast()->once()->withNoArgs();

        $repository->shouldReceive('update');
        $repository->shouldReceive('findNull')->andReturn($category);

        $this->session(['categories.edit.uri' => 'http://localhost']);

        $data = [
            'name'   => 'Updated Category ' . $this->randomInt(),
            'active' => 1,
        ];
        $this->be($this->user());
        $response = $this->post(route('categories.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}