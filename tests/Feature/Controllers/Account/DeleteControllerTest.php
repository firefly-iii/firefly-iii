<?php
/**
 * DeleteControllerTest.php
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

namespace Tests\Feature\Controllers\Account;


use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;


/**
 *
 * Class DeleteControllerTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DeleteControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Account\DeleteController
     * @covers \FireflyIII\Http\Controllers\Controller
     */
    public function testDelete(): void
    {
        // mock stuff
        $repository = $this->mock(AccountRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $asset      = $this->getRandomAsset();

        $repository->shouldReceive('getAccountsByType')->withArgs([[AccountType::ASSET]])->andReturn(new Collection);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        // mock default session stuff
        $this->mockDefaultSession();

        $this->be($this->user());
        $response = $this->get(route('accounts.delete', [$asset->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Account\DeleteController
     * @covers \FireflyIII\Http\Controllers\Controller
     */
    public function testDestroy(): void
    {
        // mock stuff
        $repository = $this->mock(AccountRepositoryInterface::class);
        $asset      = $this->getRandomAsset();
        $repository->shouldReceive('findNull')->withArgs([0])->once()->andReturn(null);
        $repository->shouldReceive('destroy')->andReturn(true);

        // mock default session stuff
        $this->mockDefaultSession();

        Preferences::shouldReceive('mark')->atLeast()->once();

        $this->session(['accounts.delete.uri' => 'http://localhost/accounts/show/1']);

        $this->be($this->user());
        $response = $this->post(route('accounts.destroy', [$asset->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }


}
